"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { z } from "zod";
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
import type { QRCodeFormSchema as FormSchemaType } from "@/lib/types";

const QRCodeFormSchema = z.object({
  label: z.string().min(1, { message: "Label cannot be empty." }).max(100, { message: "Label cannot exceed 100 characters."}),
  url_destino: z.string().url({ message: "Please enter a valid URL." }),
});

export function QRCodeForm() {
  const { toast } = useToast();
  const [formError, setFormError] = React.useState<string | null>(null);
  
  const form = useForm<FormSchemaType>({
    resolver: zodResolver(QRCodeFormSchema),
    defaultValues: {
      label: "",
      url_destino: "",
    },
  });

  const onSubmit = async (data: FormSchemaType) => {
    setFormError(null);
    const formData = new FormData();
    formData.append('label', data.label);
    formData.append('url_destino', data.url_destino);

    const result = await addQRCodeAction(null, formData);

    if (result.success) {
      toast({
        title: "Success!",
        description: result.message,
      });
      form.reset();
    } else {
      setFormError(result.message);
      if (result.errors) {
        // Set form errors for individual fields if available
        (Object.keys(result.errors) as Array<keyof FormSchemaType>).forEach((key) => {
           const fieldErrors = result.errors![key];
           if (fieldErrors && fieldErrors.length > 0) {
            form.setError(key, { type: 'server', message: fieldErrors.join(', ') });
           }
        });
      }
      toast({
        title: "Error",
        description: result.message || "An unexpected error occurred.",
        variant: "destructive",
      });
    }
  };

  return (
    <Card className="w-full max-w-lg shadow-lg">
      <CardHeader>
        <CardTitle className="text-2xl font-headline text-center">Create New QR Code</CardTitle>
      </CardHeader>
      <CardContent>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
            <FormField
              control={form.control}
              name="label"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Label</FormLabel>
                  <FormControl>
                    <Input placeholder="e.g., My Website" {...field} aria-describedby="label-error" />
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
                  <FormLabel>Destination URL</FormLabel>
                  <FormControl>
                    <Input type="url" placeholder="https://example.com" {...field} aria-describedby="url-error" />
                  </FormControl>
                  <FormMessage id="url-error" />
                </FormItem>
              )}
            />
            {formError && <p className="text-sm font-medium text-destructive">{formError}</p>}
            <Button type="submit" className="w-full" disabled={form.formState.isSubmitting}>
              <PlusCircle className="mr-2 h-5 w-5" />
              {form.formState.isSubmitting ? "Adding..." : "Add QR Code"}
            </Button>
          </form>
        </Form>
      </CardContent>
    </Card>
  );
}
