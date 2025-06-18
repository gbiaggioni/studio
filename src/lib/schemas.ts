
import { z } from 'zod';

export const QRCodeFormSchema = z.object({
  label: z.string().min(1, { message: "El nombre no puede estar vacío." }).max(100, { message: "El nombre no puede exceder los 100 caracteres."}),
  url_destino: z.string().url({ message: "Por favor, ingrese una URL válida." }),
});

export type QRCodeFormValues = z.infer<typeof QRCodeFormSchema>;
