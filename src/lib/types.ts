
export interface QRCodeEntry {
  id_db: string; // Unique database ID
  label: string;
  url_destino: string; // Destination URL
  short_id: string; // Unique short ID for the redirect URL
  created_at: Date;
}

// QRCodeFormSchema type is now inferred and exported from @/lib/schemas.ts as QRCodeFormValues

