import { parse } from 'csv-parse/sync'

export type AttendanceRecord = {
  // Dynamic columns preserved from CSV
  [key: string]: string
}

export type ProcessedResult = {
  header: string[]
  records: AttendanceRecord[]
  metrics: Metrics
  distinct: {
    years: number[]
    months: number[]
    userids: string[]
  }
}

export type Metrics = {
  totalRecords: number
  uniqueUsers: number
  averagePerUser: number
  byDayOfWeek: { label: string; count: number }[]
  byHourOfDay: { hour: number; count: number }[]
}

const SOURCE_URL = 'http://10.0.22.87/ZKTeco/data/reporte_consolidado.csv'

function parseDateTimeDDMMYYYY(dateTime: string): Date {
  // Expected format: dd/mm/yyyy hh:mm:ss
  const [datePart, timePart] = dateTime.trim().split(/\s+/)
  const [dayStr, monthStr, yearStr] = datePart.split('/')
  const [hourStr, minuteStr, secondStr] = (timePart || '00:00:00').split(':')
  const day = Number(dayStr)
  const monthZeroBased = Number(monthStr) - 1
  const year = Number(yearStr)
  const hour = Number(hourStr)
  const minute = Number(minuteStr)
  const second = Number(secondStr)
  return new Date(year, monthZeroBased, day, hour, minute, second)
}

function formatDateTimeDDMMYYYY(date: Date): string {
  const dd = String(date.getDate()).padStart(2, '0')
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const yyyy = String(date.getFullYear())
  const hh = String(date.getHours()).padStart(2, '0')
  const min = String(date.getMinutes()).padStart(2, '0')
  const ss = String(date.getSeconds()).padStart(2, '0')
  return `${dd}/${mm}/${yyyy} ${hh}:${min}:${ss}`
}

function minutesBetween(a: Date, b: Date): number {
  return Math.abs(b.getTime() - a.getTime()) / 60000
}

function addMinutes(date: Date, minutes: number): Date {
  return new Date(date.getTime() + minutes * 60000)
}

function isDifferentCalendarDay(a: Date, b: Date): boolean {
  return a.getFullYear() !== b.getFullYear() || a.getMonth() !== b.getMonth() || a.getDate() !== b.getDate()
}

export async function fetchAndProcessData(): Promise<ProcessedResult> {
  const res = await fetch(SOURCE_URL, { cache: 'no-store' })
  if (!res.ok) {
    throw new Error(`No se pudo descargar el CSV desde ${SOURCE_URL}: ${res.status} ${res.statusText}`)
  }
  const csvText = await res.text()
  return processCsv(csvText)
}

export function processCsv(csvText: string): ProcessedResult {
  const parsed = parse(csvText, {
    bom: true,
    columns: true,
    skip_empty_lines: true,
    trim: true,
  }) as AttendanceRecord[]

  if (parsed.length === 0) {
    return {
      header: [],
      records: [],
      metrics: emptyMetrics(),
      distinct: { years: [], months: [], userids: [] },
    }
  }

  // Capture original header order
  const header = Object.keys(parsed[0])
  if (!header.includes('userid') || !header.includes('fecha_hora')) {
    throw new Error('El CSV debe contener las columnas obligatorias: userid y fecha_hora')
  }

  // Group by userid and sort by fecha_hora
  const useridToRows = new Map<string, AttendanceRecord[]>()
  for (const row of parsed) {
    const uid = String(row['userid'])
    const arr = useridToRows.get(uid) || []
    arr.push(row)
    useridToRows.set(uid, arr)
  }
  for (const [uid, rows] of useridToRows) {
    rows.sort((a, b) => parseDateTimeDDMMYYYY(a['fecha_hora']).getTime() - parseDateTimeDDMMYYYY(b['fecha_hora']).getTime())
    useridToRows.set(uid, rows)
  }

  // Step 1 & 2 combined sensibly: deduplicate consecutive events < 15 minutes (keep the first, discard the last)
  const fifteenMinutes = 15
  const cleanedByUser = new Map<string, AttendanceRecord[]>()
  for (const [uid, rows] of useridToRows) {
    const cleaned: AttendanceRecord[] = []
    for (const row of rows) {
      if (cleaned.length === 0) {
        cleaned.push(row)
        continue
      }
      const prev = cleaned[cleaned.length - 1]
      const prevDt = parseDateTimeDDMMYYYY(prev['fecha_hora'])
      const currDt = parseDateTimeDDMMYYYY(row['fecha_hora'])
      const diffMin = minutesBetween(prevDt, currDt)
      if (diffMin < fifteenMinutes) {
        // discard current ("el último registro")
        continue
      } else {
        cleaned.push(row)
      }
    }
    cleanedByUser.set(uid, cleaned)
  }

  // Step 3: insert a new record when gap > 7 hours AND date changes (day boundary)
  const sevenHours = 7 * 60
  const enrichedByUser = new Map<string, AttendanceRecord[]>()
  for (const [uid, rows] of cleanedByUser) {
    const enriched: AttendanceRecord[] = []
    for (let i = 0; i < rows.length; i++) {
      const current = rows[i]
      enriched.push(current)
      const next = rows[i + 1]
      if (!next) continue
      const currDt = parseDateTimeDDMMYYYY(current['fecha_hora'])
      const nextDt = parseDateTimeDDMMYYYY(next['fecha_hora'])
      const diffMin = minutesBetween(currDt, nextDt)
      if (diffMin > sevenHours && isDifferentCalendarDay(currDt, nextDt)) {
        const inserted: AttendanceRecord = { ...current }
        inserted['fecha_hora'] = formatDateTimeDDMMYYYY(addMinutes(currDt, fifteenMinutes))
        enriched.push(inserted)
      }
    }
    enrichedByUser.set(uid, enriched)
  }

  // Step 1 in spec: "agregar columna con siguiente registro" - compute on final sequence so it reflects de-dup + insertions
  const appendedColumnName = 'siguiente_fecha_hora'
  const finalHeader = header.includes(appendedColumnName) ? header : [...header, appendedColumnName]

  const finalRecords: AttendanceRecord[] = []
  for (const [, rows] of enrichedByUser) {
    for (let i = 0; i < rows.length; i++) {
      const current = { ...rows[i] }
      const next = rows[i + 1]
      if (next) {
        current[appendedColumnName] = next['fecha_hora']
      } else {
        current[appendedColumnName] = ''
      }
      finalRecords.push(current)
    }
  }

  // Build metrics and distincts
  const metrics = computeMetrics(finalRecords)
  const distinct = computeDistincts(finalRecords)

  // Keep stable order: by userid then fecha_hora
  finalRecords.sort((a, b) => {
    if (String(a['userid']) < String(b['userid'])) return -1
    if (String(a['userid']) > String(b['userid'])) return 1
    return parseDateTimeDDMMYYYY(a['fecha_hora']).getTime() - parseDateTimeDDMMYYYY(b['fecha_hora']).getTime()
  })

  return { header: finalHeader, records: finalRecords, metrics, distinct }
}

