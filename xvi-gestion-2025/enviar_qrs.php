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

// CONFIGURACIÓN GMAIL SMTP - CAMBIAR SOLO LA CONTRASEÑA
$smtp_config = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'lukakudaza@gmail.com',           // YA ESTÁ TU EMAIL
    'password' => 'oxpc omsw uoof nagm',  // CAMBIAR SOLO ESTO
    'from_name' => 'XVI Aniversario UNAMAD'
];

// Función para generar URL del QR
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

// Función para enviar email con PHPMailer
function enviarEmailConSMTP($email, $nombre, $token, $smtp_config) {
    // Verificar si PHPMailer está disponible
    if (!file_exists('../phpmailer/PHPMailer.php')) {
        return ['success' => false, 'error' => 'PHPMailer no encontrado. Descárgalo primero.'];
    }
    
    require_once '../phpmailer/PHPMailer.php';
    require_once '../phpmailer/SMTP.php';
    require_once '../phpmailer/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host = $smtp_config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_config['username'];
        $mail->Password = $smtp_config['password'];
        $mail->SMTPSecure = 'tls';
        $mail->Port = $smtp_config['port'];
        $mail->CharSet = 'UTF-8';
        
        // Configuración del email
        $mail->setFrom($smtp_config['username'], $smtp_config['from_name']);
        $mail->addAddress($email, $nombre);
        $mail->addReplyTo($smtp_config['username'], $smtp_config['from_name']);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Tu código QR - XVI Aniversario UNAMAD';
        
        $qr_url = generarUrlQR($token);
        
        $mail->Body = generarHTMLEmail($nombre, $token, $qr_url);
        
        return ['success' => $mail->send(), 'error' => null];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

// Función para enviar email sin PHPMailer (usando mail() de PHP)
function enviarEmailSimple($email, $nombre, $token, $smtp_config) {
    $qr_url = generarUrlQR($token);
    $asunto = "Tu código QR - XVI Aniversario UNAMAD";
    
    $mensaje_html = generarHTMLEmail($nombre, $token, $qr_url);
    
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $smtp_config['from_name'] . ' <' . $smtp_config['username'] . '>';
    $headers[] = 'Reply-To: ' . $smtp_config['username'];
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    
    $resultado = mail($email, $asunto, $mensaje_html, implode("\r\n", $headers));
    
    return ['success' => $resultado, 'error' => $resultado ? null : 'Error al enviar email'];
}

// Función para generar HTML del email
function generarHTMLEmail($nombre, $token, $qr_url) {
    return '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tu código QR - XVI Aniversario</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(45deg, #1976d2, #d84315);
                color: white;
                padding: 30px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
            }
            .header p {
                margin: 10px 0 0 0;
                opacity: 0.9;
            }
            .content {
                padding: 30px 20px;
            }
            .qr-section {
                text-align: center;
                margin: 30px 0;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 10px;
                border: 2px dashed #1976d2;
            }
            .qr-code {
                max-width: 250px;
                height: auto;
                margin: 20px 0;
                border: 3px solid white;
                border-radius: 10px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            }
            .instructions {
                background: #e3f2fd;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
            }
            .instructions h3 {
                color: #1976d2;
                margin-top: 0;
            }
            .instructions ol {
                margin: 10px 0;
                padding-left: 20px;
            }
            .instructions li {
                margin: 5px 0;
            }
            .evento-info {
                background: #fff3e0;
                padding: 20px;
                border-radius: 10px;
                border-left: 4px solid #ff9800;
            }
            .footer {
                background: #333;
                color: white;
                padding: 20px;
                text-align: center;
                font-size: 14px;
            }
            .highlight {
                color: #d84315;
                font-weight: bold;
            }
            .important-note {
                background: #fff8e1;
                border: 1px solid #ffc107;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
                color: #f57c00;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <h1>🎓 XVI Aniversario</h1>
                <p>Carrera de Administración y Negocios Internacionales - UNAMAD</p>
                <p><strong>Seminario: "Negocios Internacionales, Ya es una Realidad"</strong></p>
            </div>
            
            <div class="content">
                <h2>¡Hola ' . htmlspecialchars($nombre) . '! 👋</h2>
                
                <p>¡Gracias por inscribirte a nuestro seminario! Tu registro ha sido confirmado exitosamente.</p>
                
                <div class="qr-section">
                    <h3>📱 Tu Código QR Personal</h3>
                    <p><strong>Presenta este código QR en la entrada del evento</strong></p>
                    <img src="' . $qr_url . '" alt="Código QR de ' . htmlspecialchars($nombre) . '" class="qr-code">
                    <p style="color: #666; font-size: 12px;">Código: ' . htmlspecialchars($token) . '</p>
                </div>
                
                <div class="important-note">
                    ⚠️ <strong>IMPORTANTE:</strong> Guarda este email o toma una captura de pantalla del QR. Lo necesitarás para ingresar al evento.
                </div>
                
                <div class="instructions">
                    <h3>📋 Instrucciones para el día del evento:</h3>
                    <ol>
                        <li><strong>Llega 15 minutos antes</strong> del inicio del seminario</li>
                        <li><strong>Presenta tu QR</strong> en el celular o impreso en la entrada</li>
                        <li><strong>Nuestro equipo escaneará</strong> tu código para registrar tu asistencia</li>
                        <li><strong>¡Disfruta del seminario!</strong> y obtén tu certificado al final</li>
                    </ol>
                </div>
                
                <div class="evento-info">
                    <h3>📅 Información del Evento</h3>
                    <p><strong>📍 Lugar:</strong> Auditorio de Formación Académica - Centro de Idiomas, 7mo Piso</p>
                    <p><strong>🕐 Hora:</strong> 8:00 AM - Registro y Acreditación</p>
                    <p><strong>🕘 Inicio:</strong> 9:00 AM - Ceremonia de Apertura</p>
                    <p><strong>📍 Dirección:</strong> Av. Dos de Mayo - Centro de Idiomas, UNAMAD</p>
                    <p><strong>🎓 Certificado:</strong> Solo para participantes con mayor asistencia</p>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <p><strong>¿Tienes preguntas?</strong></p>
                    <p>WhatsApp: <span class="highlight">+51 975 844 881</span></p>
                    <p>Email: administracion@unamad.edu.pe</p>
                </div>
            </div>
            
            <div class="footer">
                <p><strong>Universidad Nacional Amazónica de Madre de Dios</strong></p>
                <p>Carrera de Administración de Empresas y Negocios Internacionales</p>
                <p>© ' . date('Y') . ' UNAMAD - Licenciada por SUNEDU</p>
            </div>
        </div>
    </body>
    </html>';
}

$mensaje = '';
$error = '';
$emails_enviados = [];

// Procesar envío de emails
if ($_POST) {
    $accion = $_POST['accion'] ?? '';
    $metodo_envio = $_POST['metodo'] ?? 'phpmailer'; // phpmailer o simple
    
    try {
        if ($accion === 'enviar_faltantes') {
            // Verificar/crear columna email_enviado
            $stmt = $conexion->prepare("SHOW COLUMNS FROM `registro-seminario` LIKE 'email_enviado'");
            $stmt->execute();
            if ($stmt->get_result()->num_rows == 0) {
                $conexion->query("ALTER TABLE `registro-seminario` ADD COLUMN `email_enviado` TINYINT(1) DEFAULT 0");
            }
            
            // Enviar solo a registros que tienen QR pero no han recibido email
            $stmt = $conexion->prepare("
                SELECT id, nombre_completo, email, qr_token 
                FROM `registro-seminario` 
                WHERE qr_token IS NOT NULL 
                AND qr_token != '' 
                AND (email_enviado IS NULL OR email_enviado = 0)
            ");
            $stmt->execute();
            $registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $enviados = 0;
            $errores = 0;
            
            foreach ($registros as $registro) {
                // Elegir método de envío
                if ($metodo_envio === 'phpmailer') {
                    $resultado = enviarEmailConSMTP($registro['email'], $registro['nombre_completo'], $registro['qr_token'], $smtp_config);
                } else {
                    $resultado = enviarEmailSimple($registro['email'], $registro['nombre_completo'], $registro['qr_token'], $smtp_config);
                }
                
                if ($resultado['success']) {
                    // Marcar como enviado
                    $stmt_update = $conexion->prepare("UPDATE `registro-seminario` SET email_enviado = 1 WHERE id = ?");
                    $stmt_update->bind_param("i", $registro['id']);
                    $stmt_update->execute();
                    
                    $emails_enviados[] = [
                        'nombre' => $registro['nombre_completo'],
                        'email' => $registro['email'],
                        'estado' => 'enviado'
                    ];
                    $enviados++;
                } else {
                    $emails_enviados[] = [
                        'nombre' => $registro['nombre_completo'],
                        'email' => $registro['email'],
                        'estado' => 'error',
                        'error' => $resultado['error']
                    ];
                    $errores++;
                }
                
                // Pausa para evitar spam
                usleep(1000000); // 1 segundo
            }
            
            $mensaje = "Se enviaron $enviados emails exitosamente.";
            if ($errores > 0) {
                $mensaje .= " $errores emails fallaron.";
            }
        }
        
    } catch (Exception $e) {
        $error = "Error al enviar emails: " . $e->getMessage();
        error_log("Error enviando emails: " . $e->getMessage());
    }
}

// Obtener estadísticas
try {
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM `registro-seminario` WHERE qr_token IS NOT NULL AND qr_token != ''");
    $stmt->execute();
    $total_con_qr = $stmt->get_result()->fetch_assoc()['total'];
    
    $stmt = $conexion->prepare("SELECT COUNT(*) as enviados FROM `registro-seminario` WHERE email_enviado = 1");
    $stmt->execute();
    $emails_enviados_count = $stmt->get_result()->fetch_assoc()['enviados'];
    
    $pendientes = $total_con_qr - $emails_enviados_count;
    
} catch (Exception $e) {
    $error = "Error al obtener estadísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envío de QRs por Email - XVI Aniversario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .stat-card {
            background: linear-gradient(45deg, #1976d2, #d84315);
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .config-section {
            background: #fff3e0;
            border: 1px solid #ffcc02;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
        }
        .config-section h6 {
            color: #ef6c00;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-envelope me-2"></i>Envío de QRs por Email
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="generar_qrs.php">
                    <i class="fas fa-qrcode me-1"></i>Generar QRs
                </a>
                <a class="nav-link" href="index.php">
                    <i class="fas fa-arrow-left me-1"></i>Panel Principal
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        
        <!-- Configuración SMTP -->
        <div class="config-section">
            <h6><i class="fas fa-cog me-2"></i>Configuración SMTP</h6>
            <p><strong>Host:</strong> <?php echo $smtp_config['host']; ?></p>
            <p><strong>Usuario:</strong> <?php echo $smtp_config['username']; ?></p>
            <p><strong>Estado PHPMailer:</strong> 
                <?php if (file_exists('../phpmailer/PHPMailer.php')): ?>
                    <span class="badge bg-success">Disponible</span>
                <?php else: ?>
                    <span class="badge bg-warning">No encontrado</span>
                    <small class="d-block">Descarga PHPMailer para mejor compatibilidad</small>
                <?php endif; ?>
            </p>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3><?php echo $total_con_qr; ?></h3>
                        <p class="mb-0"><i class="fas fa-qrcode me-2"></i>Con QR</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3><?php echo $emails_enviados_count; ?></h3>
                        <p class="mb-0"><i class="fas fa-check-circle me-2"></i>Emails Enviados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h3><?php echo $pendientes; ?></h3>
                        <p class="mb-0"><i class="fas fa-clock me-2"></i>Pendientes</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Envío -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-paper-plane me-2"></i>Enviar QRs por Email</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Método de envío:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="metodo" value="phpmailer" id="phpmailer" checked>
                            <label class="form-check-label" for="phpmailer">
                                PHPMailer (SMTP) - Recomendado para Hostinger
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="metodo" value="simple" id="simple">
                            <label class="form-check-label" for="simple">
                                PHP mail() - Método simple (puede fallar)
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" name="accion" value="enviar_faltantes" class="btn btn-primary btn-lg">
                        <i class="fas fa-send me-2"></i>Enviar a Pendientes (<?php echo $pendientes; ?>)
                    </button>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Importante:</strong> Configura primero los datos SMTP en la línea 19-25 del archivo.
                        </small>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($emails_enviados)): ?>
        <!-- Resultados -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>Resultados del Envío</h5>
            </div>
            <div class="card-body">
                <?php foreach ($emails_enviados as $email): ?>
                    <div class="d-flex justify-content-between mb-2 p-2 bg-light rounded">
                        <div>
                            <strong><?php echo htmlspecialchars($email['nombre']); ?></strong><br>
                            <small><?php echo htmlspecialchars($email['email']); ?></small>
                            <?php if (isset($email['error'])): ?>
                                <br><small class="text-danger"><?php echo htmlspecialchars($email['error']); ?></small>
                            <?php endif; ?>
                        </div>
                        <span class="badge <?php echo $email['estado'] === 'enviado' ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $email['estado']; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>