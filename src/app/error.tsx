'use client' 

import { useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { AlertOctagon, Terminal } from 'lucide-react'
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

  if (isConfigError) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center p-4 bg-gradient-to-br from-destructive/10 to-background">
        <Card className="w-full max-w-3xl text-left shadow-2xl border-2 border-destructive">
          <CardHeader>
            <CardTitle className="flex items-center text-3xl font-headline text-destructive">
              <AlertOctagon className="mr-4 h-10 w-10" />
              ¡ACCIÓN REQUERIDA! Error de Configuración
            </CardTitle>
            <CardDescription className="text-lg pt-2">
              <strong>El código de la aplicación es correcto.</strong> El problema está en la configuración de tu servidor, y ahora podemos diagnosticarlo con certeza.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="bg-destructive/10 p-4 rounded-md">
              <p className="font-semibold text-destructive">
                Error Detectado:
              </p>
              <code className="text-destructive font-mono text-sm whitespace-pre-wrap mt-2 block">
                {error.message}
              </code>
            </div>
            
            <div className="space-y-4 text-center border-t border-border pt-6">
              <h3 className="text-2xl font-semibold flex items-center justify-center">
                <Terminal className="mr-3 h-7 w-7 text-primary" />
                La Prueba Definitiva está en los Logs
              </h3>
              <p className="text-muted-foreground text-base max-w-2xl mx-auto">
                Para encontrar la causa raíz, conéctate a tu servidor y ejecuta este comando para ver qué está pasando dentro del contenedor:
              </p>
              <div className="bg-muted p-3 rounded-md text-left">
                <code className="font-mono text-sm text-foreground">
                  sudo docker logs qreasy-container
                </code>
              </div>
              <p className="text-muted-foreground text-base max-w-2xl mx-auto">
                Busca una sección que empiece con <code className="bg-muted px-1.5 py-0.5 rounded-sm">--- [QREASY_DOCKER_DEBUG] Imprimiendo variables de entorno ---</code>. Si tus variables <code className="bg-muted px-1.5 py-0.5 rounded-sm">DB_HOST</code>, <code className="bg-muted px-1.5 py-0.5 rounded-sm">DB_USER</code>, etc., **NO aparecen en esa lista**, el problema está en tu archivo <code className="bg-muted px-1.5 py-0.5 rounded-sm">.env.local</code> o en el comando `docker run`. Usa el script <code className="bg-muted px-1.5 py-0.5 rounded-sm">./configure-env.sh</code> para crearlo correctamente y reinicia el contenedor como se indica en el archivo <code className="bg-muted px-1.5 py-0.5 rounded-sm">README.md</code>.
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    )
  }

  // Fallback for other types of errors
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
