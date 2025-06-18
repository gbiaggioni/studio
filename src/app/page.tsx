
import { getQRCodes } from '@/lib/db';
import { QRCodeForm } from '@/components/QRCodeForm';
import { QRCodeCard } from '@/components/QRCodeCard';
import { DeleteAllButton } from '@/components/DeleteAllButton';
import { Separator } from '@/components/ui/separator';
import { QrCode } from 'lucide-react';

export default async function Home() {
  const qrCodes = await getQRCodes();
  const baseUrl = process.env.NEXT_PUBLIC_BASE_URL || 'http://localhost:9002';

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

          {qrCodes.length === 0 ? (
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
