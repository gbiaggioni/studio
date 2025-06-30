
import mysql from 'mysql2/promise';
import type { RowDataPacket, ResultSetHeader } from 'mysql2/promise';
import type { QRCodeEntry } from './types';

const dbConfig = {
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
};

let pool: mysql.Pool;

function getPool(): mysql.Pool {
  if (!pool || pool.pool.ended) {
    if (!dbConfig.host || !dbConfig.user || !dbConfig.database) {
      // Return a mock pool that will reject promises. This will be handled by callers.
      return {
        // @ts-ignore
        execute: () => Promise.reject(new Error("La base de datos no está configurada. Por favor, revisa tu archivo .env.local."))
      }
    }
    pool = mysql.createPool(dbConfig);
  }
  return pool;
}

export async function generateShortId(length: number = 6): Promise<string> {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    let isUnique = false;
    let attempts = 0;
    const maxAttempts = 100;

    const db = getPool();

    while (!isUnique && attempts < maxAttempts) {
        result = '';
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        
        try {
            const [rows] = await db.execute<RowDataPacket[]>('SELECT short_id FROM qr_codes WHERE short_id = ?', [result]);
            if (rows.length === 0) {
                isUnique = true;
            }
        } catch (error) {
            // Re-throw the error to be caught by the server action.
            throw error;
        }
        attempts++;
    }

    if (!isUnique) {
        throw new Error("No se pudo generar un ID único después de múltiples intentos.");
    }

    return result;
}


export async function getQRCodes(): Promise<QRCodeEntry[]> {
    try {
        const db = getPool();
        const [rows] = await db.execute<RowDataPacket[]>('SELECT * FROM qr_codes ORDER BY created_at DESC');
        return rows as QRCodeEntry[];
    } catch (error) {
        const errorMessage = error instanceof Error ? error.message : "Unknown database error";
        console.error(`[QREASY_DB_ERROR] Failed to fetch QR codes. Is the database configured correctly? Error: ${errorMessage}`);
        // Return an empty array to allow the page to render gracefully.
        // The developer will see the error in the server logs.
        return [];
    }
}


export async function addQRCodeDB(label: string, url_destino: string): Promise<QRCodeEntry> {
    const db = getPool();
    const newQRCode: QRCodeEntry = {
        id_db: crypto.randomUUID(),
        label,
        url_destino,
        short_id: await generateShortId(),
        created_at: new Date(),
    };
    
    await db.execute(
        'INSERT INTO qr_codes (id_db, label, url_destino, short_id, created_at) VALUES (?, ?, ?, ?, ?)',
        [newQRCode.id_db, newQRCode.label, newQRCode.url_destino, newQRCode.short_id, newQRCode.created_at]
    );

    return newQRCode;
}


export async function updateQRCodeDB(id_db: string, label: string, url_destino: string): Promise<QRCodeEntry | undefined> {
    const db = getPool();
    const [result] = await db.execute<ResultSetHeader>(
        'UPDATE qr_codes SET label = ?, url_destino = ? WHERE id_db = ?',
        [label, url_destino, id_db]
    );

    if (result.affectedRows > 0) {
        const [rows] = await db.execute<RowDataPacket[]>('SELECT * FROM qr_codes WHERE id_db = ?', [id_db]);
        return (rows as QRCodeEntry[])[0];
    }
    return undefined;
}


export async function deleteQRCodeDB(id_db: string): Promise<boolean> {
    const db = getPool();
    const [result] = await db.execute<ResultSetHeader>('DELETE FROM qr_codes WHERE id_db = ?', [id_db]);
    return result.affectedRows > 0;
}


export async function deleteAllQRCodesDB(): Promise<boolean> {
    const db = getPool();
    await db.execute<ResultSetHeader>('TRUNCATE TABLE qr_codes');
    return true;
}


export async function getQRCodeByShortIdDB(short_id: string): Promise<QRCodeEntry | undefined> {
    try {
        const db = getPool();
        const [rows] = await db.execute<RowDataPacket[]>('SELECT * FROM qr_codes WHERE short_id = ?', [short_id]);
        if (rows.length > 0) {
            return rows[0] as QRCodeEntry;
        }
        return undefined;
    } catch (error) {
        const errorMessage = error instanceof Error ? error.message : "Unknown database error";
        console.error(`[QREASY_DB_ERROR] Failed to fetch QR code by shortId '${short_id}'. Is the database configured correctly? Error: ${errorMessage}`);
        // Return undefined to allow the redirect page to show a graceful "not found" message.
        return undefined;
    }
}
