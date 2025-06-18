<?php
// Incluir configuración
require_once 'config.php';

// Crear conexión con manejo de errores
try {
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verificar conexión
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión");
    }
    
    // Configurar charset
    $conexion->set_charset("utf8");
    
} catch (Exception $e) {
    // En producción, NO mostrar detalles del error
    error_log($e->getMessage());
    die("Lo sentimos, estamos experimentando problemas técnicos.");
}
?>