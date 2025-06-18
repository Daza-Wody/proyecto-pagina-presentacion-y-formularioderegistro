<?php
session_start();

// Credenciales (cámbialas por las que quieras)
$usuario_correcto = "admin_seminario";
$password_correcto = "XVI2025_UNAMAD!";

// Si ya está logueado, redirigir al panel
if (isset($_SESSION['admin_logueado']) && $_SESSION['admin_logueado'] === true) {
    header('Location: index.php');
    exit();
}

$error = '';

// Procesar login
if ($_POST) {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($usuario === $usuario_correcto && $password === $password_correcto) {
        $_SESSION['admin_logueado'] = true;
        $_SESSION['admin_usuario'] = $usuario;
        $_SESSION['admin_login_time'] = time();
        
        header('Location: index.php');
        exit();
    } else {
        $error = 'Usuario o contraseña incorrectos';
        // Log del intento fallido
        error_log("Intento de login fallido desde IP: " . $_SERVER['REMOTE_ADDR'] . " - Usuario: " . $usuario);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1976d2 0%, #d84315 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Arial', sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            background: linear-gradient(45deg, #1976d2, #d84315);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-header h3 {
            margin: 0;
            font-weight: bold;
        }
        
        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(45deg, #1976d2, #d84315);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            padding: 0.75rem 2rem;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: linear-gradient(45deg, #d84315, #1976d2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            color: #6c757d;
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: #1976d2;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .security-note {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #1565c0;
        }
        
        .security-note i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-shield-alt fa-2x mb-2"></i>
            <h3>Panel de Administración</h3>
            <p>XVI Aniversario - UNAMAD</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="usuario">
                        <i class="fas fa-user me-1"></i>Usuario
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               id="usuario" 
                               name="usuario" 
                               placeholder="Ingrese su usuario" 
                               required 
                               autocomplete="username">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock me-1"></i>Contraseña
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Ingrese su contraseña" 
                               required 
                               autocomplete="current-password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="security-note">
                <i class="fas fa-info-circle"></i>
                <strong>Nota de seguridad:</strong> Esta es un área restringida. 
                Solo personal autorizado puede acceder al panel de administración.
            </div>
            
            <div class="footer-text">
                © <?php echo date('Y'); ?> UNAMAD - Sistema de Gestión de Seminarios
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-focus en el campo usuario
        document.getElementById('usuario').focus();
        
        // Efecto de typing en el placeholder
        let isTyping = false;
        document.getElementById('usuario').addEventListener('focus', function() {
            if (!isTyping) {
                isTyping = true;
                this.placeholder = '';
                setTimeout(() => {
                    this.placeholder = 'Ingrese su usuario';
                }, 100);
            }
        });
    </script>
</body>
</html>