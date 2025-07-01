'use client' 

import { useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card'
import { AlertOctagon, Terminal, FileWarning, Database, ServerCrash } from 'lucide-react'
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
              ¡ACCIÓN REQUERIDA! Error Crítico de Configuración
            </CardTitle>
            <CardDescription className="text-lg pt-2">
              <strong>El código de la aplicación es correcto.</strong> El problema está en la configuración de tu servidor. Hemos detectado la causa exacta y aquí tienes la solución.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            
            {isConnectionRefused && (
              <div className="bg-destructive/10 p-6 rounded-lg border border-destructive">
                <h3 className="text-2xl font-semibold flex items-center text-destructive">
                  <Database className="mr-3 h-7 w-7" />
                  Causa del Problema: Conexión Rechazada (`ECONNREFUSED`)
                </h3>
                <p className="mt-2 text-base text-destructive-foreground/90">
                  ¡Buenas noticias! Las variables de entorno se cargaron correctamente. El problema ahora es que tu servidor de base de datos está **rechazando la conexión** desde Docker por seguridad.
                </p>
                <div className="mt-4 space-y-2">
                    <p className="font-semibold text-lg">Solución en 3 pasos:</p>
                    <ol className="list-decimal list-inside space-y-2 pl-4 text-base">
                      <li>
                        <strong>Abre el archivo de configuración de MariaDB/MySQL en tu servidor:</strong>
                        <div className="bg-muted p-2 rounded-md mt-1"><code className="font-mono text-sm">sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf</code></div>
                      </li>
                      <li>
                        <strong>Busca la línea <code className="font-mono text-sm bg-muted px-1 rounded">bind-address = 127.0.0.1</code> y coméntala añadiendo un `#` al principio:</strong>
                        <div className="bg-muted p-2 rounded-md mt-1"><code className="font-mono text-sm"># bind-address = 127.0.0.1</code></div>
                      </li>
                      <li>
                        <strong>Guarda el archivo, reinicia la base de datos y luego reinicia el contenedor:</strong>
                        <div className="bg-muted p-2 rounded-md mt-1 space-y-1">
                          <code className="font-mono text-sm block">sudo systemctl restart mariadb</code>
                          <code className="font-mono text-sm block">sudo docker restart qreasy-container</code>
                        </div>
                      </li>
                    </ol>
                </div>
              </div>
            )}

            {isConfigError && (
               <div className="bg-destructive/10 p-6 rounded-lg border border-destructive">
                <h3 className="text-2xl font-semibold flex items-center text-destructive">
                  <FileWarning className="mr-3 h-7 w-7" />
                  Causa del Problema: Variables de Entorno Faltantes
                </h3>
                <p className="mt-2 text-base text-destructive-foreground/90">
                  Este error significa que el contenedor Docker **no encontró o no pudo leer el archivo `.env.local`** al arrancar. Esto suele ocurrir por un error en el comando `docker run`.
                </p>
                <div className="mt-4 space-y-2">
                    <p className="font-semibold text-lg">Solución:</p>
                    <p>Asegúrate de que tu comando `docker run` contenga la parte <code className="font-mono text-sm bg-muted px-1 rounded">--env-file ./.env.local</code>. Sigue los 4 pasos del `README.md`.</p>
                     <p className="font-semibold text-lg">Comando correcto para reiniciar:</p>
                     <div className="bg-muted p-2 rounded-md mt-1 space-y-1">
                          <code className="font-mono text-sm block"># 1. Detener y eliminar el contenedor viejo (¡muy importante!)</code>
                          <code className="font-mono text-sm block">sudo docker stop qreasy-container</code>
                          <code className="font-mono text-sm block">sudo docker rm qreasy-container</code>
                          <code className="font-mono text-sm block"># 2. Reconstruir la imagen por si acaso</code>
                          <code className="font-mono text-sm block">sudo docker build -t qreasy-app .</code>
                          <code className="font-mono text-sm block"># 3. Lanzar el nuevo contenedor con el comando completo</code>
                          <code className="font-mono text-sm block">sudo docker run -d --restart unless-stopped --name qreasy-container -p 3001:3000 --env-file ./.env.local qreasy-app</code>
                        </div>
                </div>
              </div>
            )}
            
          </CardContent>
          <CardFooter className="text-center border-t border-border pt-4">
              <p className="text-muted-foreground text-sm max-w-2xl mx-auto mt-2">
                Para más detalles, siempre puedes revisar los logs completos del contenedor con: 
                <code className="font-mono text-sm bg-muted px-1 rounded-md mx-1">sudo docker logs qreasy-container</code>
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
