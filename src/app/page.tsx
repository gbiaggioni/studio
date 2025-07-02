
import { getQRCodes, type QRCodeEntry } from '@/lib/db';
import { QRCodeForm } from '@/components/QRCodeForm';
import { QRCodeCard } from '@/components/QRCodeCard';
import { DeleteAllButton } from '@/components/DeleteAllButton';
import { Separator } from '@/components/ui/separator';
import { QrCode, AlertTriangle } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

export const dynamic = 'force-dynamic';

export default async function Home() {
  let qrCodes: QRCodeEntry[] = [];
  let dbError: string | null = null;

  try {
    qrCodes = await getQRCodes();
  } catch (error) {
    // Log the actual error to the server console for debugging
    console.error("[QREASY_DB_ERROR]", error);
    if (error instanceof Error) {
      dbError = error.message;
    } else {
      dbError = "Ocurrió un error desconocido al conectar con la base de datos.";
    }
  }

  const baseUrl = process.env.NEXT_PUBLIC_BASE_URL || 'https://qr.esquel.ar';

  return (
    <div className="min-h-screen flex flex-col items-center p-4 md:p-8 bg-gradient-to-br from-background to-secondary/30">
      <header className="mb-10 text-center">
        <div className="flex items-center justify-center mb-2">
          <QrCode size={48} className="text-primary mr-3" />
          <h1 className="text-5xl font-headline font-bold text-primary">QREasy</h1>
        </div>
        <p className="text-muted-foreground text-lg">
          Crea, gestiona y comparte tus códigos QR con facilidad.
        </p>
      </header>

      <main className="w-full max-w-5xl flex flex-col items-center space-y-12">
        
        {dbError && (
          <Alert variant="destructive" className="w-full">
            <AlertTriangle className="h-4 w-4" />
            <AlertTitle>Error de Conexión con la Base de Datos</AlertTitle>
            <AlertDescription>
              No se pudo establecer conexión con la base de datos. Si estás en un entorno de desarrollo, esto es esperado si no has configurado un archivo <code className="font-mono text-xs bg-muted px-1 rounded-md">.env.local</code>. En producción, revisa tus variables de entorno y la configuración del servidor.
              <p className="font-mono text-xs mt-2 bg-destructive/20 p-2 rounded-md">{dbError}</p>
            </AlertDescription>
          </Alert>
        )}

        <section className="w-full flex justify-center" aria-labelledby="create-qr-heading">
           <h2 id="create-qr-heading" className="sr-only">Crear Código QR</h2>
          <QRCodeForm />
        </section>
        
        <Separator className="my-8" />

        <section className="w-full" aria-labelledby="qr-codes-list-heading">
          <div className="flex justify-between items-center mb-6">
            <h2 id="qr-codes-list-heading" className="text-3xl font-headline font-semibold text-foreground">Tus Códigos QR</h2>
            {qrCodes.length > 0 && <DeleteAllButton />}
          </div>

          {dbError ? (
             <p className="text-center text-muted-foreground text-lg py-10">
              No se pueden cargar los códigos QR debido al error de conexión.
            </p>
          ) : qrCodes.length === 0 ? (
            <p className="text-center text-muted-foreground text-lg py-10">
              Aún no hay códigos QR. ¡Agrega uno usando el formulario de arriba!
            </p>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {qrCodes.map((qr) => (
                <QRCodeCard key={qr.id_db} qrCode={qr} baseUrl={baseUrl} />
              ))}
            </div>
          )}
        </section>
      </main>
      <footer className="mt-16 text-center text-muted-foreground">
        <p>&copy; {new Date().getFullYear()} QREasy. Todos los derechos reservados.</p>
      </footer>
    </div>
  );
}
