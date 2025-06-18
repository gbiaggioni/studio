"use client";

import React, { useState, useEffect } from 'react';
import QRCode from 'qrcode.react';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from "@/components/ui/alert-dialog";
import { Printer, Trash2, Copy } from 'lucide-react';
import type { QRCodeEntry } from '@/lib/types';
import { deleteQRCodeAction } from '@/app/actions';
import { useToast } from '@/hooks/use-toast';

interface QRCodeCardProps {
  qrCode: QRCodeEntry;
  baseUrl: string;
}

export function QRCodeCard({ qrCode, baseUrl }: QRCodeCardProps) {
  const { toast } = useToast();
  const [isMounted, setIsMounted] = useState(false);
  const shortUrl = `${baseUrl}/r/${qrCode.short_id}`;
  const cardPrintId = `qr-card-print-${qrCode.id_db}`;

  useEffect(() => {
    setIsMounted(true);
  }, []);

  const handleDelete = async () => {
    const result = await deleteQRCodeAction(qrCode.id_db);
    if (result.success) {
      toast({
        title: "Success!",
        description: result.message,
      });
    } else {
      toast({
        title: "Error",
        description: result.message || "Failed to delete QR code.",
        variant: "destructive",
      });
    }
  };

  const handlePrint = () => {
    const printContent = document.getElementById(cardPrintId);
    if (printContent) {
      const newWindow = window.open('', '_blank', 'height=600,width=800');
      if (newWindow) {
        newWindow.document.write('<html><head><title>Print QR Code</title>');
        newWindow.document.write('<style>body { font-family: "PT Sans", sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; } .qr-container { text-align: center; } canvas { margin-bottom: 1rem; } h2 { margin-bottom: 0.5rem; font-size: 1.5rem; } p { font-size: 1rem; word-break: break-all; }</style>');
        newWindow.document.write('</head><body>');
        newWindow.document.write(printContent.innerHTML);
        newWindow.document.write('</body></html>');
        newWindow.document.close();
        // Wait for content to load before printing
        setTimeout(() => {
          newWindow.print();
          newWindow.close();
        }, 250); 
      }
    }
  };
  
  const handleCopyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text).then(() => {
      toast({ title: "Copied!", description: "URL copied to clipboard." });
    }).catch(err => {
      toast({ title: "Error", description: "Failed to copy URL.", variant: "destructive" });
    });
  };

  if (!isMounted) {
    // Render placeholder or null during SSR/hydration phase to avoid mismatch with qrcode.react
    return (
      <Card className="w-full max-w-sm shadow-lg animate-pulse">
        <CardHeader>
          <div className="h-6 bg-muted rounded w-3/4"></div>
        </CardHeader>
        <CardContent className="flex flex-col items-center space-y-4">
          <div className="bg-muted rounded-md p-4">
            <div className="w-40 h-40 bg-muted-foreground/20 rounded"></div>
          </div>
          <div className="h-4 bg-muted rounded w-full"></div>
          <div className="h-4 bg-muted rounded w-5/6"></div>
        </CardContent>
        <CardFooter className="flex justify-between">
          <div className="h-10 bg-muted rounded w-20"></div>
          <div className="h-10 bg-muted rounded w-20"></div>
        </CardFooter>
      </Card>
    );
  }

  return (
    <Card className="w-full max-w-sm shadow-lg hover:shadow-xl transition-shadow duration-300" aria-labelledby={`card-title-${qrCode.id_db}`}>
      <CardHeader>
        <CardTitle id={`card-title-${qrCode.id_db}`} className="text-xl font-headline truncate" title={qrCode.label}>{qrCode.label}</CardTitle>
        <CardDescription>Created: {new Date(qrCode.created_at).toLocaleDateString()}</CardDescription>
      </CardHeader>
      <CardContent className="flex flex-col items-center space-y-3">
        <div id={cardPrintId} className="qr-container bg-card p-3 rounded-md border border-border">
          <h2 className="text-lg font-semibold text-center text-foreground mb-2 md:hidden print:block">{qrCode.label}</h2>
          <QRCode value={shortUrl} size={160} level="H" renderAs="canvas" />
          <p className="text-xs text-muted-foreground text-center mt-2 md:hidden print:block">Scan to visit: {qrCode.url_destino}</p>
        </div>
        
        <div className="text-sm text-center w-full space-y-1">
          <p className="font-medium text-foreground">Destination:</p>
          <p className="text-muted-foreground truncate hover:text-clip hover:whitespace-normal" title={qrCode.url_destino}>
            <a href={qrCode.url_destino} target="_blank" rel="noopener noreferrer" className="hover:underline break-all">{qrCode.url_destino}</a>
          </p>
        </div>
        <div className="text-sm text-center w-full space-y-1">
          <p className="font-medium text-foreground">Short URL:</p>
          <div className="flex items-center justify-center space-x-2">
            <a href={shortUrl} target="_blank" rel="noopener noreferrer" className="text-primary hover:underline break-all" title={shortUrl}>{shortUrl}</a>
            <Button variant="ghost" size="icon" onClick={() => handleCopyToClipboard(shortUrl)} aria-label="Copy short URL">
              <Copy className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </CardContent>
      <CardFooter className="flex justify-between">
        <Button variant="outline" onClick={handlePrint} aria-label={`Print QR code for ${qrCode.label}`}>
          <Printer className="mr-2 h-4 w-4" /> Print
        </Button>
        <AlertDialog>
          <AlertDialogTrigger asChild>
            <Button variant="destructive" aria-label={`Delete QR code for ${qrCode.label}`}>
              <Trash2 className="mr-2 h-4 w-4" /> Delete
            </Button>
          </AlertDialogTrigger>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>Are you sure?</AlertDialogTitle>
              <AlertDialogDescription>
                This action cannot be undone. This will permanently delete the QR code for "{qrCode.label}".
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>Cancel</AlertDialogCancel>
              <AlertDialogAction onClick={handleDelete} className="bg-destructive hover:bg-destructive/90">
                Delete
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      </CardFooter>
    </Card>
  );
}
