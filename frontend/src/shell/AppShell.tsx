import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import { useEffect } from 'react'

function useAuthGuard() {
	const navigate = useNavigate()
	useEffect(() => {
		const token = localStorage.getItem('api_token')
		if (!token) navigate('/login')
	}, [navigate])
}

export default function AppShell() {
	useAuthGuard()
	return (
		<div className="min-h-screen flex flex-col">
			<header className="border-b bg-white">
				<nav className="container mx-auto flex items-center gap-4 p-4" aria-label="Navegación principal">
					<h1 className="text-lg font-semibold">Licencias</h1>
					<NavLink to="/licencias" className={({isActive}) => isActive ? 'font-bold' : ''}>Cargar Licencia</NavLink>
					<NavLink to="/historial" className={({isActive}) => isActive ? 'font-bold' : ''}>Historial</NavLink>
					<NavLink to="/catalogo" className={({isActive}) => isActive ? 'font-bold' : ''}>Catálogo</NavLink>
					<div className="ml-auto" />
					<button onClick={() => { document.documentElement.classList.toggle('high-contrast') }} className="text-sm mr-4" aria-label="Alternar alto contraste">Alto contraste</button>
					<button onClick={() => {localStorage.removeItem('api_token'); location.href = '/login'}} className="text-sm" aria-label="Cerrar sesión (Salir)">Salir</button>
				</nav>
			</header>
			<main className="container mx-auto p-4">
				<Outlet />
			</main>
		</div>
	)
}