
"use client";

import React, { useState, useEffect, useTransition } from 'react';
import QRCode from 'qrcode.react';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from "@/components/ui/alert-dialog";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from "@/components/ui/dialog";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Printer, Trash2, Copy, Pencil } from 'lucide-react';
import { QRCodeFormSchema, type QRCodeFormValues } from '@/lib/schemas';
import { deleteQRCodeAction, updateQRCodeAction } from '@/app/actions';
import { useToast } from '@/hooks/use-toast';
import type { QRCodeEntry } from '@/lib/db';

interface QRCodeCardProps {
  qrCode: QRCodeEntry;
  baseUrl: string;
}

export function QRCodeCard({ qrCode, baseUrl }: QRCodeCardProps) {
  const { toast } = useToast();
  const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
  const [isPending, startTransition] = useTransition();

  const shortUrl = `${baseUrl}/r/${qrCode.short_id}`;
  const cardPrintId = `qr-card-print-${qrCode.id_db}`;

  const form = useForm<QRCodeFormValues>({
    resolver: zodResolver(QRCodeFormSchema),
    defaultValues: {
      label: qrCode.label,
      url_destino: qrCode.url_destino,
    },
  });

  useEffect(() => {
    if (isEditDialogOpen) {
      form.reset({
        label: qrCode.label,
        url_destino: qrCode.url_destino,
      });
    }
  }, [isEditDialogOpen, qrCode, form]);

  const onEditSubmit = async (data: QRCodeFormValues) => {
    const formData = new FormData();
    formData.append('label', data.label);
    formData.append('url_destino', data.url_destino);

    startTransition(async () => {
      const result = await updateQRCodeAction(qrCode.id_db, null, formData);
      if (result.success) {
        toast({
          title: "¡Éxito!",
          description: result.message,
        });
        setIsEditDialogOpen(false);
      } else {
        if (result.errors) {
          (Object.keys(result.errors) as Array<keyof QRCodeFormValues>).forEach((key) => {
            const fieldErrors = result.errors![key];
            if (fieldErrors && fieldErrors.length > 0) {
              form.setError(key, { type: 'server', message: fieldErrors.join(', ') });
            }
          });
        }
        toast({
          title: "Error",
          description: result.message || "Ocurrió un error inesperado.",
          variant: "destructive",
        });
      }
    });
  };

  const handleDelete = async () => {
    const result = await deleteQRCodeAction(qrCode.id_db);
    if (result.success) {
      toast({
        title: "¡Éxito!",
        description: result.message,
      });
    } else {
      toast({
        title: "Error",
        description: result.message || "Error al eliminar el código QR.",
        variant: "destructive",
      });
    }
  };

  const handlePrint = () => {
    const printContent = document.getElementById(cardPrintId);
    if (printContent) {
      const newWindow = window.open('', '_blank', 'height=800,width=600');
      if (newWindow) {
        newWindow.document.write(`
          <html>
            <head>
              <title>Imprimir Código QR</title>
              <style>
                @media print {
                  @page { size: A4; margin: 0; }
                  body { margin: 2cm; }
                }
                body { 
                  font-family: "PT Sans", sans-serif; 
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  min-height: 100vh;
                }
                .qr-print-container {
                  width: 100%;
                  max-width: 17cm; /* A4 width (21cm) minus margins (2cm*2) */
                  text-align: center;
                  display: flex;
                  flex-direction: column;
                  align-items: center;
                }
                .qr-print-container svg {
                  width: 100%;
                  height: auto;
                  margin-bottom: 1.5cm;
                }
                .qr-print-container h2 {
                  font-size: 24pt;
                  font-weight: bold;
                  margin-bottom: 1cm;
                }
                .qr-print-container p {
                  font-size: 12pt;
                  word-break: break-all;
                }
              </style>
            </head>
            <body>
              <div class="qr-print-container">
                ${printContent.innerHTML}
              </div>
            </body>
          </html>
        `);
        newWindow.document.close();
        setTimeout(() => {
          newWindow.print();
          newWindow.close();
        }, 250);
      }
    }
  };
  
  const handleCopyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text).then(() => {
      toast({ title: "¡Copiado!", description: "URL copiada al portapapeles." });
    }).catch(err => {
      toast({ title: "Error", description: "Error al copiar la URL.", variant: "destructive" });
    });
  };

  return (
    <Card className="w-full max-w-sm shadow-lg hover:shadow-xl transition-shadow duration-300" aria-labelledby={`card-title-${qrCode.id_db}`}>
      <CardHeader>
        <CardTitle id={`card-title-${qrCode.id_db}`} className="text-xl font-headline truncate" title={qrCode.label}>{qrCode.label}</CardTitle>
        <CardDescription>Creado: {new Date(qrCode.created_at).toLocaleDateString()}</CardDescription>
      </CardHeader>
      <CardContent className="flex flex-col items-center space-y-3">
        <div id={cardPrintId} className="qr-container bg-card p-3 rounded-md border border-border">
          <h2 className="text-lg font-semibold text-center text-foreground mb-2 md:hidden print:block">{qrCode.label}</h2>
          <QRCode value={shortUrl} size={160} level="H" renderAs="svg" />
          <p className="text-xs text-muted-foreground text-center mt-2 md:hidden print:block">Escanea para visitar: {qrCode.url_destino}</p>
        </div>
        
        <div className="text-sm text-center w-full space-y-1">
          <p className="font-medium text-foreground">Destino:</p>
          <p className="text-muted-foreground truncate hover:text-clip hover:whitespace-normal" title={qrCode.url_destino}>
            <a href={qrCode.url_destino} target="_blank" rel="noopener noreferrer" className="hover:underline break-all">{qrCode.url_destino}</a>
          </p>
        </div>
        <div className="text-sm text-center w-full space-y-1">
          <p className="font-medium text-foreground">URL Corta:</p>
          <div className="flex items-center justify-center space-x-2">
            <a href={shortUrl} target="_blank" rel="noopener noreferrer" className="text-primary hover:underline break-all" title={shortUrl}>{shortUrl}</a>
            <Button variant="ghost" size="icon" onClick={() => handleCopyToClipboard(shortUrl)} aria-label="Copiar URL corta">
              <Copy className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </CardContent>
      <CardFooter className="grid grid-cols-2 lg:grid-cols-3 gap-2">
        <Button variant="outline" onClick={handlePrint} aria-label={`Imprimir código QR para ${qrCode.label}`} className="lg:col-span-1">
          <Printer className="mr-2 h-4 w-4" /> Imprimir
        </Button>

        <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
          <DialogTrigger asChild>
            <Button variant="outline" className="lg:col-span-1">
              <Pencil className="mr-2 h-4 w-4" /> Editar
            </Button>
          </DialogTrigger>
          <DialogContent className="sm:max-w-[425px]">
            <DialogHeader>
              <DialogTitle>Editar Código QR</DialogTitle>
              <DialogDescription>
                Modifica los detalles a continuación. El código QR visual no cambiará.
              </DialogDescription>
            </DialogHeader>
            <Form {...form}>
              <form onSubmit={form.handleSubmit(onEditSubmit)} className="space-y-4 pt-4">
                <FormField
                  control={form.control}
                  name="label"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Nombre</FormLabel>
                      <FormControl>
                        <Input placeholder="Ej: Mi Sitio Web" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="url_destino"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>URL de Destino</FormLabel>
                      <FormControl>
                        <Input type="url" placeholder="https://ejemplo.com" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <DialogFooter>
                  <Button type="submit" disabled={isPending}>
                    {isPending ? "Guardando..." : "Guardar Cambios"}
                  </Button>
                </DialogFooter>
              </form>
            </Form>
          </DialogContent>
        </Dialog>
        
        <AlertDialog>
          <AlertDialogTrigger asChild>
            <Button variant="destructive" aria-label={`Eliminar código QR para ${qrCode.label}`} className="col-span-2 lg:col-span-1">
              <Trash2 className="mr-2 h-4 w-4" /> Eliminar
            </Button>
          </AlertDialogTrigger>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
              <AlertDialogDescription>
                Esta acción no se puede deshacer. Esto eliminará permanentemente el código QR para "{qrCode.label}".
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>Cancelar</AlertDialogCancel>
              <AlertDialogAction onClick={handleDelete} className="bg-destructive hover:bg-destructive/90">
                Eliminar
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      </CardFooter>
    </Card>
  );
}