function emptyMetrics(): Metrics {
  return {
    totalRecords: 0,
    uniqueUsers: 0,
    averagePerUser: 0,
    byDayOfWeek: [],
    byHourOfDay: [],
  }
}

function computeMetrics(records: AttendanceRecord[]): Metrics {
  const totalRecords = records.length
  const users = new Set(records.map(r => String(r['userid'])))
  const uniqueUsers = users.size
  const averagePerUser = uniqueUsers === 0 ? 0 : Number((totalRecords / uniqueUsers).toFixed(2))

  const dayLabels = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']
  const byDayMap = new Map<number, number>()
  const byHourMap = new Map<number, number>()

  for (let d = 0; d < 7; d++) byDayMap.set(d, 0)
  for (let h = 0; h < 24; h++) byHourMap.set(h, 0)

  for (const r of records) {
    const dt = parseDateTimeDDMMYYYY(r['fecha_hora'])
    byDayMap.set(dt.getDay(), (byDayMap.get(dt.getDay()) || 0) + 1)
    byHourMap.set(dt.getHours(), (byHourMap.get(dt.getHours()) || 0) + 1)
  }

  const byDayOfWeek = Array.from(byDayMap.entries()).map(([d, count]) => ({ label: dayLabels[d], count }))
  const byHourOfDay = Array.from(byHourMap.entries()).map(([hour, count]) => ({ hour, count }))

  return { totalRecords, uniqueUsers, averagePerUser, byDayOfWeek, byHourOfDay }
}

function computeDistincts(records: AttendanceRecord[]): { years: number[]; months: number[]; userids: string[] } {
  const years = new Set<number>()
  const months = new Set<number>()
  const userids = new Set<string>()
  for (const r of records) {
    const dt = parseDateTimeDDMMYYYY(r['fecha_hora'])
    years.add(dt.getFullYear())
    months.add(dt.getMonth() + 1)
    userids.add(String(r['userid']))
  }
  return {
    years: Array.from(years).sort((a, b) => a - b),
    months: Array.from(months).sort((a, b) => a - b),
    userids: Array.from(userids).sort(),
  }
}

export function filterRecords(records: AttendanceRecord[], filters: { year?: number; month?: number; userid?: string }): AttendanceRecord[] {
  return records.filter(r => {
    const dt = parseDateTimeDDMMYYYY(r['fecha_hora'])
    const okYear = filters.year ? dt.getFullYear() === filters.year : true
    const okMonth = filters.month ? dt.getMonth() + 1 === filters.month : true
    const okUser = filters.userid ? String(r['userid']) === filters.userid : true
    return okYear && okMonth && okUser
  })
}

export function toCsv(header: string[], rows: AttendanceRecord[]): string {
  const escape = (v: string) => {
    if (v == null) return ''
    const s = String(v)
    if (/[",\n]/.test(s)) {
      return '"' + s.replace(/"/g, '""') + '"'
    }
    return s
  }
  const lines: string[] = []
  lines.push(header.join(','))
  for (const r of rows) {
    lines.push(header.map(h => escape(r[h] ?? '')).join(','))
  }
  return lines.join('\n')
}