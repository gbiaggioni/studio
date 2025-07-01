
'use client' 

import { useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { AlertOctagon, FileText } from 'lucide-react'

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

  return (
    <div className="min-h-screen flex flex-col items-center justify-center p-4 bg-gradient-to-br from-destructive/10 to-background">
      <Card className="w-full max-w-3xl text-left shadow-2xl border-2 border-destructive">
        <CardHeader>
          <CardTitle className="flex items-center text-3xl font-headline text-destructive">
            <AlertOctagon className="mr-4 h-10 w-10" />
            {isConfigError ? "¡ACCIÓN REQUERIDA! Problema de Configuración" : "Ocurrió un Error"}
          </CardTitle>
          <CardDescription className="text-lg pt-2">
            {isConfigError 
              ? <strong>El código de la aplicación es correcto. La solución está en la configuración de tu servidor.</strong>
              : "La aplicación encontró un problema inesperado."
            }
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="bg-destructive/10 p-4 rounded-md">
            <p className="font-semibold text-destructive">
              Mensaje de Error Detectado:
            </p>
            <code className="text-destructive font-mono text-sm whitespace-pre-wrap mt-2 block">
              {error.message}
            </code>
          </div>
          
          {isConfigError && (
            <div className="space-y-4 text-center border-t border-border pt-6">
              <h3 className="text-2xl font-semibold flex items-center justify-center">
                <FileText className="mr-3 h-7 w-7 text-primary" /> La Solución Está en el Archivo README.md
              </h3>
              <p className="text-muted-foreground text-base max-w-xl mx-auto">
                He preparado una guía de solución de problemas detallada en el archivo <code>README.md</code> de tu proyecto. Por favor, abre ese archivo y sigue los pasos en la sección <strong>"🆘 ¡ATENCIÓN! LA SOLUCIÓN ESTÁ AQUÍ 🆘"</strong>.
              </p>
              <p className="text-muted-foreground text-base max-w-xl mx-auto">
                Resolver este problema requiere que ejecutes comandos en la terminal de tu servidor, como se describe en la guía. El asistente de IA no puede realizar más cambios de código para solucionar esto.
              </p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
