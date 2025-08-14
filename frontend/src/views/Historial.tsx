import { useQuery } from '@tanstack/react-query'
import api from '../services/api'
import { useState } from 'react'
import SelectDocente from '../components/SelectDocente'

export default function Historial() {
	const [docenteId, setDocenteId] = useState('')
	const { data, refetch } = useQuery({ queryKey:['licencias', docenteId], queryFn: async()=> (await api.get('/licencias', { params: docenteId? { docente_id: docenteId } : {} })).data })
	return (
		<div className="space-y-4">
			<h2 className="text-xl font-semibold">Historial</h2>
			<div className="max-w-md">
				<SelectDocente value={docenteId} onChange={v=>{setDocenteId(v); setTimeout(()=>refetch(), 0)}} />
			</div>
			<table className="min-w-full border">
				<thead className="bg-gray-100 text-left">
					<tr>
						<th className="p-2 border">ID</th>
						<th className="p-2 border">Docente</th>
						<th className="p-2 border">Desde</th>
						<th className="p-2 border">Hasta</th>
						<th className="p-2 border">Notificaciones</th>
					</tr>
				</thead>
				<tbody>
					{(data||[]).map((l:any) => (
						<tr key={l.id} className="odd:bg-white even:bg-gray-50">
							<td className="p-2 border">{l.id}</td>
							<td className="p-2 border">{l.docente_id}</td>
							<td className="p-2 border">{l.desde}</td>
							<td className="p-2 border">{l.hasta}</td>
							<td className="p-2 border text-sm">{l.notificaciones?.map((n:any,i:number)=>(<span key={i} className={"inline-block mr-2 "+(n.estado==='enviado'?'text-green-700':'text-red-700')}>{n.curso_id}:{n.canal}</span>))}</td>
						</tr>
					))}
				</tbody>
			</table>
		</div>
	)
}