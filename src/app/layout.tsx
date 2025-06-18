
import type { Metadata } from 'next';
import { PT_Sans } from 'next/font/google';
import './globals.css';
import { Toaster } from "@/components/ui/toaster";

const ptSans = PT_Sans({
  subsets: ['latin', 'cyrillic'],
  weight: ['400', '700'],
  style: ['normal', 'italic'],
  variable: '--font-pt-sans',
});

export const metadata: Metadata = {
  title: 'QREasy - Gestor de Códigos QR',
  description: 'Crea, gestiona y comparte tus códigos QR fácilmente.',
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="es" className={`${ptSans.variable} antialiased`}>
      <head />
      <body className="font-body">
        {children}
        <Toaster />
      </body>
    </html>
  );
}
