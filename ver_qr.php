<?php
// Incluir archivos de configuración
require_once 'includes/config.php';
require_once 'includes/conexion.php';

// Función para generar URL del QR usando QR Server API (más confiable)
function generarUrlQR($token, $size = 300) {
    $base_url = 'https://api.qrserver.com/v1/create-qr-code/';
    $params = [
        'size' => $size . 'x' . $size,
        'data' => $token,
        'format' => 'png',
        'bgcolor' => 'FFFFFF',
        'color' => '000000',
        'margin' => 10
    ];
    return $base_url . '?' . http_build_query($params);
}

$participante = null;
$error = '';

// Si hay un ID o token en la URL
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $conexion->prepare("SELECT * FROM `registro-seminario` WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $participante = $result->fetch_assoc();
        } else {
            $error = "No se encontró participante con ID: $id";
        }
    } catch (Exception $e) {
        $error = "Error al buscar participante: " . $e->getMessage();
    }
} elseif (isset($_GET['token'])) {
    $token = $_GET['token'];
    try {
        $stmt = $conexion->prepare("SELECT * FROM `registro-seminario` WHERE qr_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $participante = $result->fetch_assoc();
        } else {
            $error = "No se encontró participante con token: $token";
        }
    } catch (Exception $e) {
        $error = "Error al buscar participante: " . $e->getMessage();
    }
}

// Obtener todos los participantes con QR para el selector
try {
    $stmt = $conexion->prepare("SELECT id, nombre_completo, email, qr_token FROM `registro-seminario` WHERE qr_token IS NOT NULL AND qr_token != '' ORDER BY nombre_completo");
    $stmt->execute();
    $todos_participantes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $todos_participantes = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver QR - XVI Aniversario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }
        
        .qr-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 600px;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #1976d2, #d84315);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .qr-display {
            text-align: center;
            padding: 2rem;
        }
        
        .qr-image {
            border: 3px solid #1976d2;
            border-radius: 15px;
            margin: 1rem 0;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .participant-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        
        .token-display {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: monospace;
            word-break: break-all;
        }
        
        .btn-download {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 10px 30px;
            border-radius: 25px;
            margin: 5px;
        }
        
        .btn-download:hover {
            background: linear-gradient(45deg, #20c997, #28a745);
            color: white;
        }
        
        .btn-print {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            border: none;
            color: white;
            padding: 10px 30px;
            border-radius: 25px;
            margin: 5px;
        }
        
        .btn-print:hover {
            background: linear-gradient(45deg, #fd7e14, #ffc107);
            color: white;
        }
        
        .selector-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        @media print {
            .no-print { display: none !important; }
            .qr-container { 
                box-shadow: none; 
                border: 1px solid #ccc;
                margin: 0;
                max-width: none;
            }
            .header { background: #1976d2 !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Selector de participante -->
        <div class="selector-container no-print">
            <h5><i class="fas fa-search me-2"></i>Buscar QR de Participante</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <select name="id" class="form-select" onchange="this.form.submit()">
                        <option value="">Seleccionar participante...</option>
                        <?php foreach ($todos_participantes as $p): ?>
                            <option value="<?php echo $p['id']; ?>" 
                                    <?php echo (isset($_GET['id']) && $_GET['id'] == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['nombre_completo']); ?> - <?php echo htmlspecialchars($p['email']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <a href="admin/generar_qrs.php" class="btn btn-primary">
                        <i class="fas fa-cog me-1"></i>Panel Admin
                    </a>
                </div>
            </form>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger no-print">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($participante): ?>
            <div class="qr-container">
                <div class="header">
                    <h1><i class="fas fa-qrcode me-2"></i>Código QR</h1>
                    <p>XVI Aniversario - UNAMAD</p>
                </div>
                
                <div class="qr-display">
                    <div class="participant-info">
                        <h4><?php echo htmlspecialchars($participante['nombre_completo']); ?></h4>
                        <p class="mb-1">
                            <i class="fas fa-envelope me-2"></i>
                            <?php echo htmlspecialchars($participante['email']); ?>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-phone me-2"></i>
                            <?php echo htmlspecialchars($participante['celular']); ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-university me-2"></i>
                            <?php echo htmlspecialchars($participante['universidad']); ?>
                        </p>
                        <?php if ($participante['asistio'] == 1): ?>
                            <div class="mt-2">
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>
                                    Ya asistió - <?php echo date('d/m/Y H:i', strtotime($participante['fecha_asistencia'])); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <img src="<?php echo generarUrlQR($participante['qr_token']); ?>" 
                         alt="QR de <?php echo htmlspecialchars($participante['nombre_completo']); ?>" 
                         class="qr-image">
                    
                    <div class="token-display">
                        <small class="text-muted">Token QR:</small><br>
                        <strong><?php echo htmlspecialchars($participante['qr_token']); ?></strong>
                    </div>
                    
                    <div class="no-print">
                        <button onclick="descargarQR()" class="btn btn-download">
                            <i class="fas fa-download me-2"></i>Descargar QR
                        </button>
                        <button onclick="window.print()" class="btn btn-print">
                            <i class="fas fa-print me-2"></i>Imprimir
                        </button>
                        <a href="checkin.php" class="btn btn-success">
                            <i class="fas fa-camera me-2"></i>Ir a Check-in
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="qr-container">
                <div class="header">
                    <h1><i class="fas fa-qrcode me-2"></i>Ver Código QR</h1>
                    <p>Selecciona un participante para ver su QR</p>
                </div>
                <div class="qr-display">
                    <p class="text-muted">
                        <i class="fas fa-arrow-up me-2"></i>
                        Usa el selector de arriba para buscar el QR de cualquier participante.
                    </p>
                    
                    <?php if (empty($todos_participantes)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No hay participantes con QR generado. 
                            <a href="admin/generar_qrs.php">Generar QRs primero</a>.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function descargarQR() {
            <?php if ($participante): ?>
                const qrUrl = '<?php echo generarUrlQR($participante['qr_token']); ?>';
                const nombre = '<?php echo htmlspecialchars($participante['nombre_completo']); ?>';
                
                // Crear un enlace temporal para descarga
                const link = document.createElement('a');
                link.href = qrUrl;
                link.download = `QR_${nombre.replace(/\s+/g, '_')}.png`;
                link.target = '_blank';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            <?php endif; ?>
        }
        
        // Mensaje de ayuda si no hay participante seleccionado
        <?php if (!$participante && empty($_GET['id']) && empty($_GET['token'])): ?>
            console.log('Para ver un QR específico, añade ?id=11 a la URL (donde 11 es el ID del participante)');
        <?php endif; ?>
    </script>
</body>
</html>