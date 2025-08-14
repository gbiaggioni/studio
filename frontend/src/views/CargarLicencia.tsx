import { useState } from 'react'
import { useMutation } from '@tanstack/react-query'
import api from '../services/api'
import SelectDocente from '../components/SelectDocente'

export default function CargarLicencia() {
	const [docenteId, setDocenteId] = useState('DOC-001')
	const [desde, setDesde] = useState('')
	const [hasta, setHasta] = useState('')
	const [motivo, setMotivo] = useState('')
	const [impacto, setImpacto] = useState<any|null>(null)

	const simular = useMutation({
		mutationFn: async () => {
			const { data } = await api.post('/licencias', { docente_id: docenteId, desde, hasta, motivo, simulate: true })
			return data
		},
		onSuccess: (data) => setImpacto(data.impacto),
	})

	const confirmar = useMutation({
		mutationFn: async () => {
			const { data } = await api.post('/licencias', { docente_id: docenteId, desde, hasta, motivo })
			return data
		},
		onSuccess: (data) => {
			alert('Notificaciones enviadas')
			setImpacto(data.impacto)
		},
	})

	const disableConfirm = !docenteId || !desde || !hasta || desde>hasta || !motivo || !impacto

	return (
		<div className="space-y-6">
			<h2 className="text-xl font-semibold">Cargar Licencia</h2>
			<div className="grid md:grid-cols-4 gap-4 items-end">
				<SelectDocente value={docenteId} onChange={setDocenteId} />
				<label className="flex flex-col">
					<span className="text-sm">Desde</span>
					<input type="date" value={desde} onChange={e=>setDesde(e.target.value)} className="border rounded p-2" aria-label="Fecha desde" />
				</label>
				<label className="flex flex-col">
					<span className="text-sm">Hasta</span>
					<input type="date" min={desde||undefined} value={hasta} onChange={e=>setHasta(e.target.value)} className="border rounded p-2" aria-label="Fecha hasta" />
				</label>
				<label className="flex flex-col md:col-span-1">
					<span className="text-sm">Motivo</span>
					<input value={motivo} onChange={e=>setMotivo(e.target.value)} className="border rounded p-2" aria-label="Motivo" placeholder="Enfermedad" />
				</label>
			</div>
			<div className="flex gap-2">
				<button onClick={()=>simular.mutate()} disabled={!docenteId||!desde||!hasta||desde>hasta||!motivo} className="bg-gray-900 text-white rounded px-3 py-2">Simular impacto</button>
				<button onClick={()=>{ if (confirm('¿Confirmar y enviar notificaciones a preceptores?')) confirmar.mutate() }} disabled={disableConfirm} className="bg-blue-600 text-white rounded px-3 py-2">Confirmar y notificar</button>
			</div>
			{impacto && (
				<div>
					<div className="text-sm text-gray-600 mb-2">Cursos afectados: <strong>{impacto.cursos.length}</strong> — Clases: <strong>{impacto.clases.length}</strong></div>
					<table className="min-w-full border" aria-label="Tabla de clases afectadas">
						<thead className="bg-gray-100 text-left">
							<tr>
								<th className="p-2 border">Curso</th>
								<th className="p-2 border">Materia</th>
								<th className="p-2 border">Fecha</th>
								<th className="p-2 border">Hora</th>
								<th className="p-2 border">Aula</th>
							</tr>
						</thead>
						<tbody>
							{impacto.clases.map((c:any, i:number) => (
								<tr key={i} className="odd:bg-white even:bg-gray-50">
									<td className="p-2 border">{c.curso_id}</td>
									<td className="p-2 border">{c.materia}</td>
									<td className="p-2 border">{c.fecha}</td>
									<td className="p-2 border">{c.hora_inicio}–{c.hora_fin}</td>
									<td className="p-2 border">{c.aula||'-'}</td>
								</tr>
							))}
						</tbody>
					</table>
				</div>
			)}
		</div>
	)
}