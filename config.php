<?php
declare(strict_types=1);

/*
 * Configuración de conexión MySQL.
 * Recomendación: configura estas credenciales como variables de entorno
 * antes de publicar el sitio. No compartas este archivo públicamente.
 */
const DB_HOST = 'mysql-webjobs.alwaysdata.net';
const DB_NAME = 'webjobs_burguerking';
const DB_USER = 'webjobs';
const DB_PASS = 'clase321';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function crearTablas(): void {
    $sql = "CREATE TABLE IF NOT EXISTS pedidos (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        direccion VARCHAR(255) NOT NULL,
        estado ENUM('preparando','despachado') NOT NULL DEFAULT 'preparando',
        cantidad_sencilla INT UNSIGNED NOT NULL DEFAULT 0,
        cantidad_doble INT UNSIGNED NOT NULL DEFAULT 0,
        cantidad_mixta INT UNSIGNED NOT NULL DEFAULT 0,
        creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_estado (estado),
        INDEX idx_creado_en (creado_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    db()->exec($sql);
}
