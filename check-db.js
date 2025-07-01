// check-db.js
require('dotenv').config({ path: './.env.local' });
const mysql = require('mysql2/promise');

async function checkDatabaseConnection() {
  console.log('--- [QREASY_DB_CHECK] Intentando conectar a la base de datos ---');
  console.log('Usando las siguientes credenciales (la contraseña no se muestra):');
  console.log(`- Host: ${process.env.DB_HOST}`);
  console.log(`- Usuario: ${process.env.DB_USER}`);
  console.log(`- Base de Datos: ${process.env.DB_NAME}`);
  console.log('----------------------------------------------------');


  if (!process.env.DB_HOST || !process.env.DB_USER || !process.env.DB_PASSWORD || !process.env.DB_NAME) {
    console.error('\n\x1b[31m%s\x1b[0m', '¡ERROR! Faltan una o más variables de entorno en tu archivo .env.local.');
    console.error('Por favor, ejecuta el script "./configure-env.sh" para crearlo correctamente.');
    process.exit(1);
  }

  let connection;
  try {
    connection = await mysql.createConnection({
      host: process.env.DB_HOST,
      user: process.env.DB_USER,
      password: process.env.DB_PASSWORD,
      database: process.env.DB_NAME,
      connectTimeout: 10000 // 10 segundos de timeout
    });

    console.log('\n\x1b[32m%s\x1b[0m', '¡ÉXITO! La conexión a la base de datos se estableció correctamente.');
    console.log('Tu archivo .env.local está bien configurado y la base de datos es accesible.');

  } catch (error) {
    console.error('\n\x1b[31m%s\x1b[0m', '¡ERROR! No se pudo conectar a la base de datos.');
    console.error('Detalles del error:');

    // Provide user-friendly hints based on the error code
    if (error.code === 'ER_ACCESS_DENIED_ERROR') {
      console.error('- El usuario o la contraseña son incorrectos. Verifica las credenciales.');
    } else if (error.code === 'ENOTFOUND' || error.code === 'ECONNREFUSED') {
      console.error(`- No se pudo encontrar el servidor en la dirección '${process.env.DB_HOST}'.`);
      console.error('- Verifica que el host de la base de datos sea correcto.');
      console.error('- Si estás usando Docker, el host suele ser 172.17.0.1, no localhost o 127.0.0.1.');
    } else if (error.code === 'ER_BAD_DB_ERROR') {
        console.error(`- La base de datos '${process.env.DB_NAME}' no existe. Verifica el nombre.`);
    } else {
      console.error('- ' + error.message);
    }

    process.exit(1);

  } finally {
    if (connection) {
      await connection.end();
    }
  }
}

checkDatabaseConnection();
