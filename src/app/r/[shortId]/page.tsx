import { redirect, notFound } from 'next/navigation';
import { getQRCodeByShortId } from '@/app/actions';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Link from 'next/link';
import { Button } from '@/components/ui/button';

interface RedirectPageProps {
  params: {
    shortId: string;
  };
}

export default async function RedirectPage({ params }: RedirectPageProps) {
  const { shortId } = params;

  if (!shortId) {
    notFound();
  }

  const qrCode = await getQRCodeByShortId(shortId);

  if (qrCode && qrCode.url_destino) {
    redirect(qrCode.url_destino);
  } else {
    // If not found, render a "not found" message within the app's styling.
    return (
      <div className="min-h-screen flex flex-col items-center justify-center p-4 bg-gradient-to-br from-background to-secondary/30">
        <Card className="w-full max-w-md text-center shadow-xl">
          <CardHeader>
            <CardTitle className="text-2xl font-headline text-destructive">QR Code Not Found</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <p className="text-muted-foreground">
              The QR code you scanned or the link you followed ({params.shortId}) is invalid or has expired.
            </p>
            <Button asChild variant="outline">
              <Link href="/">Go to Homepage</Link>
            </Button>
          </CardContent>
        </Card>
      </div>
    );
  }
}
