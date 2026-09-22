<?php
require_once dirname(__DIR__) . '/config/config.php';

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';port=' . db_port;

$opciones =[
    
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try{
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    exit('Error de conexión a la base de datos');   
    }
