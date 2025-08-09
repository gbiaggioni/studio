"use client"

import React, { useEffect, useMemo, useState } from 'react'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js'
import { Bar } from 'react-chartjs-2'
import { Grid, _ } from 'gridjs-react'
import 'gridjs/dist/theme/mermaid.css'
import type { AttendanceRecord } from '@/lib/attendance'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

type ApiResult = {
  header: string[]
  records: AttendanceRecord[]
  metrics: {
    totalRecords: number
    uniqueUsers: number
    averagePerUser: number
    byDayOfWeek: { label: string; count: number }[]
    byHourOfDay: { hour: number; count: number }[]
  }
  distinct: {
    years: number[]
    months: number[]
    userids: string[]
  }
}

function parseDate(dateStr: string): Date {
  const [datePart, timePart] = dateStr.trim().split(/\s+/)
  const [d, m, y] = datePart.split('/').map(Number)
  const [hh, mm, ss] = (timePart || '00:00:00').split(':').map(Number)
  return new Date(y, m - 1, d, hh, mm, ss)
}

export default function PanelPage() {
  const [data, setData] = useState<ApiResult | null>(null)
  const [year, setYear] = useState<number | ''>('')
  const [month, setMonth] = useState<number | ''>('')
  const [userid, setUserid] = useState<string>('')
  const [search, setSearch] = useState<string>('')

  useEffect(() => {
    async function load() {
      const res = await fetch('/api/attendance', { cache: 'no-store' })
      const json = (await res.json()) as ApiResult
      setData(json)
    }
    load()
  }, [])

  const filteredRows = useMemo(() => {
    if (!data) return []
    return data.records.filter(r => {
      const dt = parseDate(r['fecha_hora'])
      const okYear = year ? dt.getFullYear() === year : true
      const okMonth = month ? dt.getMonth() + 1 === month : true
      const okUser = userid ? String(r['userid']) === userid : true
      const okSearch = search
        ? Object.values(r).some(v => String(v).toLowerCase().includes(search.toLowerCase()))
        : true
      return okYear && okMonth && okUser && okSearch
    })
  }, [data, year, month, userid, search])

  const metrics = useMemo(() => {
    const total = filteredRows.length
    const users = new Set(filteredRows.map(r => String(r['userid'])))
    const avg = users.size ? Number((total / users.size).toFixed(2)) : 0

    const dayLabels = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']
    const byDay = new Array(7).fill(0)
    const byHour = new Array(24).fill(0)
    for (const r of filteredRows) {
      const d = parseDate(r['fecha_hora'])
      byDay[d.getDay()]++
      byHour[d.getHours()]++
    }

    return {
      totalRecords: total,
      uniqueUsers: users.size,
      averagePerUser: avg,
      byDayOfWeek: dayLabels.map((label, i) => ({ label, count: byDay[i] })),
      byHourOfDay: byHour.map((count, hour) => ({ hour, count })),
    }
  }, [filteredRows])

  const columns = useMemo(() => data?.header || [], [data])
  const gridData = useMemo(() => {
    if (!data) return [] as string[][]
    return filteredRows.map(r => columns.map(c => r[c] ?? ''))
  }, [data, filteredRows, columns])

  function downloadCsv() {
    if (!data) return
    const header = columns.join(',')
    const rows = gridData.map(row =>
      row
        .map(cell => {
          if (cell == null) return ''
          const s = String(cell)
          return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s
        })
        .join(',')
    )
    const csv = [header, ...rows].join('\n')
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'reporte_procesado.csv'
    a.click()
    URL.revokeObjectURL(url)
  }

  return (
    <div className="p-6 space-y-6">
      <h1 className="text-2xl font-semibold">Panel de Control - Reporte Procesado</h1>

      {/* Filtros */}
      <div className="grid md:grid-cols-5 gap-4 items-end">
        <div>
          <label className="block text-sm font-medium">Año</label>
          <select
            className="mt-1 w-full border rounded p-2"
            value={year}
            onChange={e => setYear(e.target.value ? Number(e.target.value) : '')}
          >
            <option value="">Todos</option>
            {data?.distinct.years.map(y => (
              <option key={y} value={y}>
                {y}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium">Mes</label>
          <select
            className="mt-1 w-full border rounded p-2"
            value={month}
            onChange={e => setMonth(e.target.value ? Number(e.target.value) : '')}
          >
            <option value="">Todos</option>
            {data?.distinct.months.map(m => (
              <option key={m} value={m}>
                {m.toString().padStart(2, '0')}
              </option>
            ))}
          </select>
        </div>
        <div className="md:col-span-2">
          <label className="block text-sm font-medium">Usuario (userid)</label>
          <select className="mt-1 w-full border rounded p-2" value={userid} onChange={e => setUserid(e.target.value)}>
            <option value="">Todos</option>
            {data?.distinct.userids.map(u => (
              <option key={u} value={u}>
                {u}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium">Búsqueda</label>
          <input
            className="mt-1 w-full border rounded p-2"
            placeholder="Buscar..."
            value={search}
            onChange={e => setSearch(e.target.value)}
          />
        </div>
      </div>

      {/* Métricas */}
      <div className="grid md:grid-cols-4 gap-4">
        <div className="border rounded p-4">
          <div className="text-sm text-gray-500">Registros</div>
          <div className="text-2xl font-semibold">{metrics.totalRecords}</div>
        </div>
        <div className="border rounded p-4">
          <div className="text-sm text-gray-500">Usuarios únicos</div>
          <div className="text-2xl font-semibold">{metrics.uniqueUsers}</div>
        </div>
        <div className="border rounded p-4">
          <div className="text-sm text-gray-500">Promedio por usuario</div>
          <div className="text-2xl font-semibold">{metrics.averagePerUser}</div>
        </div>
        <div className="border rounded p-4 flex items-end justify-between">
          <div>
            <div className="text-sm text-gray-500">Descargar CSV</div>
            <button onClick={downloadCsv} className="mt-2 px-3 py-2 bg-blue-600 text-white rounded">
              Descargar filtrado
            </button>
          </div>
        </div>
      </div>

      {/* Gráficos */}
      <div className="grid md:grid-cols-2 gap-6">
        <div className="border rounded p-4">
          <h2 className="font-medium mb-2">Distribución por día de la semana</h2>
          <Bar
            data={{
              labels: metrics.byDayOfWeek.map(d => d.label),
              datasets: [
                {
                  label: 'Registros',
                  data: metrics.byDayOfWeek.map(d => d.count),
                  backgroundColor: 'rgba(59,130,246,0.5)',
                },
              ],
            }}
            options={{ responsive: true, plugins: { legend: { position: 'top' as const } } }}
          />
        </div>
        <div className="border rounded p-4">
          <h2 className="font-medium mb-2">Distribución por hora del día</h2>
          <Bar
            data={{
              labels: metrics.byHourOfDay.map(d => d.hour),
              datasets: [
                {
                  label: 'Registros',
                  data: metrics.byHourOfDay.map(d => d.count),
                  backgroundColor: 'rgba(16,185,129,0.5)',
                },
              ],
            }}
            options={{ responsive: true, plugins: { legend: { position: 'top' as const } } }}
          />
        </div>
      </div>

      {/* Tabla */}
      <div className="border rounded p-2 overflow-x-auto">
        {data ? (
          <Grid
            data={gridData}
            columns={columns}
            search={false}
            sort={true}
            pagination={{ limit: 20 }}
            className={{ table: 'min-w-full' }}
            language={{
              search: { placeholder: 'Buscar...' },
              pagination: {
                previous: 'Anterior',
                next: 'Siguiente',
                showing: 'Mostrando',
                results: () => 'registros',
              },
            }}
          />
        ) : (
          <div className="p-4">Cargando datos...</div>
        )}
      </div>
    </div>
  )
}