'use client' 

import { useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { AlertOctagon, Terminal, FileWarning } from 'lucide-react'
import { Button } from '@/components/ui/button';

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string }
  reset: () => void
}) {
  useEffect(() => {
    console.error("[QREASY_CLIENT_ERROR_BOUNDARY]", error)
  }, [error])

  const isConfigError = error.message.includes("La configuración de la base de datos es incompleta");
  const isConnectionRefused = error.message.includes("ECONNREFUSED");

  if (isConfigError || isConnectionRefused) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center p-4 bg-gradient-to-br from-destructive/10 to-background">
        <Card className="w-full max-w-4xl text-left shadow-2xl border-2 border-destructive">
          <CardHeader>
            <CardTitle className="flex items-center text-3xl font-headline text-destructive">
              <AlertOctagon className="mr-4 h-10 w-10" />
              ¡ACCIÓN REQUERIDA! Error Crítico de Entorno
            </CardTitle>
            <CardDescription className="text-lg pt-2">
              <strong>El código de la aplicación es correcto.</strong> El problema está en la configuración de tu servidor. Hemos detectado la causa exacta.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            
            {isConnectionRefused && (
              <div className="bg-destructive/10 p-6 rounded-lg border border-destructive">
                <h3 className="text-2xl font-semibold flex items-center text-destructive">
                  <Terminal className="mr-3 h-7 w-7" />
                  Causa del Problema: `ECONNREFUSED`
                </h3>
                <p className="mt-2 text-base text-destructive-foreground/90">
                  Este error significa que tu servidor de base de datos está **rechazando activamente** la conexión desde Docker. Es una medida de seguridad.
                </p>
                <p className="mt-4 font-semibold text-lg">
                  Solución: Sigue la guía en el archivo `README.md` para editar el archivo de configuración de tu base de datos y cambiar la directiva `bind-address`.
                </p>
              </div>
            )}

            {isConfigError && (
               <div className="bg-destructive/10 p-6 rounded-lg border border-destructive">
                <h3 className="text-2xl font-semibold flex items-center text-destructive">
                  <FileWarning className="mr-3 h-7 w-7" />
                  Causa del Problema: Variables de Entorno Faltantes
                </h3>
                <p className="mt-2 text-base text-destructive-foreground/90">
                  Este error significa que el archivo `.env.local` con las credenciales **no se está cargando** en el contenedor Docker.
                </p>
                <p className="mt-4 font-semibold text-lg">
                  Solución: Sigue la "Guía Definitiva de Despliegue en 4 Pasos" del archivo `README.md`. Asegúrate de ejecutar el script `./configure-env.sh` en el directorio correcto.
                </p>
              </div>
            )}
            
            <div className="text-center border-t border-border pt-6">
              <h3 className="text-xl font-semibold">
                ¿Necesitas más pistas?
              </h3>
              <p className="text-muted-foreground text-base max-w-2xl mx-auto mt-2">
                Conéctate a tu servidor y ejecuta este comando para ver los logs detallados del contenedor:
              </p>
              <div className="bg-muted p-3 rounded-md text-left mt-3">
                <code className="font-mono text-sm text-foreground">
                  sudo docker logs qreasy-container
                </code>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    )
  }

  // Fallback para otros tipos de errores
  return (
    <div className="min-h-screen flex flex-col items-center justify-center p-4 bg-background">
      <Card className="w-full max-w-md text-center shadow-xl">
        <CardHeader>
          <CardTitle className="flex items-center justify-center text-2xl font-headline text-destructive">
            Ocurrió un Error Inesperado
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-muted-foreground">
            La aplicación encontró un problema. Intenta recargar la página.
          </p>
          <pre className="text-xs text-left bg-muted p-2 rounded-md overflow-x-auto">
            <code>{error.message}</code>
          </pre>
          <Button onClick={() => reset()} variant="outline">
            Recargar Página
          </Button>
        </CardContent>
      </Card>
    </div>
  )
}
