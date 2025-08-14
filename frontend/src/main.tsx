import React from 'react'
import { createRoot } from 'react-dom/client'
import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import './styles.css'
import AppShell from './shell/AppShell'
import Login from './views/Login'
import CargarLicencia from './views/CargarLicencia'
import Historial from './views/Historial'
import Catalogo from './views/Catalogo'

const router = createBrowserRouter([
	{ path: '/', element: <AppShell />, children: [
		{ index: true, element: <CargarLicencia /> },
		{ path: 'login', element: <Login /> },
		{ path: 'licencias', element: <CargarLicencia /> },
		{ path: 'historial', element: <Historial /> },
		{ path: 'catalogo', element: <Catalogo /> },
	]}
])

const qc = new QueryClient()

createRoot(document.getElementById('root')!).render(
	<React.StrictMode>
		<QueryClientProvider client={qc}>
			<RouterProvider router={router} />
		</QueryClientProvider>
	</React.StrictMode>
)