<?php
// Incluir archivos de configuración
require_once 'includes/config.php';
require_once 'includes/conexion.php';

// Headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar OPTIONS request para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Función para responder con JSON
function responderJSON($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Función para log de eventos
function logEvento($mensaje, $datos = []) {
    $log = date('Y-m-d H:i:s') . " - " . $mensaje;
    if (!empty($datos)) {
        $log .= " - " . json_encode($datos, JSON_UNESCAPED_UNICODE);
    }
    error_log($log);
}

try {
    // Verificar si es solicitud de estadísticas
    if (isset($_GET['stats'])) {
        $stmt = $conexion->prepare("SELECT COUNT(*) as asistentes FROM `registro-seminario` WHERE asistio = 1");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        responderJSON([
            'asistentes' => (int)$result['asistentes']
        ]);
    }
    
    // Verificar que sea POST para verificación de QR
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderJSON([
            'success' => false,
            'message' => 'Método no permitido. Use POST.'
        ]);
    }
    
    // Obtener el token QR
    $qr_token = $_POST['qr_token'] ?? '';
    
    if (empty($qr_token)) {
        responderJSON([
            'success' => false,
            'message' => 'Token QR no proporcionado.'
        ]);
    }
    
    // Limpiar el token (remover espacios y caracteres especiales)
    $qr_token = trim($qr_token);
    
    logEvento("Verificando token QR", ['token' => $qr_token]);
    
    // Buscar el participante por token QR
    $stmt = $conexion->prepare("
        SELECT id, nombre_completo, email, celular, universidad, carrera, 
               asistio, fecha_asistencia, hora_llegada, qr_token 
        FROM `registro-seminario` 
        WHERE qr_token = ?
    ");
    $stmt->bind_param("s", $qr_token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        logEvento("Token QR no encontrado", ['token' => $qr_token]);
        responderJSON([
            'success' => false,
            'message' => 'Código QR inválido o no encontrado. Verifica que el código sea correcto.'
        ]);
    }
    
    $participante = $result->fetch_assoc();
    
    // Verificar si ya había marcado asistencia
    if ($participante['asistio'] == 1) {
        logEvento("Participante ya registrado", [
            'id' => $participante['id'],
            'nombre' => $participante['nombre_completo'],
            'primera_asistencia' => $participante['fecha_asistencia']
        ]);
        
        responderJSON([
            'success' => true,
            'ya_registrado' => true,
            'message' => 'Este participante ya había marcado asistencia anteriormente.',
            'participante' => [
                'id' => $participante['id'],
                'nombre_completo' => $participante['nombre_completo'],
                'email' => $participante['email'],
                'universidad' => $participante['universidad'],
                'carrera' => $participante['carrera'],
                'fecha_asistencia' => date('d/m/Y H:i', strtotime($participante['fecha_asistencia']))
            ]
        ]);
    }
    
    // Marcar asistencia
    $fecha_actual = date('Y-m-d H:i:s');
    $hora_actual = date('H:i:s');
    
    $stmt_update = $conexion->prepare("
        UPDATE `registro-seminario` 
        SET asistio = 1, fecha_asistencia = ?, hora_llegada = ? 
        WHERE id = ?
    ");
    $stmt_update->bind_param("ssi", $fecha_actual, $hora_actual, $participante['id']);
    
    if ($stmt_update->execute()) {
        logEvento("Asistencia registrada exitosamente", [
            'id' => $participante['id'],
            'nombre' => $participante['nombre_completo'],
            'email' => $participante['email'],
            'fecha' => $fecha_actual
        ]);
        
        responderJSON([
            'success' => true,
            'ya_registrado' => false,
            'message' => 'Asistencia registrada exitosamente.',
            'participante' => [
                'id' => $participante['id'],
                'nombre_completo' => $participante['nombre_completo'],
                'email' => $participante['email'],
                'celular' => $participante['celular'],
                'universidad' => $participante['universidad'],
                'carrera' => $participante['carrera'],
                'fecha_asistencia' => date('d/m/Y H:i', strtotime($fecha_actual))
            ]
        ]);
    } else {
        logEvento("Error al actualizar asistencia", [
            'id' => $participante['id'],
            'error' => $conexion->error
        ]);
        
        responderJSON([
            'success' => false,
            'message' => 'Error al registrar asistencia. Intenta nuevamente.'
        ]);
    }
    
} catch (Exception $e) {
    logEvento("Error en verificar_asistencia.php", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    responderJSON([
        'success' => false,
        'message' => 'Error interno del servidor. Contacta al administrador.'
    ]);
}
?>