<?php
// Configuración de conexión a la base de datos usando PDO
$host = 'localhost';
$dbname = 'usm_postulaciones';
$username = 'root';
$password = ''; 

// Intentamos establecer la conexión a la base de datos
try {
    // Creamos la instancia de PHP Data Object (PDO) para conectarnos a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Configuramos PDO para que lance excepciones en caso de error, lo que facilita la depuración
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Desactivamos la emulación de sentencias preparadas para mejorar la seguridad y el rendimiento
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // Si falla, detenemos la ejecución y mostramos el error
    die("Error de conexión a la base de datos: " . $e->getMessage());
}