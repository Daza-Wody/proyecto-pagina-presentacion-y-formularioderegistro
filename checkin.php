<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in QR - XVI Aniversario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1976d2 0%, #d84315 100%);
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }
        
        .checkin-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            margin: 20px auto;
            max-width: 500px;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #1976d2, #d84315);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        .header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .scanner-section {
            padding: 2rem;
            text-align: center;
        }
        
        .scanner-container {
            position: relative;
            margin: 2rem 0;
            border: 3px solid #1976d2;
            border-radius: 15px;
            overflow: hidden;
            background: #f8f9fa;
        }
        
        #qr-reader {
            width: 100%;
            border: none;
        }
        
        .scanner-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 2px solid #fff;
            border-radius: 10px;
            box-shadow: 0 0 0 999px rgba(0, 0, 0, 0.3);
            pointer-events: none;
        }
        
        .scanner-corners {
            position: absolute;
            width: 20px;
            height: 20px;
            border: 3px solid #d84315;
        }
        
        .corner-tl { top: -3px; left: -3px; border-right: none; border-bottom: none; }
        .corner-tr { top: -3px; right: -3px; border-left: none; border-bottom: none; }
        .corner-bl { bottom: -3px; left: -3px; border-right: none; border-top: none; }
        .corner-br { bottom: -3px; right: -3px; border-left: none; border-top: none; }
        
        .manual-input {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        
        .btn-scan {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: bold;
            margin: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-scan:hover {
            background: linear-gradient(45deg, #20c997, #28a745);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-manual {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
        }
        
        .btn-manual:hover {
            background: linear-gradient(45deg, #fd7e14, #ffc107);
            color: white;
        }
        
        .result-card {
            margin: 1rem 0;
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .success-card {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border-left: 5px solid #28a745;
        }
        
        .error-card {
            background: linear-gradient(135deg, #f8d7da, #f1b0b7);
            border-left: 5px solid #dc3545;
        }
        
        .warning-card {
            background: linear-gradient(135deg, #fff3cd, #fce094);
            border-left: 5px solid #ffc107;
        }
        
        .participant-info {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .stats-bar {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            color: white;
            text-align: center;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .loading {
            display: none;
            text-align: center;
            margin: 1rem 0;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #1976d2;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .checkin-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header {
                padding: 1.5rem 1rem;
            }
            
            .scanner-section {
                padding: 1.5rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="checkin-container">
            <div class="header">
                <i class="fas fa-qrcode fa-2x mb-2"></i>
                <h1>Check-in QR</h1>
                <p>XVI Aniversario - UNAMAD</p>
                <p>Seminario: "Negocios Internacionales, Ya es una Realidad"</p>
            </div>
            
            <div class="scanner-section">
                <div id="stats" class="stats-bar">
                    <i class="fas fa-users me-2"></i>
                    <span id="asistentes-count">0</span> asistentes registrados
                </div>
                
                <!-- Scanner QR -->
                <div class="scanner-container" id="scanner-container">
                    <div id="qr-reader"></div>
                    <div class="scanner-overlay">
                        <div class="scanner-corners corner-tl"></div>
                        <div class="scanner-corners corner-tr"></div>
                        <div class="scanner-corners corner-bl"></div>
                        <div class="scanner-corners corner-br"></div>
                    </div>
                </div>
                
                <!-- Botones de control -->
                <div class="text-center">
                    <button id="start-scanner" class="btn btn-scan pulse">
                        <i class="fas fa-camera me-2"></i>Iniciar Escáner
                    </button>
                    <button id="stop-scanner" class="btn btn-danger" style="display: none;">
                        <i class="fas fa-stop me-2"></i>Detener
                    </button>
                </div>
                
                <!-- Input manual -->
                <div class="manual-input">
                    <h6><i class="fas fa-keyboard me-2"></i>Entrada Manual</h6>
                    <div class="input-group">
                        <input type="text" id="manual-code" class="form-control" 
                               placeholder="Escanea o ingresa el código QR">
                        <button class="btn btn-manual" type="button" onclick="procesarCodigoManual()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <small class="text-muted">
                        También puedes ingresar el código manualmente si no funciona el escáner
                    </small>
                </div>
                
                <!-- Loading -->
                <div id="loading" class="loading">
                    <div class="spinner"></div>
                    <p>Procesando...</p>
                </div>
                
                <!-- Resultado -->
                <div id="result-container"></div>
                
                <!-- Panel de administración -->
                <div class="text-center mt-3">
                    <a href="admin/index.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-cog me-1"></i>Panel Admin
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    
    <script>
        let html5QrcodeScanner = null;
        let isScanning = false;
        
        // Inicializar página
        document.addEventListener('DOMContentLoaded', function() {
            actualizarEstadisticas();
            
            // Escuchar Enter en input manual
            document.getElementById('manual-code').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    procesarCodigoManual();
                }
            });
        });
        
        // Iniciar escáner
        document.getElementById('start-scanner').addEventListener('click', function() {
            if (!isScanning) {
                iniciarEscaner();
            }
        });
        
        // Detener escáner
        document.getElementById('stop-scanner').addEventListener('click', function() {
            if (isScanning) {
                detenerEscaner();
            }
        });
        
        function iniciarEscaner() {
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };
            
            html5QrcodeScanner = new Html5Qrcode("qr-reader");
            
            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    // Usar cámara trasera si está disponible
                    let cameraId = devices[0].id;
                    for (let device of devices) {
                        if (device.label && device.label.toLowerCase().includes('back')) {
                            cameraId = device.id;
                            break;
                        }
                    }
                    
                    html5QrcodeScanner.start(
                        cameraId,
                        config,
                        onScanSuccess,
                        onScanFailure
                    ).then(() => {
                        isScanning = true;
                        document.getElementById('start-scanner').style.display = 'none';
                        document.getElementById('stop-scanner').style.display = 'inline-block';
                        mostrarMensaje('Escáner iniciado. Enfoca el código QR.', 'info');
                    }).catch(err => {
                        console.error('Error al iniciar escáner:', err);
                        mostrarMensaje('Error al acceder a la cámara: ' + err, 'error');
                    });
                } else {
                    mostrarMensaje('No se encontraron cámaras en este dispositivo.', 'error');
                }
            }).catch(err => {
                console.error('Error al obtener cámaras:', err);
                mostrarMensaje('Error al acceder a las cámaras del dispositivo.', 'error');
            });
        }
        
        function detenerEscaner() {
            if (html5QrcodeScanner && isScanning) {
                html5QrcodeScanner.stop().then(() => {
                    isScanning = false;
                    document.getElementById('start-scanner').style.display = 'inline-block';
                    document.getElementById('stop-scanner').style.display = 'none';
                    mostrarMensaje('Escáner detenido.', 'info');
                }).catch(err => {
                    console.error('Error al detener escáner:', err);
                });
            }
        }
        
        function onScanSuccess(decodedText, decodedResult) {
            // Detener escáner automáticamente al escanear
            detenerEscaner();
            
            // Procesar el código escaneado
            document.getElementById('manual-code').value = decodedText;
            procesarCodigo(decodedText);
        }
        
        function onScanFailure(error) {
            // Errores de escaneo continuo - no mostrar
            // console.log('Scan failure:', error);
        }
        
        function procesarCodigoManual() {
            const codigo = document.getElementById('manual-code').value.trim();
            if (codigo) {
                procesarCodigo(codigo);
            } else {
                mostrarMensaje('Por favor ingresa un código QR.', 'error');
            }
        }
        
        function procesarCodigo(codigo) {
            mostrarLoading(true);
            
            // Enviar código al servidor para verificación
            fetch('verificar_asistencia.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'qr_token=' + encodeURIComponent(codigo)
            })
            .then(response => response.json())
            .then(data => {
                mostrarLoading(false);
                mostrarResultado(data);
                actualizarEstadisticas();
                
                // Limpiar input después de 3 segundos
                setTimeout(() => {
                    document.getElementById('manual-code').value = '';
                }, 3000);
            })
            .catch(error => {
                mostrarLoading(false);
                console.error('Error:', error);
                mostrarMensaje('Error de conexión. Intenta nuevamente.', 'error');
            });
        }
        
        function mostrarResultado(data) {
            const container = document.getElementById('result-container');
            let html = '';
            
            if (data.success) {
                if (data.ya_registrado) {
                    // Ya había marcado asistencia antes
                    html = `
                        <div class="card result-card warning-card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    Ya Registrado
                                </h5>
                                <div class="participant-info">
                                    <strong>${data.participante.nombre_completo}</strong><br>
                                    <small class="text-muted">${data.participante.email}</small><br>
                                    <small class="text-muted">${data.participante.universidad}</small>
                                </div>
                                <p class="mb-2">
                                    <i class="fas fa-clock me-2"></i>
                                    Primera asistencia: ${data.participante.fecha_asistencia}
                                </p>
                                <p class="mb-0">Este participante ya había marcado asistencia anteriormente.</p>
                            </div>
                        </div>
                    `;
                } else {
                    // Nuevo registro de asistencia
                    html = `
                        <div class="card result-card success-card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    ¡Asistencia Registrada!
                                </h5>
                                <div class="participant-info">
                                    <strong>${data.participante.nombre_completo}</strong><br>
                                    <small class="text-muted">${data.participante.email}</small><br>
                                    <small class="text-muted">${data.participante.universidad}</small>
                                </div>
                                <p class="mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    Registrado: ${new Date().toLocaleString('es-PE')}
                                </p>
                            </div>
                        </div>
                    `;
                }
            } else {
                // Error o código inválido
                html = `
                    <div class="card result-card error-card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-times-circle text-danger me-2"></i>
                                Código Inválido
                            </h5>
                            <p class="mb-0">${data.message}</p>
                            <small class="text-muted">
                                Verifica que el código QR sea válido o contacta al organizador.
                            </small>
                        </div>
                    </div>
                `;
            }
            
            container.innerHTML = html;
            
            // Auto-limpiar resultado después de 10 segundos
            setTimeout(() => {
                container.innerHTML = '';
            }, 10000);
        }
        
        function mostrarMensaje(mensaje, tipo) {
            const container = document.getElementById('result-container');
            const tipoClass = tipo === 'error' ? 'error-card' : 
                             tipo === 'success' ? 'success-card' : 'warning-card';
            const icono = tipo === 'error' ? 'fas fa-exclamation-triangle' : 
                         tipo === 'success' ? 'fas fa-check-circle' : 'fas fa-info-circle';
            
            container.innerHTML = `
                <div class="card result-card ${tipoClass}">
                    <div class="card-body">
                        <p class="mb-0">
                            <i class="${icono} me-2"></i>
                            ${mensaje}
                        </p>
                    </div>
                </div>
            `;
            
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
        
        function mostrarLoading(mostrar) {
            document.getElementById('loading').style.display = mostrar ? 'block' : 'none';
        }
        
        function actualizarEstadisticas() {
            fetch('verificar_asistencia.php?stats=1')
                .then(response => response.json())
                .then(data => {
                    if (data.asistentes !== undefined) {
                        document.getElementById('asistentes-count').textContent = data.asistentes;
                    }
                })
                .catch(error => {
                    console.error('Error al actualizar estadísticas:', error);
                });
        }
        
        // Actualizar estadísticas cada 30 segundos
        setInterval(actualizarEstadisticas, 30000);
        
        // Prevenir zoom en iOS
        document.addEventListener('gesturestart', function (e) {
            e.preventDefault();
        });
    </script>
</body>
</html>