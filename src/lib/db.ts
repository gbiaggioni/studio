
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

// Singleton pool
let pool: mysql.Pool | null = null;

/**
 * Obtiene el pool de conexiones. Si no existe, lo crea.
 * Lanza un error si la configuración de la base de datos es incompleta.
 */
function getPool(): mysql.Pool {
    if (pool) return pool;

    if (!dbConfig.host || !dbConfig.user || !dbConfig.database) {
      // Este error será capturado por las funciones que llaman a getPool.
      throw new Error("La base de datos no está configurada. Por favor, revisa tus variables de entorno.");
    }
    
    pool = mysql.createPool(dbConfig);
    return pool;
}

/**
 * Obtiene todos los códigos QR. Es una lectura segura que devuelve un array vacío en caso de error.
 */
export async function getQRCodes(): Promise<QRCodeEntry[]> {
    try {
        const db = getPool();
        const [rows] = await db.execute<RowDataPacket[]>('SELECT * FROM qr_codes ORDER BY created_at DESC');
        return rows as QRCodeEntry[];
    } catch (error) {
        const errorMessage = error instanceof Error ? error.message : "Unknown database error";
        console.error(`[QREASY_DB_ERROR] Fallo al obtener los códigos QR. ¿Está la base de datos configurada correctamente? Error: ${errorMessage}`);
        return [];
    }
}

/**
 * Obtiene un código QR por su short_id. Es una lectura segura que devuelve null en caso de error.
 */
export async function getQRCodeByShortIdDB(short_id: string): Promise<QRCodeEntry | null> {
    try {
        const db = getPool();
        const [rows] = await db.execute<RowDataPacket[]>('SELECT * FROM qr_codes WHERE short_id = ?', [short_id]);
        return (rows[0] as QRCodeEntry) || null;
    } catch (error) {
        const errorMessage = error instanceof Error ? error.message : "Unknown database error";
        console.error(`[QREASY_DB_ERROR] Fallo al obtener el código QR por shortId '${short_id}'. Error: ${errorMessage}`);
        return null;
    }
}

/**
 * Genera un ID corto único. Es parte de una operación de escritura, por lo que lanza un error si falla.
 */
export async function generateShortId(length: number = 6): Promise<string> {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    let isUnique = false;
    let attempts = 0;
    const maxAttempts = 100;

    const db = getPool(); // Lanza error si no está configurada

    while (!isUnique && attempts < maxAttempts) {
        result = '';
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        
        const [rows] = await db.execute<RowDataPacket[]>('SELECT short_id FROM qr_codes WHERE short_id = ?', [result]);
        if (rows.length === 0) {
            isUnique = true;
        }
        attempts++;
    }

    if (!isUnique) {
        throw new Error("No se pudo generar un ID único después de múltiples intentos.");
    }

    return result;
}

/**
 * Añade un nuevo código QR. Es una operación de escritura que lanza un error si falla.
 */
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

/**
 * Actualiza un código QR. Es una operación de escritura que lanza un error si falla.
 */
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

/**
 * Elimina un código QR. Es una operación de escritura que lanza un error si falla.
 */
export async function deleteQRCodeDB(id_db: string): Promise<boolean> {
    const db = getPool();
    const [result] = await db.execute<ResultSetHeader>('DELETE FROM qr_codes WHERE id_db = ?', [id_db]);
    return result.affectedRows > 0;
}

/**
 * Elimina todos los códigos QR. Es una operación de escritura que lanza un error si falla.
 */
export async function deleteAllQRCodesDB(): Promise<boolean> {
    const db = getPool();
    await db.execute<ResultSetHeader>('TRUNCATE TABLE qr_codes');
    return true;
}

/**
 * Función de conveniencia para la página de redirección, que usa la función de lectura segura.
 */
export async function getQRCodeByShortId(shortId: string): Promise<QRCodeEntry | null> {
  return await getQRCodeByShortIdDB(shortId);
}
