'use client' // Error components must be Client Components

import { useEffect } from 'react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { AlertTriangle } from 'lucide-react'

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string }
  reset: () => void
}) {
  useEffect(() => {
    // Log the error to the console, which will be captured by PM2 logs
    console.error("[GLOBAL_ERROR_BOUNDARY]", error)
  }, [error])

  return (
    <div className="min-h-screen flex flex-col items-center justify-center p-4 bg-gradient-to-br from-background to-secondary/30">
      <Card className="w-full max-w-lg text-center shadow-xl border-destructive/50">
          <CardHeader>
            <CardTitle className="flex items-center justify-center text-2xl font-headline text-destructive">
              <AlertTriangle className="mr-2 h-6 w-6" />
              Ocurrió un Error Inesperado
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            <p className="text-muted-foreground">
              Lo sentimos, algo salió mal en el servidor. Hemos registrado el error automáticamente para que nuestro equipo pueda revisarlo.
            </p>
            <Button
              onClick={() => reset()}
              variant="outline"
            >
              Intentar de nuevo
            </Button>
          </CardContent>
        </Card>
    </div>
  )
}
