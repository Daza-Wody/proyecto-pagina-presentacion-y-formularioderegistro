<?php
// Incluir archivos de configuración
require_once '../includes/config.php';
require_once '../includes/conexion.php';

// Iniciar sesión
session_start();

// VERIFICAR LOGIN - OBLIGATORIO
if (!isset($_SESSION['admin_logueado']) || $_SESSION['admin_logueado'] !== true) {
    header('Location: login.php');
    exit();
}

// Función para generar token único
function generarTokenUnico() {
    return 'QR_' . uniqid() . '_' . bin2hex(random_bytes(8));
}

// Función para generar QR usando QR Server API (más confiable que Google Charts)
function generarUrlQR($token, $size = 200) {
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

$mensaje = '';
$error = '';
$qrs_generados = [];

// Procesar generación de QRs
if ($_POST) {
    $accion = $_POST['accion'] ?? '';
    
    try {
        if ($accion === 'generar_faltantes') {
            // Generar QRs solo para registros que no tienen token
            $stmt = $conexion->prepare("SELECT id, nombre_completo, email FROM `registro-seminario` WHERE qr_token IS NULL OR qr_token = ''");
            $stmt->execute();
            $registros_sin_qr = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $contador = 0;
            foreach ($registros_sin_qr as $registro) {
                $token = generarTokenUnico();
                
                $stmt_update = $conexion->prepare("UPDATE `registro-seminario` SET qr_token = ? WHERE id = ?");
                $stmt_update->bind_param("si", $token, $registro['id']);
                
                if ($stmt_update->execute()) {
                    $qrs_generados[] = [
                        'id' => $registro['id'],
                        'nombre' => $registro['nombre_completo'],
                        'email' => $registro['email'],
                        'token' => $token,
                        'qr_url' => generarUrlQR($token)
                    ];
                    $contador++;
                }
            }
            
            $mensaje = "Se generaron $contador códigos QR exitosamente.";
            
        } elseif ($accion === 'regenerar_todos') {
            // Regenerar QRs para TODOS los registros
            $stmt = $conexion->prepare("SELECT id, nombre_completo, email FROM `registro-seminario`");
            $stmt->execute();
            $todos_registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $contador = 0;
            foreach ($todos_registros as $registro) {
                $token = generarTokenUnico();
                
                $stmt_update = $conexion->prepare("UPDATE `registro-seminario` SET qr_token = ? WHERE id = ?");
                $stmt_update->bind_param("si", $token, $registro['id']);
                
                if ($stmt_update->execute()) {
                    $qrs_generados[] = [
                        'id' => $registro['id'],
                        'nombre' => $registro['nombre_completo'],
                        'email' => $registro['email'],
                        'token' => $token,
                        'qr_url' => generarUrlQR($token)
                    ];
                    $contador++;
                }
            }
            
            $mensaje = "Se regeneraron $contador códigos QR para todos los registros.";
        }
        
    } catch (Exception $e) {
        $error = "Error al generar QRs: " . $e->getMessage();
        error_log("Error generando QRs: " . $e->getMessage());
    }
}

// Obtener estadísticas
try {
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM `registro-seminario`");
    $stmt->execute();
    $total_registros = $stmt->get_result()->fetch_assoc()['total'];
    
    $stmt = $conexion->prepare("SELECT COUNT(*) as con_qr FROM `registro-seminario` WHERE qr_token IS NOT NULL AND qr_token != ''");
    $stmt->execute();
    $registros_con_qr = $stmt->get_result()->fetch_assoc()['con_qr'];
    
    $registros_sin_qr = $total_registros - $registros_con_qr;
    
} catch (Exception $e) {
    $error = "Error al obtener estadísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de QRs - XVI Aniversario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .stat-card {
            background: linear-gradient(45deg, #1976d2, #d84315);
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .qr-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .qr-image {
            max-width: 150px;
            height: auto;
        }
        .btn-generar {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
        }
        .btn-generar:hover {
            background: linear-gradient(45deg, #20c997, #28a745);
            color: white;
        }
        .btn-regenerar {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            border: none;
            color: white;
        }
        .btn-regenerar:hover {
            background: linear-gradient(45deg, #fd7e14, #ffc107);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-qrcode me-2"></i>
                Generador de QRs - XVI Aniversario
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-arrow-left me-1"></i>Volver al Panel
                </a>
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt me-1"></i>Ver Sitio
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($mensaje); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3><?php echo $total_registros; ?></h3>
                        <p class="mb-0"><i class="fas fa-users me-2"></i>Total Registros</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3><?php echo $registros_con_qr; ?></h3>
                        <p class="mb-0"><i class="fas fa-qrcode me-2"></i>Con QR</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3><?php echo $registros_sin_qr; ?></h3>
                        <p class="mb-0"><i class="fas fa-times-circle me-2"></i>Sin QR</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3><?php echo $registros_con_qr > 0 ? round(($registros_con_qr/$total_registros)*100) : 0; ?>%</h3>
                        <p class="mb-0"><i class="fas fa-percentage me-2"></i>Completado</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-magic me-2"></i>Generar QRs</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="d-grid gap-2">
                                <button type="submit" name="accion" value="generar_faltantes" class="btn btn-generar btn-lg">
                                    <i class="fas fa-plus-circle me-2"></i>
                                    Generar QRs Faltantes (<?php echo $registros_sin_qr; ?>)
                                </button>
                                <button type="submit" name="accion" value="regenerar_todos" class="btn btn-regenerar btn-lg" 
                                        onclick="return confirm('¿Estás seguro? Esto regenerará TODOS los QRs existentes.')">
                                    <i class="fas fa-sync-alt me-2"></i>
                                    Regenerar TODOS los QRs
                                </button>
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Los QRs se generan usando Google Charts API. Cada código es único e irrepetible.
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Información</h5>
                    </div>
                    <div class="card-body">
                        <h6>¿Cómo funciona?</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Cada registro obtiene un token único</li>
                            <li><i class="fas fa-check text-success me-2"></i>El QR contiene este token encriptado</li>
                            <li><i class="fas fa-check text-success me-2"></i>En el evento se escanea para marcar asistencia</li>
                            <li><i class="fas fa-check text-success me-2"></i>Se evita duplicación y falsificación</li>
                        </ul>
                        
                        <h6 class="mt-3">Próximos pasos:</h6>
                        <ol class="list-unstyled">
                            <li><i class="fas fa-arrow-right text-primary me-2"></i>Generar QRs</li>
                            <li><i class="fas fa-arrow-right text-primary me-2"></i>Enviar por email (opcional)</li>
                            <li><i class="fas fa-arrow-right text-primary me-2"></i>Usar página de check-in en el evento</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($qrs_generados)): ?>
        <!-- QRs Generados -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-qrcode me-2"></i>QRs Generados (<?php echo count($qrs_generados); ?>)</h5>
                        <button class="btn btn-primary btn-sm" onclick="imprimirQRs()">
                            <i class="fas fa-print me-1"></i>Imprimir Todos
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row" id="qrs-container">
                            <?php foreach ($qrs_generados as $qr): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="qr-card text-center">
                                    <img src="<?php echo $qr['qr_url']; ?>" alt="QR para <?php echo htmlspecialchars($qr['nombre']); ?>" class="qr-image mb-2">
                                    <h6><?php echo htmlspecialchars($qr['nombre']); ?></h6>
                                    <p class="text-muted small mb-1"><?php echo htmlspecialchars($qr['email']); ?></p>
                                    <p class="text-muted small">ID: <?php echo $qr['id']; ?></p>
                                    <small class="text-secondary"><?php echo $qr['token']; ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function imprimirQRs() {
            const contenidoQRs = document.getElementById('qrs-container').innerHTML;
            const ventanaImpresion = window.open('', '_blank');
            
            ventanaImpresion.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>QRs para Impresión</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .qr-card { 
                            display: inline-block; 
                            margin: 10px; 
                            padding: 15px; 
                            border: 1px solid #ccc; 
                            text-align: center; 
                            width: 200px;
                            break-inside: avoid;
                        }
                        .qr-image { max-width: 150px; height: auto; }
                        h6 { margin: 10px 0 5px 0; font-size: 14px; }
                        p { margin: 5px 0; font-size: 12px; }
                        small { font-size: 10px; }
                        @media print {
                            .qr-card { page-break-inside: avoid; }
                        }
                    </style>
                </head>
                <body>
                    <h1>QRs de Asistencia - XVI Aniversario</h1>
                    <div style="display: flex; flex-wrap: wrap;">
                        ${contenidoQRs}
                    </div>
                </body>
                </html>
            `);
            
            ventanaImpresion.document.close();
            ventanaImpresion.focus();
            ventanaImpresion.print();
        }
        
        // Auto-refresh cada 30 segundos si no hay QRs generados
        <?php if (empty($qrs_generados)): ?>
        setTimeout(function() {
            if (!document.querySelector('.alert-success')) {
                location.reload();
            }
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>