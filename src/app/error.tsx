'use client' // Error components must be Client Components

import { useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { AlertTriangle, Database, FileCheck, Terminal } from 'lucide-react'

// Componente especializado para el error de configuración de la base de datos
function DatabaseConfigError({ error, reset }: { error: Error, reset: () => void }) {
  return (
    <Card className="w-full max-w-3xl text-left shadow-xl border-destructive/50">
      <CardHeader>
        <CardTitle className="flex items-center text-2xl font-headline text-destructive">
          <Database className="mr-3 h-7 w-7" />
          Error Crítico: Falla la Conexión a la Base de Datos
        </CardTitle>
        <CardDescription>
          Tu aplicación se ha iniciado, pero no puede encontrar las credenciales de la base de datos. Esto es casi siempre un problema con el archivo de configuración <strong>.env.local</strong>.
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        <div className="bg-destructive/10 p-4 rounded-md">
          <p className="font-mono text-sm text-destructive font-semibold">
            Error Reportado:
          </p>
          <code className="text-destructive font-mono text-sm whitespace-pre-wrap">
            {error.message}
          </code>
        </div>
        
        <div className="space-y-4">
          <h3 className="text-lg font-semibold flex items-center"><FileCheck className="mr-2 h-5 w-5 text-primary" /> Cómo Solucionarlo: Revisa tu archivo `.env.local`</h3>
          <p className="text-muted-foreground">
            Conéctate a tu servidor por SSH y abre este archivo para editarlo:
          </p>
          <pre className="bg-muted p-3 rounded-md text-sm text-foreground overflow-x-auto">
            <code>nano /home/esquel.org.ar/qr/.env.local</code>
          </pre>
          <p className="text-muted-foreground">
            Asegúrate de que el contenido se vea <strong>exactamente</strong> así, con tus credenciales reales y sin comillas:
          </p>
          <pre className="bg-muted p-3 rounded-md text-sm text-foreground overflow-x-auto">
            <code>
              DB_HOST=172.17.0.1<br />
              DB_USER=tu_usuario_de_bd<br />
              DB_PASSWORD=tu_contraseña_secreta<br />
              DB_NAME=el_nombre_de_tu_bd<br />
              NEXT_PUBLIC_BASE_URL=https://qr.esquel.org.ar
            </code>
          </pre>
          <p className="text-muted-foreground">
            <span className="font-bold">Puntos clave:</span> El `DB_HOST` debe ser `172.17.0.1`. No debe haber espacios antes o después del signo `=`.
          </p>
        </div>
        
        <div className="space-y-4">
            <h3 className="text-lg font-semibold flex items-center"><Terminal className="mr-2 h-5 w-5 text-primary" /> Reinicia el Contenedor</h3>
            <p className="text-muted-foreground">
              Después de guardar los cambios en `.env.local`, debes reiniciar el contenedor para que los lea. Ejecuta estos 3 comandos en orden:
            </p>
            <pre className="bg-muted p-3 rounded-md text-sm text-foreground overflow-x-auto">
              <code>
                sudo docker stop qreasy-container<br />
                sudo docker rm qreasy-container<br />
                sudo docker run -d --restart unless-stopped --name qreasy-container -p 3001:3000 --env-file ./.env.local qreasy-app
              </code>
            </pre>
             <p className="text-muted-foreground">
              <span className="font-bold">Importante:</span> Asegúrate de ejecutar estos comandos desde el directorio correcto: `/home/esquel.org.ar/qr`.
            </p>
        </div>
      </CardContent>
      <CardFooter>
         <Button onClick={() => reset()} variant="outline" className="w-full">
            Intentar Recargar la Página
          </Button>
      </CardFooter>
    </Card>
  )
}

// Componente para errores genéricos
function GenericError({ error, reset }: { error: Error, reset: () => void }) {
  return (
    <Card className="w-full max-w-2xl text-center shadow-xl border-destructive/50">
        <CardHeader>
          <CardTitle className="flex items-center justify-center text-2xl font-headline text-destructive">
            <AlertTriangle className="mr-2 h-6 w-6" />
            Ocurrió un Error en el Servidor
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-6">
          <p className="text-muted-foreground">
            Lo sentimos, la aplicación encontró un problema. El error específico es:
          </p>
          <div className="bg-destructive/10 p-4 rounded-md text-left max-h-60 overflow-auto">
            <code className="text-destructive font-mono text-sm whitespace-pre-wrap">
              {error.message}
            </code>
          </div>
          <p className="text-muted-foreground">
            Por favor, revisa la configuración mencionada en el error. Hemos registrado los detalles para que nuestro equipo pueda revisarlos.
          </p>
          <Button
            onClick={() => reset()}
            variant="outline"
          >
            Intentar de nuevo
          </Button>
        </CardContent>
      </Card>
  )
}

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string }
  reset: () => void
}) {
  useEffect(() => {
    // Log the error to an error reporting service
    console.error("[GLOBAL_ERROR_BOUNDARY_CLIENT]", error)
  }, [error])

  // Comprueba si es el error específico de configuración de la base de datos
  const isDbConfigError = error.message.includes("La configuración de la base de datos es incompleta");

  return (
    <div className="min-h-screen flex flex-col items-center justify-center p-4 bg-gradient-to-br from-background to-secondary/30">
      {isDbConfigError ? (
        <DatabaseConfigError error={error} reset={reset} />
      ) : (
        <GenericError error={error} reset={reset} />
      )}
    </div>
  )
}
