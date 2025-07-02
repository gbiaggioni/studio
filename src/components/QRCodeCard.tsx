"use client";

import React from 'react';
import QRCode from 'qrcode.react';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Printer, Trash2 } from 'lucide-react';
import type { QRCodeEntry } from '@/lib/db';

interface QRCodeCardProps {
  qrCode: QRCodeEntry;
  baseUrl: string;
}

export function QRCodeCard({ qrCode, baseUrl }: QRCodeCardProps) {
  const shortUrl = `${baseUrl}/r/${qrCode.short_id}`;

  return (
    <Card className="w-full max-w-sm shadow-lg hover:shadow-xl transition-shadow duration-300" aria-labelledby={`card-title-${qrCode.id_db}`}>
      <CardHeader>
        <CardTitle id={`card-title-${qrCode.id_db}`} className="text-xl font-headline truncate" title={qrCode.label}>
          {qrCode.label}
        </CardTitle>
        <CardDescription>
          Creado: {new Date(qrCode.created_at).toLocaleDateString()}
        </CardDescription>
      </CardHeader>
      <CardContent className="flex flex-col items-center space-y-4">
        <div className="p-3 bg-white rounded-lg border">
          <QRCode value={shortUrl} size={160} level="H" renderAs="svg" />
        </div>
        
        <div className="text-sm text-center w-full space-y-1">
          <p className="font-medium text-foreground">Destino:</p>
          <p className="text-muted-foreground truncate" title={qrCode.url_destino}>
            <a href={qrCode.url_destino} target="_blank" rel="noopener noreferrer" className="hover:underline break-all">
              {qrCode.url_destino}
            </a>
          </p>
        </div>

        <div className="text-sm text-center w-full space-y-1">
          <p className="font-medium text-foreground">URL Corta:</p>
            <a href={shortUrl} target="_blank" rel="noopener noreferrer" className="text-primary hover:underline break-all" title={shortUrl}>
              {shortUrl}
            </a>
        </div>
      </CardContent>
      <CardFooter className="grid grid-cols-2 gap-2">
        <Button variant="outline" disabled>
          <Printer className="mr-2 h-4 w-4" /> Imprimir
        </Button>
        <Button variant="destructive" disabled>
          <Trash2 className="mr-2 h-4 w-4" /> Eliminar
        </Button>
      </CardFooter>
    </Card>
  );
}
