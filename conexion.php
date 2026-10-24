<?php
// 1. Configuración de la base de datos (Usando el usuario dedicado, NO root)
$host = '127.0.0.1'; // Usar 127.0.0.1 en lugar de 'localhost' fuerza la conexión TCP/IP, evitando problemas con sockets de MySQL en algunos servidores
$db   = 'controlo_arpiedi';
$user = 'arpiedi_app'; // Usuario de aplicación con privilegios limitados
$pass = 'ontragura123!TuCseñaSe'; // Contraseña fuerte
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // ¡Excelente! Mantiene las prepared statements reales
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // 2. Manejo seguro de errores para producción
    // Registra el error real en un archivo de log del servidor (recomendado)
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    
    // Muestra un mensaje genérico al usuario para no revelar información sensible
    // En un entorno real, podrías redirigir a una página de error 500 personalizada
    die("Error crítico: No se pudo establecer la conexión con la base de datos. Por favor, inténtelo más tarde.");
}
?>
