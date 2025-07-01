// Este archivo es la fuente única de verdad para las interfaces de datos.
export interface QRCodeEntry {
  id_db: string;
  label: string;
  url_destino: string;
  short_id: string;
  created_at: Date;
}
