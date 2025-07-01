import mysql from 'mysql2/promise';
import type { RowDataPacket, ResultSetHeader } from 'mysql2/promise';
import type { QRCodeEntry } from './types';

// Singleton pool
let pool: mysql.Pool | null = null;

/**
 * Obtiene el pool de conexiones. Si no existe, lo crea.
 * Lanza un error si la configuración de la base de datos es incompleta.
 */
function getPool(): mysql.Pool {
    if (pool) return pool;

    // Log environment variables for debugging purposes inside the container.
    // This will help diagnose if the .env.local file is being loaded correctly.
    console.log("--- [QREASY_DOCKER_DEBUG] Imprimiendo variables de entorno ---");
    console.log(`DB_HOST: ${process.env.DB_HOST ? 'Cargado' : 'NO CARGADO'}`);
    console.log(`DB_USER: ${process.env.DB_USER ? 'Cargado' : 'NO CARGADO'}`);
    console.log(`DB_PASSWORD: ${process.env.DB_PASSWORD ? 'Cargado (longitud: ' + process.env.DB_PASSWORD.length + ')' : 'NO CARGADO'}`);
    console.log(`DB_NAME: ${process.env.DB_NAME ? 'Cargado' : 'NO CARGADO'}`);
    console.log(`NEXT_PUBLIC_BASE_URL: ${process.env.NEXT_PUBLIC_BASE_URL ? 'Cargado' : 'NO CARGADO'}`);
    console.log("----------------------------------------------------------");

    const missingVars = [];
    if (!process.env.DB_HOST) missingVars.push('DB_HOST');
    if (!process.env.DB_USER) missingVars.push('DB_USER');
    if (!process.env.DB_PASSWORD) missingVars.push('DB_PASSWORD');
    if (!process.env.DB_NAME) missingVars.push('DB_NAME');

    if (missingVars.length > 0) {
        const errorMsg = `La configuración de la base de datos es incompleta. Falta(n) la(s) siguiente(s) variable(s) de entorno: ${missingVars.join(', ')}. Por favor, revisa tus logs de contenedor y el archivo .env.local.`;
        // Log the error before throwing, so it appears in container logs
        console.error(`[QREASY_DB_ERROR] ${errorMsg}`);
        throw new Error(errorMsg);
    }

    const dbConfig = {
        host: process.env.DB_HOST,
        user: process.env.DB_USER,
        password: process.env.DB_PASSWORD,
        database: process.env.DB_NAME,
        waitForConnections: true,
        connectionLimit: 10,
        queueLimit: 0,
    };
    
    pool = mysql.createPool(dbConfig);
    return pool;
}

/**
 * Obtiene todos los códigos QR. Es una operación crítica para la página principal.
 * Lanza un error si la consulta a la base de datos falla, para ser capturado por los límites de error de Next.js.
 */
export async function getQRCodes(): Promise<QRCodeEntry[]> {
    try {
        const db = getPool();
        const [rows] = await db.execute<RowDataPacket[]>('SELECT * FROM qr_codes ORDER BY created_at DESC');
        return rows as QRCodeEntry[];
    } catch (error) {
        const errorMessage = error instanceof Error ? error.message : "Unknown database error";
        console.error(`[QREASY_DB_ERROR] Fallo al obtener los códigos QR. ¿Está la base de datos configurada correctamente? Error: ${errorMessage}`);
        // Re-lanza el error para que Next.js pueda mostrar la página de error correcta y registrar el error completo.
        throw new Error(errorMessage);
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

    