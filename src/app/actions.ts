
"use server";

import { revalidatePath } from 'next/cache';
import { QRCodeFormSchema } from '@/lib/schemas'; // Updated import
import { addQRCodeDB, deleteQRCodeDB, deleteAllQRCodesDB, getQRCodeByShortIdDB, updateQRCodeDB } from '@/lib/db';
import type { QRCodeEntry } from '@/lib/types';

export async function addQRCodeAction(prevState: any, formData: FormData) {
  const validatedFields = QRCodeFormSchema.safeParse({
    label: formData.get('label'),
    url_destino: formData.get('url_destino'),
  });

  if (!validatedFields.success) {
    return {
      message: "La validación falló.",
      errors: validatedFields.error.flatten().fieldErrors,
      success: false,
    };
  }

  try {
    await addQRCodeDB(validatedFields.data.label, validatedFields.data.url_destino);
    revalidatePath('/');
    return { message: "Código QR agregado exitosamente.", success: true, errors: {} };
  } catch (error) {
    return { message: "Error al agregar el Código QR.", success: false, errors: {} };
  }
}

export async function updateQRCodeAction(id_db: string, prevState: any, formData: FormData) {
    const validatedFields = QRCodeFormSchema.safeParse({
    label: formData.get('label'),
    url_destino: formData.get('url_destino'),
  });

  if (!validatedFields.success) {
    return {
      message: "La validación falló.",
      errors: validatedFields.error.flatten().fieldErrors,
      success: false,
    };
  }

  try {
    const updatedQRCode = await updateQRCodeDB(id_db, validatedFields.data.label, validatedFields.data.url_destino);
    if (updatedQRCode) {
      revalidatePath('/');
      return { message: "Código QR actualizado exitosamente.", success: true, errors: {} };
    } else {
        return { message: "No se encontró el Código QR para actualizar.", success: false, errors: {} };
    }
  } catch (error) {
    return { message: "Error al actualizar el Código QR.", success: false, errors: {} };
  }
}


export async function deleteQRCodeAction(id_db: string) {
  try {
    const success = await deleteQRCodeDB(id_db);
    if (success) {
      revalidatePath('/');
      return { message: "Código QR eliminado exitosamente.", success: true };
    }
    return { message: "Error al eliminar el Código QR o Código QR no encontrado.", success: false };
  } catch (error) {
    return { message: "Error al eliminar el Código QR.", success: false };
  }
}

export async function deleteAllQRCodesAction() {
  try {
    await deleteAllQRCodesDB();
    revalidatePath('/');
    return { message: "Todos los Códigos QR eliminados exitosamente.", success: true };
  } catch (error) {
    return { message: "Error al eliminar todos los Códigos QR.", success: false };
  }
}

export async function getQRCodeByShortId(shortId: string): Promise<QRCodeEntry | null> {
  try {
    const qrCode = await getQRCodeByShortIdDB(shortId);
    return qrCode || null;
  } catch (error) {
    console.error("Error al obtener el código QR por ID corto:", error);
    return null;
  }
}
