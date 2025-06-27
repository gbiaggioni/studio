import mysql from 'mysql2/promise';
import type { RowDataPacket, ResultSetHeader } from 'mysql2/promise';
import type { QRCodeEntry } from './types';
import dotenv from 'dotenv';

dotenv.config();

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
      if (typeof window === 'undefined') {
        console.error("FATAL: Database environment variables are not configured.");
      }
      return {
        // @ts-ignore
        execute: () => Promise.reject(new Error("Database not configured."))
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
            console.error("Error checking short_id uniqueness:", error);
            throw new Error("Could not connect to the database to generate a unique ID.");
        }
        attempts++;
    }

    if (!isUnique) {
        throw new Error("Could not generate a unique ID after multiple attempts.");
    }

    return result;
}


export async function getQRCodes(): Promise<QRCodeEntry[]> {
    const db = getPool();
    const [rows] = await db.execute<RowDataPacket[]>('SELECT * FROM qr_codes ORDER BY created_at DESC');
    return rows as QRCodeEntry[];
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
    const db = getPool();
    const [rows] = await db.execute<RowDataPacket[]>('SELECT * FROM qr_codes WHERE short_id = ?', [short_id]);
    if (rows.length > 0) {
        return rows[0] as QRCodeEntry;
    }
    return undefined;
}
