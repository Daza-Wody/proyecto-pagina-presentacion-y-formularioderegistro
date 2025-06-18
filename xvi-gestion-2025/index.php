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

// Verificar que no haya expirado (opcional - 2 horas)
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time'] > 7200)) {
    session_destroy();
    header('Location: login.php?expired=1');
    exit();
}

// Obtener estadísticas generales
try {
    // Total de registrados
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM `registro-seminario`");
    $stmt->execute();
    $total_registrados = $stmt->get_result()->fetch_assoc()['total'];

    // Registrados por universidad (CONSULTA SIMPLIFICADA)
    $stmt = $conexion->prepare("SELECT universidad, COUNT(*) as cantidad FROM `registro-seminario` GROUP BY universidad ORDER BY cantidad DESC");
    $stmt->execute();
    $universidades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // DEBUG: Si está vacío, mostrar datos raw
    if (empty($universidades)) {
        $stmt_debug = $conexion->prepare("SELECT universidad FROM `registro-seminario` LIMIT 5");
        $stmt_debug->execute();
        $raw_unis = $stmt_debug->get_result()->fetch_all(MYSQLI_ASSOC);
        error_log("DEBUG Universidades vacías. Datos raw: " . print_r($raw_unis, true));
    }

    // Registrados por carrera (CONSULTA SIMPLIFICADA)
    $stmt = $conexion->prepare("SELECT carrera, COUNT(*) as cantidad FROM `registro-seminario` GROUP BY carrera ORDER BY cantidad DESC");
    $stmt->execute();
    $carreras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // DEBUG: Si está vacío, mostrar datos raw
    if (empty($carreras)) {
        $stmt_debug = $conexion->prepare("SELECT carrera FROM `registro-seminario` LIMIT 5");
        $stmt_debug->execute();
        $raw_carreras = $stmt_debug->get_result()->fetch_all(MYSQLI_ASSOC);
        error_log("DEBUG Carreras vacías. Datos raw: " . print_r($raw_carreras, true));
    }

    // Registrados por país (CORREGIDO - sin filtros)
    $stmt = $conexion->prepare("SELECT pais, COUNT(*) as cantidad FROM `registro-seminario` WHERE pais IS NOT NULL AND pais != '' GROUP BY pais ORDER BY cantidad DESC");
    $stmt->execute();
    $paises = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Cómo se enteraron (CORREGIDO - sin filtros)
    $stmt = $conexion->prepare("SELECT como_entero, COUNT(*) as cantidad FROM `registro-seminario` WHERE como_entero IS NOT NULL AND como_entero != '' GROUP BY como_entero ORDER BY cantidad DESC");
    $stmt->execute();
    $como_enteraron = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // NUEVAS ESTADÍSTICAS DE ASISTENCIA Y QR
    // Total que asistieron
    $stmt = $conexion->prepare("SELECT COUNT(*) as total_asistieron FROM `registro-seminario` WHERE asistio = 1");
    $stmt->execute();
    $total_asistieron = $stmt->get_result()->fetch_assoc()['total_asistieron'];

    // Total con QR generado
    $stmt = $conexion->prepare("SELECT COUNT(*) as total_con_qr FROM `registro-seminario` WHERE qr_token IS NOT NULL AND qr_token != ''");
    $stmt->execute();
    $total_con_qr = $stmt->get_result()->fetch_assoc()['total_con_qr'];

    // Calcular porcentajes
    $porcentaje_asistencia = $total_con_qr > 0 ? round(($total_asistieron / $total_con_qr) * 100) : 0;
    $pendientes_asistencia = $total_con_qr - $total_asistieron;

    // Asistencia por hora
    $stmt = $conexion->prepare("
        SELECT 
            HOUR(fecha_asistencia) as hora,
            COUNT(*) as cantidad 
        FROM `registro-seminario` 
        WHERE asistio = 1 AND fecha_asistencia IS NOT NULL
        GROUP BY HOUR(fecha_asistencia) 
        ORDER BY hora
    ");
    $stmt->execute();
    $asistencia_por_hora = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Últimas asistencias
    $stmt = $conexion->prepare("
        SELECT nombre_completo, email, universidad, fecha_asistencia, hora_llegada 
        FROM `registro-seminario` 
        WHERE asistio = 1 
        ORDER BY fecha_asistencia DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $ultimas_asistencias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Últimos 10 registros
    $stmt = $conexion->prepare("SELECT * FROM `registro-seminario` ORDER BY fecha_registro DESC LIMIT 10");
    $stmt->execute();
    $ultimos_registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Todos los registros para la tabla completa
    $buscar = $_GET['buscar'] ?? '';
    $filtro_universidad = $_GET['universidad'] ?? '';
    
    $sql = "SELECT * FROM `registro-seminario` WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($buscar)) {
        $sql .= " AND (nombre_completo LIKE ? OR email LIKE ? OR celular LIKE ?)";
        $buscar_param = "%$buscar%";
        $params[] = $buscar_param;
        $params[] = $buscar_param;
        $params[] = $buscar_param;
        $types .= "sss";
    }
    
    if (!empty($filtro_universidad)) {
        $sql .= " AND universidad = ?";
        $params[] = $filtro_universidad;
        $types .= "s";
    }
    
    $sql .= " ORDER BY fecha_registro DESC";
    
    $stmt = $conexion->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $todos_registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Error en panel admin: " . $e->getMessage());
    $error = "Error al cargar datos: " . $e->getMessage();
}

// Mapeo de valores para mostrar mejor
$carreras_nombres = [
    'administracion' => 'Administración de Empresas y Negocios Internacionales',
    'contabilidad' => 'Contabilidad',
    'ing-sistemas' => 'Ing. Sistemas e Informática',
    'ing-forestal' => 'Ing. Forestal y Medio Ambiente',
    'ing-agroindustrial' => 'Ing. Agroindustrial',
    'derecho' => 'Derecho',
    'educacion' => 'Educación',
    'veterinaria' => 'Veterinaria',
    'enfermeria' => 'Enfermería',
    'ecoturismo' => 'Ecoturismo',
    'otro' => 'Otro'
];

$como_entero_nombres = [
    'redes_sociales' => 'Redes Sociales',
    'amigos' => 'Amigos/Compañeros',
    'profesores' => 'Profesores',
    'afiches' => 'Afiches/Publicidad',
    'web_universidad' => 'Página Web Universidad',
    'otro' => 'Otro'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - XVI Aniversario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(45deg, #1976d2, #d84315);
            color: white;
        }
        .stat-card .card-body {
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .stat-card-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }
        .stat-card-warning {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            color: white;
        }
        .stat-card-info {
            background: linear-gradient(45deg, #17a2b8, #6f42c1);
            color: white;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .btn-export {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
        }
        .btn-export:hover {
            background: linear-gradient(45deg, #20c997, #28a745);
            color: white;
        }
        .search-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .qr-tools {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .btn-qr {
            margin: 5px;
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 600;
        }
        .asistencia-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 5px;
            border-left: 4px solid #28a745;
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        .live-indicator {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line me-2"></i>
                Panel de Administración - XVI Aniversario
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>
                    Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_usuario']); ?>
                </span>
                <span class="live-indicator me-3">
                    <i class="fas fa-circle me-1"></i>EN VIVO
                </span>
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt me-1"></i>Ver Sitio
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas Principales de Registro -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $total_registrados; ?></div>
                        <div><i class="fas fa-users me-2"></i>Total Registrados</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-number"><?php echo 300 - $total_registrados; ?></div>
                        <div><i class="fas fa-chair me-2"></i>Cupos Disponibles</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-number"><?php echo count($universidades); ?></div>
                        <div><i class="fas fa-university me-2"></i>Universidades</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-number"><?php echo round(($total_registrados/300)*100); ?>%</div>
                        <div><i class="fas fa-percentage me-2"></i>Capacidad Ocupada</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas de Asistencia y QR -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card-success">
                    <div class="card-body">
                        <div class="stat-number pulse"><?php echo $total_asistieron; ?></div>
                        <div><i class="fas fa-check-circle me-2"></i>Total Asistieron</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card-warning">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $pendientes_asistencia; ?></div>
                        <div><i class="fas fa-clock me-2"></i>Pendientes</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card-info">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $porcentaje_asistencia; ?>%</div>
                        <div><i class="fas fa-percentage me-2"></i>% Asistencia</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card-info">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $total_con_qr; ?></div>
                        <div><i class="fas fa-qrcode me-2"></i>QRs Generados</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Herramientas QR y Check-in -->
        <div class="qr-tools">
            <h5><i class="fas fa-qrcode me-2"></i>Herramientas QR y Control de Asistencia</h5>
            <div class="d-flex gap-2 flex-wrap">
                <a href="generar_qrs.php" class="btn btn-success btn-qr">
                    <i class="fas fa-plus-circle me-1"></i>Generar QRs
                </a>
                <a href="enviar_qrs.php" class="btn btn-info btn-qr">
                    <i class="fas fa-envelope me-1"></i>Enviar QRs
                </a>
                <a href="../checkin.php" class="btn btn-primary btn-qr pulse" target="_blank">
                    <i class="fas fa-camera me-1"></i>Página Check-in
                </a>
                <a href="../ver_qr.php" class="btn btn-secondary btn-qr" target="_blank">
                    <i class="fas fa-eye me-1"></i>Ver QRs
                </a>
                <button class="btn btn-warning btn-qr" onclick="actualizarDatos()">
                    <i class="fas fa-sync me-1"></i>Actualizar
                </button>
            </div>
            <small class="text-muted d-block mt-2">
                <i class="fas fa-info-circle me-1"></i>
                Usa la página de check-in en tablets/móviles durante el evento para escanear QRs y controlar asistencia
            </small>
        </div>

        <?php
        // DEBUG: Mostrar información de depuración SIEMPRE si no hay datos
        if ((empty($universidades) || empty($carreras)) && $total_registrados > 0) {
            echo "<div class='alert alert-info'>";
            echo "<h6>🔍 Información de Depuración:</h6>";
            echo "<p><strong>Total registrados:</strong> $total_registrados</p>";
            echo "<p><strong>Universidades encontradas:</strong> " . count($universidades) . "</p>";
            echo "<p><strong>Carreras encontradas:</strong> " . count($carreras) . "</p>";
            
            // Mostrar datos exactos de la base
            $stmt_debug = $conexion->prepare("SELECT id, nombre_completo, universidad, carrera FROM `registro-seminario` ORDER BY id DESC LIMIT 3");
            $stmt_debug->execute();
            $debug_data = $stmt_debug->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo "<p><strong>Últimos registros en la base:</strong></p>";
            echo "<table class='table table-sm'>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Universidad</th><th>Carrera</th></tr>";
            foreach ($debug_data as $data) {
                echo "<tr>";
                echo "<td>" . $data['id'] . "</td>";
                echo "<td>" . htmlspecialchars($data['nombre_completo']) . "</td>";
                echo "<td><strong>'" . htmlspecialchars($data['universidad']) . "'</strong></td>";
                echo "<td><strong>'" . htmlspecialchars($data['carrera']) . "'</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
        }
        ?>

        <!-- Gráficos -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-university me-2"></i>Registros por Universidad (<?php echo count($universidades); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($universidades)): ?>
                        <div class="chart-container">
                            <canvas id="universidadesChart"></canvas>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-chart-pie fa-3x mb-3"></i>
                            <p>No hay datos para mostrar gráfico</p>
                            <small>Total registrados: <?php echo $total_registrados; ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <!-- DEBUG: Mostrar datos en texto si el gráfico no aparece -->
                        <?php if (!empty($universidades)): ?>
                        <div class="mt-3">
                            <small class="text-muted">Datos encontrados:</small>
                            <ul class="list-unstyled">
                                <?php foreach ($universidades as $uni): ?>
                                <li><i class="fas fa-university me-2"></i><?php echo htmlspecialchars($uni['universidad']); ?>: <strong><?php echo $uni['cantidad']; ?></strong></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-graduation-cap me-2"></i>Registros por Carrera (<?php echo count($carreras); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($carreras)): ?>
                        <div class="chart-container">
                            <canvas id="carrerasChart"></canvas>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-chart-bar fa-3x mb-3"></i>
                            <p>No hay datos para mostrar gráfico</p>
                            <small>Total registrados: <?php echo $total_registrados; ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <!-- DEBUG: Mostrar datos en texto si el gráfico no aparece -->
                        <?php if (!empty($carreras)): ?>
                        <div class="mt-3">
                            <small class="text-muted">Datos encontrados:</small>
                            <ul class="list-unstyled">
                                <?php foreach ($carreras as $carr): ?>
                                <li><i class="fas fa-graduation-cap me-2"></i><?php echo $carreras_nombres[$carr['carrera']] ?? $carr['carrera']; ?>: <strong><?php echo $carr['cantidad']; ?></strong></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asistencia por Hora y Últimas Asistencias -->
        <?php if (!empty($asistencia_por_hora)): ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-clock me-2"></i>Asistencia por Hora</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="asistenciaChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-list me-2"></i>Últimas Asistencias</h5>
                        <span class="badge bg-success"><?php echo count($ultimas_asistencias); ?></span>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <?php if (!empty($ultimas_asistencias)): ?>
                            <?php foreach ($ultimas_asistencias as $asistencia): ?>
                                <div class="asistencia-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($asistencia['nombre_completo']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($asistencia['email']); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <small><i class="fas fa-clock me-1"></i><?php echo date('H:i', strtotime($asistencia['fecha_asistencia'])); ?></small><br>
                                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($asistencia['fecha_asistencia'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-info-circle me-2"></i>
                                Aún no hay asistencias registradas
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Búsqueda y Filtros -->
        <div class="search-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Buscar participante</label>
                    <input type="text" class="form-control" name="buscar" 
                           placeholder="Nombre, email o celular..." 
                           value="<?php echo htmlspecialchars($buscar); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filtrar por universidad</label>
                    <select class="form-select" name="universidad">
                        <option value="">Todas las universidades</option>
                        <?php foreach ($universidades as $uni): ?>
                            <option value="<?php echo htmlspecialchars($uni['universidad']); ?>" 
                                    <?php echo ($filtro_universidad === $uni['universidad']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($uni['universidad']); ?> (<?php echo $uni['cantidad']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Buscar
                    </button>
                    <a href="?" class="btn btn-secondary me-2">
                        <i class="fas fa-times me-1"></i>Limpiar
                    </a>
                    <button type="button" class="btn btn-export" onclick="exportarExcel()">
                        <i class="fas fa-file-excel me-1"></i>Exportar
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla de Registros -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-list me-2"></i>Lista de Participantes (<?php echo count($todos_registros); ?>)</h5>
                <small class="text-muted">Mostrando resultados de búsqueda</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>#</th>
                                <th>Nombre Completo</th>
                                <th>Email</th>
                                <th>Celular</th>
                                <th>Universidad</th>
                                <th>Código</th>
                                <th>Carrera</th>
                                <th>País</th>
                                <th>QR</th>
                                <th>Asistencia</th>
                                <th>Fecha Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todos_registros as $index => $registro): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($registro['nombre_completo']); ?></strong></td>
                                <td><?php echo htmlspecialchars($registro['email']); ?></td>
                                <td><?php echo htmlspecialchars($registro['celular']); ?></td>
                                <td>
                                    <small><?php echo htmlspecialchars($registro['universidad']); ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($registro['codigo_alumno'])): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($registro['codigo_alumno']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo $carreras_nombres[$registro['carrera']] ?? $registro['carrera']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($registro['pais']); ?></td>
                                <td>
                                    <?php if (!empty($registro['qr_token'])): ?>
                                        <a href="../ver_qr.php?id=<?php echo $registro['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-qrcode"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($registro['asistio'] == 1): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>
                                            <?php echo date('H:i', strtotime($registro['fecha_asistencia'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo date('d/m/Y H:i', strtotime($registro['fecha_registro'])); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Estadísticas Adicionales -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-globe me-2"></i>Participantes por País</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($paises as $pais): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo htmlspecialchars($pais['pais']); ?></span>
                                <span class="badge bg-primary"><?php echo $pais['cantidad']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-bullhorn me-2"></i>¿Cómo se enteraron?</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($como_enteraron as $como): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo $como_entero_nombres[$como['como_entero']] ?? $como['como_entero']; ?></span>
                                <span class="badge bg-success"><?php echo $como['cantidad']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Verificar que Chart.js esté cargado
        console.log('Chart.js disponible:', typeof Chart !== 'undefined');
        console.log('Datos de universidades:', <?php echo json_encode($universidades); ?>);
        console.log('Datos de carreras:', <?php echo json_encode($carreras); ?>);
        
        // Gráfico de Universidades - SOLO si hay datos
        <?php if (!empty($universidades)): ?>
        try {
            const ctxUniversidades = document.getElementById('universidadesChart');
            if (ctxUniversidades) {
                const universidadesChart = new Chart(ctxUniversidades.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode(array_column($universidades, 'universidad')); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_column($universidades, 'cantidad')); ?>,
                            backgroundColor: [
                                '#1976d2', '#d84315', '#388e3c', '#f57c00',
                                '#7b1fa2', '#c2185b', '#00796b', '#455a64'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
                console.log('Gráfico de universidades creado:', universidadesChart);
            } else {
                console.error('Canvas universidadesChart no encontrado');
            }
        } catch (error) {
            console.error('Error creando gráfico de universidades:', error);
        }
        <?php endif; ?>

        // Gráfico de Carreras - SOLO si hay datos
        <?php if (!empty($carreras)): ?>
        try {
            const ctxCarreras = document.getElementById('carrerasChart');
            if (ctxCarreras) {
                const carrerasChart = new Chart(ctxCarreras.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode(array_map(function($c) use ($carreras_nombres) { 
                            return $carreras_nombres[$c['carrera']] ?? $c['carrera']; 
                        }, $carreras)); ?>,
                        datasets: [{
                            label: 'Registros',
                            data: <?php echo json_encode(array_column($carreras, 'cantidad')); ?>,
                            backgroundColor: '#1976d2'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    maxRotation: 45
                                }
                            }
                        }
                    }
                });
                console.log('Gráfico de carreras creado:', carrerasChart);
            } else {
                console.error('Canvas carrerasChart no encontrado');
            }
        } catch (error) {
            console.error('Error creando gráfico de carreras:', error);
        }
        <?php endif; ?>