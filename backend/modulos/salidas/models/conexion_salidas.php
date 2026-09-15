<?php
require_once dirname(__DIR__, 3) . '/config/config.php';

$dsnSalidas = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';port=' . db_port;

$opcionesSalidas = [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES => false,
];

try {
	$pdoSalidas = new PDO($dsnSalidas, DB_USER, DB_PASS, $opcionesSalidas);
} catch (PDOException $e) {
	error_log('Error de conexión del módulo salidas: ' . $e->getMessage());
	exit('Error de conexión a la base de datos');
}
