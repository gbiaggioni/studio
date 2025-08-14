import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import api from '../services/api'
import { useState } from 'react'

function Docentes() {
	const qc = useQueryClient()
	const { data } = useQuery({ queryKey:['docentes'], queryFn: async()=> (await api.get('/docentes')).data })
	const [nuevo, setNuevo] = useState({ id:'', apellido:'', nombre:'', activo:true })
	const add = useMutation({ mutationFn: async()=> api.post('/docentes', nuevo), onSuccess:()=> qc.invalidateQueries({queryKey:['docentes']}) })
	return (
		<div className="space-y-2">
			<h3 className="font-semibold">Docentes</h3>
			<table className="min-w-full border text-sm">
				<thead className="bg-gray-100 text-left"><tr><th className="p-2 border">ID</th><th className="p-2 border">Nombre</th><th className="p-2 border">Activo</th></tr></thead>
				<tbody>
					{(data||[]).map((d:any)=>(<tr key={d.id} className="odd:bg-white even:bg-gray-50"><td className="p-2 border">{d.id}</td><td className="p-2 border">{d.apellido} {d.nombre}</td><td className="p-2 border">{d.activo?'Sí':'No'}</td></tr>))}
				</tbody>
			</table>
			<div className="flex gap-2">
				<input className="border p-1" placeholder="ID" value={nuevo.id} onChange={e=>setNuevo({...nuevo, id:e.target.value})} />
				<input className="border p-1" placeholder="Apellido" value={nuevo.apellido} onChange={e=>setNuevo({...nuevo, apellido:e.target.value})} />
				<input className="border p-1" placeholder="Nombre" value={nuevo.nombre} onChange={e=>setNuevo({...nuevo, nombre:e.target.value})} />
				<button className="bg-gray-800 text-white px-2 rounded" onClick={()=>add.mutate()}>Agregar</button>
			</div>
		</div>
	)
}

function Cursos() {
	const qc = useQueryClient()
	const { data } = useQuery({ queryKey:['cursos'], queryFn: async()=> (await api.get('/cursos')).data })
	const [nuevo, setNuevo] = useState({ id:'', turno:'Mañana' })
	const add = useMutation({ mutationFn: async()=> api.post('/cursos', { ...nuevo, preceptores: [] }), onSuccess:()=> qc.invalidateQueries({queryKey:['cursos']}) })
	const del = useMutation({ mutationFn: async(id:string)=> api.delete('/cursos', { params: { id } }), onSuccess:()=> qc.invalidateQueries({queryKey:['cursos']}) })
	return (
		<div className="space-y-2">
			<h3 className="font-semibold">Cursos</h3>
			<table className="min-w-full border text-sm">
				<thead className="bg-gray-100 text-left"><tr><th className="p-2 border">ID</th><th className="p-2 border">Turno</th><th className="p-2 border">Preceptores</th><th className="p-2 border">Acciones</th></tr></thead>
				<tbody>
					{(data||[]).map((c:any)=>(
						<tr key={c.id} className="odd:bg-white even:bg-gray-50">
							<td className="p-2 border">{c.id}</td>
							<td className="p-2 border">{c.turno}</td>
							<td className="p-2 border">{c.preceptores?.map((p:any)=>p.nombre).join(', ')}</td>
							<td className="p-2 border"><button className="text-red-700" onClick={()=> del.mutate(c.id)}>Eliminar</button></td>
						</tr>
					))}
				</tbody>
			</table>
			<div className="flex gap-2">
				<input className="border p-1" placeholder="ID" value={nuevo.id} onChange={e=>setNuevo({...nuevo, id:e.target.value})} />
				<select className="border p-1" value={nuevo.turno} onChange={e=>setNuevo({...nuevo, turno:e.target.value})}>
					<option>Mañana</option>
					<option>Tarde</option>
					<option>Noche</option>
				</select>
				<button className="bg-gray-800 text-white px-2 rounded" onClick={()=>add.mutate()}>Agregar</button>
			</div>
		</div>
	)
}

function Horarios() {
	const qc = useQueryClient()
	const { data } = useQuery({ queryKey:['horarios'], queryFn: async()=> (await api.get('/horarios')).data })
	const [nuevo, setNuevo] = useState({ curso_id:'', materia:'', docente_id:'', dia_semana:1, hora_inicio:'08:00', hora_fin:'09:20', aula:'' })
	const add = useMutation({ mutationFn: async()=> api.post('/horarios', nuevo), onSuccess:()=> qc.invalidateQueries({queryKey:['horarios']}) })
	return (
		<div className="space-y-2">
			<h3 className="font-semibold">Horarios</h3>
			<table className="min-w-full border text-sm">
				<thead className="bg-gray-100 text-left"><tr><th className="p-2 border">Curso</th><th className="p-2 border">Materia</th><th className="p-2 border">Docente</th><th className="p-2 border">Día</th><th className="p-2 border">Hora</th></tr></thead>
				<tbody>
					{(data||[]).map((h:any, i:number)=>(<tr key={i} className="odd:bg-white even:bg-gray-50"><td className="p-2 border">{h.curso_id}</td><td className="p-2 border">{h.materia}</td><td className="p-2 border">{h.docente_id}</td><td className="p-2 border">{h.dia_semana}</td><td className="p-2 border">{h.hora_inicio}–{h.hora_fin}</td></tr>))}
				</tbody>
			</table>
			<div className="grid md:grid-cols-6 gap-2">
				<input className="border p-1" placeholder="Curso" value={nuevo.curso_id} onChange={e=>setNuevo({...nuevo, curso_id:e.target.value})} />
				<input className="border p-1" placeholder="Materia" value={nuevo.materia} onChange={e=>setNuevo({...nuevo, materia:e.target.value})} />
				<input className="border p-1" placeholder="Docente" value={nuevo.docente_id} onChange={e=>setNuevo({...nuevo, docente_id:e.target.value})} />
				<input className="border p-1" placeholder="Día (1-7)" value={nuevo.dia_semana} onChange={e=>setNuevo({...nuevo, dia_semana: Number(e.target.value)})} />
				<input className="border p-1" placeholder="Desde" value={nuevo.hora_inicio} onChange={e=>setNuevo({...nuevo, hora_inicio:e.target.value})} />
				<input className="border p-1" placeholder="Hasta" value={nuevo.hora_fin} onChange={e=>setNuevo({...nuevo, hora_fin:e.target.value})} />
				<input className="border p-1 md:col-span-5" placeholder="Aula" value={nuevo.aula} onChange={e=>setNuevo({...nuevo, aula:e.target.value})} />
				<button className="bg-gray-800 text-white px-2 rounded" onClick={()=>add.mutate()}>Agregar</button>
			</div>
		</div>
	)
}

export default function Catalogo() {
	return (
		<div className="space-y-8">
			<h2 className="text-xl font-semibold">Catálogo</h2>
			<Docentes />
			<Cursos />
			<Horarios />
		</div>
	)
}