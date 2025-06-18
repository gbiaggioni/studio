
"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { addQRCodeAction } from "@/app/actions";
import { useToast } from "@/hooks/use-toast";
import React from "react";
import { PlusCircle } from "lucide-react";
import { QRCodeFormSchema, type QRCodeFormValues } from "@/lib/schemas";

export function QRCodeForm() {
  const { toast } = useToast();
  const [formError, setFormError] = React.useState<string | null>(null);
  
  const form = useForm<QRCodeFormValues>({
    resolver: zodResolver(QRCodeFormSchema),
    defaultValues: {
      label: "",
      url_destino: "",
    },
  });

  const onSubmit = async (data: QRCodeFormValues) => {
    setFormError(null);
    const formData = new FormData();
    formData.append('label', data.label);
    formData.append('url_destino', data.url_destino);

    const result = await addQRCodeAction(null, formData);

    if (result.success) {
      toast({
        title: "¡Éxito!",
        description: result.message,
      });
      form.reset();
    } else {
      setFormError(result.message);
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
  };

  return (
    <Card className="w-full max-w-lg shadow-lg">
      <CardHeader>
        <CardTitle className="text-2xl font-headline text-center">Crear Nuevo Código QR</CardTitle>
      </CardHeader>
      <CardContent>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
            <FormField
              control={form.control}
              name="label"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Nombre</FormLabel>
                  <FormControl>
                    <Input placeholder="Ej: Mi Sitio Web" {...field} aria-describedby="label-error" />
                  </FormControl>
                  <FormMessage id="label-error" />
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
                    <Input type="url" placeholder="https://ejemplo.com" {...field} aria-describedby="url-error" />
                  </FormControl>
                  <FormMessage id="url-error" />
                </FormItem>
              )}
            />
            {formError && <p className="text-sm font-medium text-destructive">{formError}</p>}
            <Button type="submit" className="w-full" disabled={form.formState.isSubmitting}>
              <PlusCircle className="mr-2 h-5 w-5" />
              {form.formState.isSubmitting ? "Agregando..." : "Agregar Código QR"}
            </Button>
          </form>
        </Form>
      </CardContent>
    </Card>
  );
}
