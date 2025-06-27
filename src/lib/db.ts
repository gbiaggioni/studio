import type { QRCodeEntry } from './types';

// In-memory store for QR codes
let qrCodes: QRCodeEntry[] = [];

// Helper to generate a short ID (simple version for mock)
export function generateShortId(length: number = 6): string {
  const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let result = '';
  let attempts = 0;
  const maxAttempts = 100; // Prevent infinite loop in a very dense scenario

  while(attempts < maxAttempts) {
    result = '';
    for (let i = 0; i < length; i++) {
      result += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    if (!qrCodes.find(qr => qr.short_id === result)) {
      return result;
    }
    attempts++;
  }
  // Fallback for extremely rare collision case in mock
  return Date.now().toString(36) + Math.random().toString(36).substring(2, 5);
}

export async function getQRCodes(): Promise<QRCodeEntry[]> {
  // Simulate DB latency
  await new Promise(resolve => setTimeout(resolve, 100));
  return [...qrCodes].sort((a, b) => b.created_at.getTime() - a.created_at.getTime());
}

export async function addQRCodeDB(label: string, url_destino: string): Promise<QRCodeEntry> {
  await new Promise(resolve => setTimeout(resolve, 100));
  const newQRCode: QRCodeEntry = {
    id_db: crypto.randomUUID(),
    label,
    url_destino,
    short_id: generateShortId(),
    created_at: new Date(),
  };
  qrCodes.push(newQRCode);
  return newQRCode;
}

export async function updateQRCodeDB(id_db: string, label: string, url_destino: string): Promise<QRCodeEntry | undefined> {
  await new Promise(resolve => setTimeout(resolve, 100));
  const qrCodeIndex = qrCodes.findIndex(qr => qr.id_db === id_db);
  if (qrCodeIndex > -1) {
    qrCodes[qrCodeIndex] = {
      ...qrCodes[qrCodeIndex],
      label,
      url_destino,
    };
    return qrCodes[qrCodeIndex];
  }
  return undefined;
}

export async function deleteQRCodeDB(id_db: string): Promise<boolean> {
  await new Promise(resolve => setTimeout(resolve, 100));
  const initialLength = qrCodes.length;
  qrCodes = qrCodes.filter(qr => qr.id_db !== id_db);
  return qrCodes.length < initialLength;
}

export async function deleteAllQRCodesDB(): Promise<boolean> {
  await new Promise(resolve => setTimeout(resolve, 100));
  qrCodes = [];
  return true;
}

export async function getQRCodeByShortIdDB(short_id: string): Promise<QRCodeEntry | undefined> {
  await new Promise(resolve => setTimeout(resolve, 100));
  return qrCodes.find(qr => qr.short_id === short_id);
}

// Initialize with some dummy data for easier testing
if (process.env.NODE_ENV === 'development' && qrCodes.length === 0) {
  addQRCodeDB('Google', 'https://google.com');
  addQRCodeDB('Firebase', 'https://firebase.google.com');
}
