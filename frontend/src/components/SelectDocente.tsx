import { useQuery } from '@tanstack/react-query'
import api from '../services/api'

export default function SelectDocente({ value, onChange }:{ value?: string, onChange: (v:string)=>void }) {
	const { data } = useQuery({ queryKey: ['docentes'], queryFn: async ()=> (await api.get('/docentes')).data })
	const docentes = data || []
	return (
		<div className="flex flex-col">
			<label className="text-sm">Docente</label>
			<input list="docentes-list" value={value||''} onChange={e=>onChange(e.target.value)} className="border rounded p-2" aria-label="Seleccionar docente" placeholder="DOC-001" />
			<datalist id="docentes-list">
				{docentes.map((d:any) => (
					<option key={d.id} value={d.id}>{d.apellido} {d.nombre}</option>
				))}
			</datalist>
		</div>
	)
}