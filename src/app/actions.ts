"use server";

import { revalidatePath } from 'next/cache';
import { z } from 'zod';
import { addQRCodeDB, deleteQRCodeDB, deleteAllQRCodesDB, getQRCodeByShortIdDB } from '@/lib/db';
import type { QRCodeEntry } from '@/lib/types';

const QRCodeSchema = z.object({
  label: z.string().min(1, { message: "Label cannot be empty." }).max(100, { message: "Label too long."}),
  url_destino: z.string().url({ message: "Invalid URL format." }),
});

export async function addQRCodeAction(prevState: any, formData: FormData) {
  const validatedFields = QRCodeSchema.safeParse({
    label: formData.get('label'),
    url_destino: formData.get('url_destino'),
  });

  if (!validatedFields.success) {
    return {
      message: "Validation failed.",
      errors: validatedFields.error.flatten().fieldErrors,
      success: false,
    };
  }

  try {
    await addQRCodeDB(validatedFields.data.label, validatedFields.data.url_destino);
    revalidatePath('/');
    return { message: "QR Code added successfully.", success: true, errors: {} };
  } catch (error) {
    return { message: "Failed to add QR Code.", success: false, errors: {} };
  }
}

export async function deleteQRCodeAction(id_db: string) {
  try {
    const success = await deleteQRCodeDB(id_db);
    if (success) {
      revalidatePath('/');
      return { message: "QR Code deleted successfully.", success: true };
    }
    return { message: "Failed to delete QR Code or QR Code not found.", success: false };
  } catch (error) {
    return { message: "Error deleting QR Code.", success: false };
  }
}

export async function deleteAllQRCodesAction() {
  try {
    await deleteAllQRCodesDB();
    revalidatePath('/');
    return { message: "All QR Codes deleted successfully.", success: true };
  } catch (error) {
    return { message: "Error deleting all QR Codes.", success: false };
  }
}

export async function getQRCodeByShortId(shortId: string): Promise<QRCodeEntry | null> {
  try {
    const qrCode = await getQRCodeByShortIdDB(shortId);
    return qrCode || null;
  } catch (error) {
    console.error("Error fetching QR code by short ID:", error);
    return null;
  }
}
