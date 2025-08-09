import { NextResponse } from 'next/server'
import { fetchAndProcessData } from '@/lib/attendance'

export const revalidate = 0

export async function GET() {
  try {
    const result = await fetchAndProcessData()
    return NextResponse.json(result)
  } catch (err: any) {
    return NextResponse.json({ error: err?.message || 'Error inesperado' }, { status: 500 })
  }
}