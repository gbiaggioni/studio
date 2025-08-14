import { FormEvent, useState } from 'react'

export default function Login() {
	const [token, setToken] = useState('')
	function onSubmit(e: FormEvent) {
		e.preventDefault()
		localStorage.setItem('api_token', token.trim())
		location.href = '/'
	}
	return (
		<div className="max-w-md mx-auto mt-16">
			<h2 className="text-xl font-semibold mb-4">Ingreso</h2>
			<form onSubmit={onSubmit} className="flex flex-col gap-3" aria-label="Formulario de ingreso por token">
				<label className="flex flex-col">
					<span className="text-sm">Token de API</span>
					<input aria-label="Token" className="border rounded p-2" value={token} onChange={e=>setToken(e.target.value)} required />
				</label>
				<button className="bg-blue-600 text-white rounded p-2">Entrar</button>
			</form>
		</div>
	)
}