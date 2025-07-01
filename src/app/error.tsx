'use client' 

import { useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card'
import { AlertOctagon, Terminal, Database, ServerCrash, GitPullRequest } from 'lucide-react'
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

  const isDeploymentError = error.message.includes("ECONNREFUSED") || error.message.includes("La configuración de la base de datos es incompleta");

  // This is the new, combined error page for deployment issues.
  if (isDeploymentError) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center p-4 bg-gradient-to-br from-destructive/10 to-background">
        <Card className="w-full max-w-4xl text-left shadow-2xl border-2 border-destructive">
          <CardHeader>
            <CardTitle className="flex items-center text-3xl font-headline text-destructive">
              <AlertOctagon className="mr-4 h-10 w-10" />
              ¡ACCIÓN REQUERIDA! Error de Despliegue
            </CardTitle>
            <CardDescription className="text-lg pt-2">
              <strong>El código de la aplicación es correcto, pero el despliegue está fallando.</strong> Este error casi siempre ocurre por un problema al actualizar el código en tu servidor.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            
            <div className="bg-destructive/10 p-6 rounded-lg border border-destructive">
              <h3 className="text-2xl font-semibold flex items-center text-destructive">
                <GitPullRequest className="mr-3 h-7 w-7" />
                Causa Raíz Más Probable: `git pull` Fallido
              </h3>
              <p className="mt-2 text-base text-destructive-foreground/90">
                Si al ejecutar `git pull origin master` en tu servidor ves el error <code className="font-mono text-sm bg-muted px-1 rounded-md">"Your local changes would be overwritten"</code>, significa que <strong>NO ESTÁS DESCARGANDO LAS CORRECCIONES</strong>.
              </p>
            </div>

            <div className="bg-amber-500/10 p-6 rounded-lg border border-amber-500">
                <h3 className="text-2xl font-semibold flex items-center text-amber-600">
                    <Terminal className="mr-3 h-7 w-7" />
                    Solución Definitiva (Forzar Actualización)
                </h3>
                <p className="mt-2 text-base">
                    Para solucionar el problema de Git y CUALQUIER otro error de despliegue, ejecuta estos dos comandos en tu servidor para forzar la sincronización con GitHub:
                </p>
                <div className="mt-4 space-y-2">
                    <ol className="list-decimal list-inside space-y-2 pl-4 text-base">
                        <li>
                            <strong>Descarta los cambios locales conflictivos:</strong>
                            <div className="bg-muted p-2 rounded-md mt-1"><code className="font-mono text-sm">git reset --hard HEAD</code></div>
                        </li>
                        <li>
                            <strong>Descarga la última versión del código:</strong>
                            <div className="bg-muted p-2 rounded-md mt-1"><code className="font-mono text-sm">git pull origin master</code></div>
                        </li>
                    </ol>
                    <p className="mt-4 font-semibold">
                        Después de ejecutar esos dos comandos, sigue la "Guía Definitiva de Despliegue" de 4 pasos que está al principio del archivo `README.md`.
                    </p>
                </div>
            </div>

          </CardContent>
          <CardFooter className="text-center border-t border-border pt-4">
              <p className="text-muted-foreground text-sm max-w-2xl mx-auto mt-2">
                Recuerda: El error no está en el código, sino en el proceso de llevar el código a tu servidor. Estos pasos solucionan ese proceso.
              </p>
          </CardFooter>
        </Card>
      </div>
    )
  }

  // Fallback para otros tipos de errores
  return (
    <div className="min-h-screen flex flex-col items-center justify-center p-4 bg-background">
      <Card className="w-full max-w-md text-center shadow-xl">
        <CardHeader className="items-center">
            <ServerCrash className="h-12 w-12 text-destructive" />
          <CardTitle className="text-2xl font-headline text-destructive">
            Ocurrió un Error Inesperado
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-muted-foreground">
            La aplicación encontró un problema que no pudimos diagnosticar automáticamente. Intenta recargar la página o revisa los logs del contenedor.
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
