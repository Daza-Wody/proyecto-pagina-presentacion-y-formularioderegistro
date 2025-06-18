<?php
// Detectar bots de redes sociales ANTES de cualquier otra cosa
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$social_bots = [
    'facebookexternalhit',
    'Facebot',
    'Twitterbot',
    'WhatsApp',
    'LinkedInBot',
    'Slackbot',
    'Telegrambot',
    'Pinterest',
    'Discordbot'
];

$is_social_bot = false;
foreach ($social_bots as $bot) {
    if (stripos($user_agent, $bot) !== false) {
        $is_social_bot = true;
        break;
    }
}

// Si es un bot social, mostrar solo el HTML mínimo con meta tags
if ($is_social_bot) {
    // IMPORTANTE: Cambia esta URL por la URL de tu imagen subida a Imgur o similar 
    $imagen_externa = 'https://i.imgur.com/XPFfex1.jpg'; // <-- CAMBIA ESTO POR TU URL DE IMAGEN 
    $url_actual = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>XVI Aniversario - Carrera de Administración UNAMAD</title>
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo $url_actual; ?>">
        <meta property="og:title" content="XVI Aniversario - Carrera de Administración UNAMAD">
        <meta property="og:description" content="Seminario: Liderazgo y Gestión en la Era Digital. Auditorio de Formación Académica. Certificado gratuito. ¡Inscríbete ahora!">
        <meta property="og:image" content="<?php echo $imagen_externa; ?>">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="600">
        
        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="<?php echo $url_actual; ?>">
        <meta name="twitter:title" content="XVI Aniversario - Carrera de Administración UNAMAD">
        <meta name="twitter:description" content="Seminario: Liderazgo y Gestión en la Era Digital. ¡Inscríbete ahora!">
        <meta name="twitter:image" content="<?php echo $imagen_externa; ?>">
    </head>
    <body>
        <h1>XVI Aniversario - Carrera de Administración</h1>
        <p>Seminario: Liderazgo y Gestión en la Era Digital</p>
        <p>Cargando...</p>
    </body>
    </html>
    <?php
    exit();
}

// CÓDIGO ORIGINAL CONTINÚA AQUÍ
// Incluir archivos de configuración
require_once 'includes/config.php';
require_once 'includes/conexion.php';

// Iniciar sesión para mensajes
session_start();

// Variables para mensajes
$mensaje_exito = '';
$mensaje_error = '';

// Verificar si hay mensaje de éxito en sesión
if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_exito = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']); // Limpiar después de mostrar
}

// Procesar formulario de registro
if ($_POST) {
    // Limpiar y validar datos
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $celular = trim($_POST['celular'] ?? '');
    $pais = $_POST['pais'] ?? '';
    $universidad = $_POST['universidad'] ?? '';
    $otra_universidad = trim($_POST['otra_universidad'] ?? '');
    $codigo_alumno = trim($_POST['codigo_alumno'] ?? '');
    $carrera = $_POST['carrera'] ?? '';
    $como_entero = $_POST['como_entero'] ?? '';
    
    // Determinar el nombre real de la universidad
    $universidad_final = $universidad;
    if ($universidad === 'otra' && !empty($otra_universidad)) {
        $universidad_final = $otra_universidad;
    }
    
    // Validaciones básicas
    $errores = [];
    
    if (empty($nombre_completo)) {
        $errores[] = "El nombre completo es obligatorio";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email es inválido";
    }
    
    if (empty($celular) || !preg_match('/^\d{9}$/', str_replace(' ', '', $celular))) {
        $errores[] = "El celular debe tener 9 dígitos";
    }
    
    if (empty($pais)) {
        $errores[] = "Debe seleccionar un país";
    }
    
    if (empty($universidad)) {
        $errores[] = "Debe seleccionar una universidad";
    }
    
    if ($universidad === 'otra' && empty($otra_universidad)) {
        $errores[] = "Debe especificar el nombre de su universidad";
    }
    
    if ($universidad === 'Universidad Nacional Amazónica de Madre de Dios' && empty($codigo_alumno)) {
        $errores[] = "El código de alumno es obligatorio para estudiantes de UNAMAD";
    }
    
    if (empty($carrera)) {
        $errores[] = "Debe seleccionar una carrera";
    }
    
    if (empty($como_entero)) {
        $errores[] = "Debe indicar cómo se enteró del evento";
    }
    
    // Si no hay errores, guardar en la base de datos
    if (empty($errores)) {
        try {
            // Verificar si el email ya existe
            $stmt_check = $conexion->prepare("SELECT id FROM `registro-seminario` WHERE email = ?");
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $resultado = $stmt_check->get_result();
            
            if ($resultado->num_rows > 0) {
                $mensaje_error = "Este email ya se encuentra registrado.";
            } else {
                // Insertar nuevo registro
                $sql = "INSERT INTO `registro-seminario` (nombre_completo, email, celular, pais, universidad, codigo_alumno, carrera, como_entero) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ssssssss", $nombre_completo, $email, $celular, $pais, $universidad_final, $codigo_alumno, $carrera, $como_entero);
                
                if ($stmt->execute()) {
                    // Guardar mensaje en sesión y redirigir para evitar reenvío
                    $_SESSION['mensaje_exito'] = "¡Registro exitoso! Te esperamos en el seminario. Recibirás un email de confirmación pronto.";
                    
                    // Redirigir a la misma página para limpiar el POST
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $mensaje_error = "Error al procesar el registro. Inténtalo nuevamente.";
                }
                
                $stmt->close();
            }
            
            $stmt_check->close();
            
        } catch (Exception $e) {
            error_log("Error en registro: " . $e->getMessage());
            $mensaje_error = "Error técnico. Por favor, inténtalo más tarde.";
        }
    } else {
        $mensaje_error = implode('<br>', $errores);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XVI Aniversario - Carrera de Administración</title>
    
    <!-- ========== META ETIQUETAS PARA VISTA PREVIA ========== -->
    
    <!-- Meta Tags para SEO básico -->
    <meta name="description" content="XVI Aniversario de la Carrera de Administración UNAMAD - Seminario: Liderazgo y Gestión en la Era Digital. Expositores reconocidos. ¡Inscríbete ahora!">
    <meta name="keywords" content="UNAMAD, Administración, Seminario, Liderazgo, Gestión Digital, Puerto Maldonado, Madre de Dios">
    <meta name="author" content="Universidad Nacional Amazónica de Madre de Dios">
    
    <?php
    // URLs para meta tags
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $currentUrl = $protocol . '://' . $host . $_SERVER['REQUEST_URI'];
    
    // IMPORTANTE: Usa una imagen externa para InfinityFree
    // Opciones:
    // 1. Sube tu imagen a https://imgur.com y pega la URL aquí
    // 2. Usa https://imgbb.com o https://postimages.org
    // 3. Usa Cloudinary o cualquier CDN gratuito
    
    $imageUrl = 'img/img-vista-previa.jpeg'; // <-- CAMBIA ESTO POR TU URL DE IMAGEN EXTERNA
    ?>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $currentUrl; ?>">
    <meta property="og:title" content="XVI Aniversario - Carrera de Administración UNAMAD">
    <meta property="og:description" content="Seminario: Negocios Internacionales, Ya es una Realidad. Auditorio de Formación Académica. Certificado gratuito. ¡Inscríbete ahora!">
    <meta property="og:image" content="<?php echo $imageUrl; ?>">
    <meta property="og:image:secure_url" content="<?php echo $imageUrl; ?>">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="XVI Aniversario Administración UNAMAD - Seminario de Liderazgo">
    <meta property="og:locale" content="es_PE">
    <meta property="og:site_name" content="UNAMAD - Administración">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo $currentUrl; ?>">
    <meta name="twitter:title" content="XVI Aniversario - Carrera de Administración UNAMAD">
    <meta name="twitter:description" content="Seminario: Negocios Internacionales, Ya es una Realidad. ¡Inscríbete ahora!">
    <meta name="twitter:image" content="<?php echo $imageUrl; ?>">
    
    <!-- WhatsApp específico -->
    <meta property="og:image:alt" content="XVI Aniversario Administración UNAMAD - Seminario de Liderazgo">
    <link rel="image_src" href="<?php echo $imageUrl; ?>">
    
    <!-- Datos estructurados para mejor SEO -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Event",
      "name": "XVI Aniversario - Seminario: Negocios Internacionales, Ya es una Realidad",
      "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
      "eventStatus": "https://schema.org/EventScheduled",
      "location": {
        "@type": "Place",
        "name": "Auditorio de Formación Académica - UNAMAD",
        "address": {
          "@type": "PostalAddress",
          "streetAddress": "Av. Dos de Mayo - Centro de Idiomas, Cuarto Piso",
          "addressLocality": "Puerto Maldonado",
          "addressRegion": "Madre de Dios",
          "addressCountry": "PE"
        }
      },
      "image": "<?php echo $imageUrl; ?>",
      "description": "Seminario especializado en liderazgo y gestión empresarial con expertos reconocidos",
      "organizer": {
        "@type": "Organization",
        "name": "Universidad Nacional Amazónica de Madre de Dios - Carrera de Administración",
        "url": "https://www.unamad.edu.pe"
      },
      "offers": {
        "@type": "Offer",
        "url": "<?php echo $currentUrl; ?>",
        "price": "0",
        "priceCurrency": "PEN",
        "availability": "https://schema.org/InStock"
      }
    }
    </script>
    
    <!-- ========== FIN DE META ETIQUETAS ========== -->
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="img/img-logo-admin.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="img/img-logo-admin.jpg">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), 
                        url('img/img-background.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
        }

        /* Header y Navegación */
        .header {
            background: #ffffff;
            backdrop-filter: blur(10px);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: bold;
            color: #1565c0;
        }

        .logo img {
            height: 60px;
            width: auto;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #1565c0;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #d84315;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), 
                        url(img/img-background.jpg);
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }

        section[id] {
            scroll-margin-top: 100px;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .btn-inscribir {
            background: linear-gradient(45deg, #1976d2, #d84315);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(25, 118, 210, 0.4);
        }

        .btn-inscribir:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(25, 118, 210, 0.6);
            background: linear-gradient(45deg, #d84315, #1976d2);
            animation: brillo 0.6s ease-in-out;
        }

        @keyframes brillo {
            0%, 100% { box-shadow: 0 8px 25px rgba(25, 118, 210, 0.6); }
            50% { box-shadow: 0 8px 35px rgba(216, 67, 21, 0.8); }
        }

        /* Sección Seminario */
        #seminario {
            background: #ffffff;
            padding: 4rem 0;
            margin: 0;
            width: 100vw;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
        }

        #seminario .seminario-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        #seminario h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #1565c0;
        }

        #seminario p {
            color: #333;
        }

        /* Lugar del Evento */
        .lugar-evento {
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
            padding: 4rem 0;
            margin: 0;
            width: 100vw;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
        }

        .lugar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .lugar-info h2 {
            font-size: 2.5rem;
            color: #1565c0;
            margin-bottom: 1rem;
        }

        .lugar-info h3 {
            font-size: 1.8rem;
            color: #d84315;
            margin-bottom: 1.5rem;
        }

        .lugar-info p {
            color: #333;
            line-height: 1.8;
            margin-bottom: 1.5rem;
            text-align: justify;
        }

        .lugar-detalles {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }

        .lugar-detalles p {
            margin: 0.5rem 0;
            font-size: 1rem;
        }

        .lugar-imagen {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .lugar-imagen img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .lugar-imagen:hover img {
            transform: scale(1.05);
        }

        .imagen-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 1rem;
            text-align: center;
            font-size: 0.9rem;
        }

        /* Secciones con overlay negro */
        .section {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 20px;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .section h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #ffffff;
        }

        .section p {
            color: #ffffff;
        }

        /* Expositores */
        .expositores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .expositor {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            transition: transform 0.3s;
            backdrop-filter: blur(10px);
        }

        .expositor:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.3);
        }

        .expositor img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
        }

        .expositor h3 {
            color: #1565c0;
        }

        .expositor p {
            color: #333;
        }

        /* Cronograma */
        #cronograma {
            background: rgba(0, 0, 0, 0.60);
            color: white;
            margin: 2rem auto;
            max-width: 1200px;
            border-radius: 20px;
            backdrop-filter: blur(15px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            position: relative;
            width: calc(100% - 4rem);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        #cronograma .section {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            background: transparent;
            box-shadow: none;
        }

        #cronograma h2 {
            color: #ffffff;
        }

        .cronograma-item {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            margin: 1rem 0;
            padding: 1.5rem;
            border-radius: 15px;
            border-left: 4px solid #d84315;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .cronograma-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.2);
        }

        .cronograma-item h3 {
            color: #ffab91;
            margin-bottom: 0.5rem;
        }

        .cronograma-item p {
            color: #ffffff;
        }

        /* Patrocinadores */
        .patrocinadores {
            background: rgba(0, 0, 0, 0.60);
            padding: 2rem 0;
            overflow: hidden;
        }

        .patrocinadores-scroll {
            display: flex;
            animation: scroll 40s linear infinite;
            gap: 3rem;
        }

        .patrocinador {
            min-width: 250px;
            height: 120px;
            background: #ffffff;
            margin: 0 2rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid #e0e0e0;
            flex-shrink: 0;
        }

        .patrocinador img {
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
        }

        @keyframes scroll {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        /* Avisos del formulario */
        .avisos-registro {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .aviso-importante {
            color: #0056b3;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .aviso-ojo {
            color: #d84315;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1976d2;
            box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
        }

        /* Campos con error */
        .campo-error {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }

        .btn-submit {
            background: linear-gradient(45deg, #1976d2, #d84315);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-submit:hover:not(:disabled) {
            background: linear-gradient(45deg, #d84315, #1976d2);
            transform: translateY(-1px);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Footer */
        .footer {
            background: #ffffff;
            color: #333;
            text-align: center;
            padding: 2rem;
            width: 100vw;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            margin-top: 2rem;
        }

        .footer h3, .footer p {
            color: #333;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .footer-info {
            flex: 1;
            text-align: center;
        }

        .footer-logos {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .footer-logo {
            height: 80px;
            width: auto;
        }

        /* Mensajes */
        .mensaje-exito {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
            text-align: center;
            border: 1px solid #c3e6cb;
        }

        .mensaje-error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
            text-align: center;
            border: 1px solid #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .section {
                padding: 2rem 1rem;
            }

            .lugar-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-logos {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav">
            <div class="logo">
                <img src="img/img-logo-admin.jpg" alt="Logo Administración">
                UNAMAD - ANI
            </div>
            <ul class="nav-links">
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#seminario">Seminario</a></li>
                <li><a href="#lugar">Lugar</a></li>
                <li><a href="#expositores">Expositores</a></li>
                <li><a href="#cronograma">Cronograma</a></li>
                <li><a href="#contacto">Contacto</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section id="inicio" class="hero">
        <div class="hero-content">
            <h1>XVI Aniversario</h1>
            <p>Carrera Profesional de Administración de Empresas y Negocios Internacionales</p>
            <p>Seminario: "Negocios Internacionales, Ya es una Realidad"</p>
            <a href="#" class="btn-inscribir" onclick="document.getElementById('modalRegistro').style.display='block'; return false;">¡Inscríbete Ahora!</a>
        </div>
    </section>

    <!-- Mensajes -->
    <?php if (!empty($mensaje_exito)): ?>
        <div class="mensaje-exito">
            <?php echo $mensaje_exito; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($mensaje_error)): ?>
        <div class="mensaje-error">
            <?php echo $mensaje_error; ?>
        </div>
    <?php endif; ?>

    <!-- Presentación del Seminario -->
    <section id="seminario">
        <div class="seminario-content">
            <h2>Sobre el Seminario</h2>
            <p style="text-align: center; font-size: 1.1rem; max-width: 800px; margin: 0 auto;">
                En conmemoración del XVI aniversario de nuestra carrera, presentamos un seminario especializado 
                en liderazgo y gestión empresarial adaptada a los desafíos del siglo XXI. Contaremos con 
                expertos reconocidos que compartirán sus experiencias y conocimientos sobre las últimas 
                tendencias en administración de empresas y negocios internacionales.
            </p>
        </div>
    </section>

    <!-- Lugar del Evento -->
    <section id="lugar" class="lugar-evento">
        <div class="lugar-content">
            <div class="lugar-info">
                <h2>Lugar del Evento</h2>
                <h3>Auditorio de Formacion Academica </h3>
                <p>
                    El seminario se llevará a cabo en el moderno Auditorio "Centro de Formacion Academica", 
                    ubicado en el centro de idomas, de la Universidad Nacional Amazónica de Madre de Dios. 
                    Este espacio cuenta con capacidad para 300 personas, equipado con tecnología de última 
                    generación, sistema de sonido profesional, aire acondicionado y todas las comodidades 
                    necesarias para garantizar una experiencia excepcional durante el evento.
                </p>
                <div class="lugar-detalles">
                    <p><strong>📍 Dirección:</strong> Av. Dos de Mayo - Centro de Idiomas</p>
                    <p><strong>🏛️ Edificio:</strong> Séptimo Piso </p>
                    <p><strong>👥 Capacidad:</strong> 300 personas</p>
                    <p><strong>🅿️ Estacionamiento:</strong> Disponible para participantes</p>
                </div>
            </div>
            <div class="lugar-imagen">
                <img src="img/img-centro-idiomas.jpg" alt="Auditorio Mg. Hugo Deza Linares">
                <p class="imagen-caption">Vista panorámica del Auditorio de Formacion Academica</p>
            </div>
        </div>
    </section>

    <!-- Expositores -->
    <section id="expositores" class="section">
        <h2>Nuestros Expositores</h2>
        <div class="expositores-grid">
            <div class="expositor">
                <img src="img/img-doc-holguin.jpg" alt="Dr. María González">
                <h3>Dr. Guido Holguin</h3>
                <p><strong>Gestión Estratégica</strong></p>
                <p>Licenciado en Administración de Empresas y egresado del Doctorado en Administración por la Universidad Nacional San Antonio Abad del Cusco, cuenta con más de una década de experiencia en el sector público y nueve años como docente universitario. Ha participado como ponente en conferencias internacionales sobre logística y comercio internacional.</p>
            </div>
            <div class="expositor">
                <img src="img/img-2.png" alt="Mg. Carlos Ruiz">
                <h3>Dr. Robert Chavez</h3>
                <p><strong>Marketing y Comercio Internacional</strong></p>
                <p>Administrador de Empresas, candidato a Doctor y Magíster en Marketing por la UNFV. Especialista en Comercio Internacional, Aduanas y Logística. Con experiencia en SUNAT, ADEX, SNI y empresas del sector exportador. Ponente internacional en varios países de Latinoamérica y docente en universidades como PUCP, UNMSM y USTA (Colombia).</p>
            </div>
            <div class="expositor">
                <img src="img/img-pineda.jpeg" alt="Lic. Ana Torres">
                <h3>Dr. Julio Pineda</h3>
                <p><strong>Intendente Aduanas Puerto Maldonado</strong></p>
                <p>Abogado por la UNA-Puno, con Maestría en Gerencia Pública y Derecho Administrativo por la misma universidad. Cuenta con especialización en Tributación por la Universidad ESAN, y en Gestión y Liderazgo para la Supervisión y Control en Aduanas por el Banco Interamericano de Desarrollo (BID-INDES).</p>
            </div>
            <div class="expositor">
                <img src="img/img-rosmery-alias.jpeg" alt="Dr. Roberto Mendoza">
                <h3>Tec. Rosmery Mamany</h3>
                <p><strong>Aduanas</strong></p>
                <p>Especialista Aduanera de la empresa Esplendor, Agencia de Aduanas en Iñapari</p>
            </div>
            <div class="expositor">
                <img src="img/logo-patrocinador-badsof.jpg" alt="Mg. Patricia Silva">
                <h3>Jerry Melendez & Christian Huaman</h3>
                <p>Nombre Comercial:</p>
                <p><strong>BADSOF SAC.</strong></p>
            </div>
            <div class="expositor">
                <img src="img/logo-buyu.jpg" alt="Ing. Luis Fernández">
                <h3>Jose Huayllino & Saili Huayllino</h3>
                <p>Nombre Comercial:</p>
                <p><strong>BUYU</strong></p>
            </div>
        </div>
    </section>

    <!-- Cronograma -->
    <section id="cronograma">
        <div class="section">
            <h2>Cronograma del Evento</h2>
            <div class="cronograma-item">
                <h3>8:00 AM - Registro y Acreditación</h3>
                <p>Recepción de participantes y entrega de materiales</p>
            </div>
            <div class="cronograma-item">
                <h3>9:00 AM - Ceremonia de Apertura</h3>
                <p>Palabras de bienvenida del Decano y autoridades universitarias</p>
            </div>
            <div class="cronograma-item">
                <h3>9:10 AM - La SUNAT y los Beneficios de los Regimenes Aduaneros </h3>
                <p><strong>Dr. Julio Pineda :</strong> "Intendente de la Aduana de Puerto Maldonado"</p>
            </div>
            <div class="cronograma-item">
                <h3>9:45 AM - El Puerto de Chancay</h3>
                <p><strong>Mg. Guido Holguin :</strong>"Docente de la Escuela de Administraion y Negocios Internacionales"</p>
            </div>
            <div class="cronograma-item">
                <h3>10:30 AM - Operatvidad de una Agencia de Aduanas</h3>
                <p><strong>Expositora Rosmery Mamani :</strong> "Especialista de la oficina Esplendor, Oficina Iñapari"</p>
            </div>
            <div class="cronograma-item">
                <h3>11:15 AM - Marcas colectivas para la internacionalización de las pymes</h3>
                <p><strong>Expositor: Doctor Robert Chávez.</strong> </p>
            </div>
            <div class="cronograma-item">
                <h3>12:00 PM - Presentacion de Egresados y Emprendedores</h3>
                <p>Networking y confraternidad</p>
            </div>
            
        </div>
    </section>

    <!-- Patrocinadores -->
    <section class="patrocinadores">
        <h2 style="text-align: center; margin-bottom: 2rem; color: white;">Nuestros Patrocinadores</h2>
        <div class="patrocinadores-scroll">
            <div class="patrocinador"><img src="img/img-acreditada.jpg" alt="Patrocinador 1"></div>
            <div class="patrocinador"><img src="img/logo-buyu.jpg" alt="Patrocinador 2"></div>
            <div class="patrocinador"><img src="img/logo-cff-administracion.jpg" alt="Patrocinador 3"></div>
            <div class="patrocinador"><img src="img/logo-patrocinador-badsof.jpg" alt="Patrocinador 4"></div>
            <div class="patrocinador"><img src="img/img-unamad-oficial.png" alt="Patrocinador 5"></div>
            <div class="patrocinador"><img src="img/img-25-plata.png" alt="Patrocinador 6"></div>
        </div>
    </section>

    <!-- Modal de Registro -->
    <div id="modalRegistro" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalRegistro').style.display='none'">&times;</span>
            <h2>Registro al Seminario</h2>
            
            <!-- Avisos importantes -->
            <div class="avisos-registro">
                <p class="aviso-importante">📝 Escribir bien sus datos de acuerdo al DNI para su certificado</p>
                <p class="aviso-ojo">⚠️ OJO: Solo se dará certificado a los participantes con mayor asistencia</p>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo:</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" 
                           value="<?php echo htmlspecialchars($nombre_completo ?? ''); ?>"
                           placeholder="Ingrese sus nombres y apellidos completos" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Correo Electrónico:</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                           placeholder="ejemplo@correo.com" 
                           oninput="validarEmailTiempoReal();" required>
                    <div id="error_email" style="color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem; display: none;"></div>
                </div>
                
                <div class="form-group">
                    <label for="celular">Celular:</label>
                    <input type="tel" id="celular" name="celular" 
                           value="<?php echo htmlspecialchars($celular ?? ''); ?>"
                           placeholder="999999999" maxlength="9" 
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                </div>
                
                <div class="form-group">
                    <label for="pais">País:</label>
                    <select id="pais" name="pais" required>
                        <option value="">Seleccionar país</option>
                        <option value="Peru" <?php echo ($pais ?? '') === 'Peru' ? 'selected' : ''; ?>>Perú</option>
                        <option value="Brasil" <?php echo ($pais ?? '') === 'Brasil' ? 'selected' : ''; ?>>Brasil</option>
                        <option value="Bolivia" <?php echo ($pais ?? '') === 'Bolivia' ? 'selected' : ''; ?>>Bolivia</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="universidad">Universidad:</label>
                    <select id="universidad" name="universidad" required onchange="
                        var universidad = this.value;
                        var divCodigo = document.getElementById('codigo_alumno_div');
                        var divOtra = document.getElementById('otra_universidad_div');
                        var inputCodigo = document.getElementById('codigo_alumno');
                        var inputOtra = document.getElementById('otra_universidad');
                        
                        // Ocultar ambos campos
                        divCodigo.style.display = 'none';
                        divOtra.style.display = 'none';
                        inputCodigo.removeAttribute('required');
                        inputOtra.removeAttribute('required');
                        inputCodigo.value = '';
                        inputOtra.value = '';
                        
                        // Mostrar según selección
                        if (universidad === 'Universidad Nacional Amazónica de Madre de Dios') {
                            divCodigo.style.display = 'block';
                            inputCodigo.setAttribute('required', 'required');
                        } else if (universidad === 'otra') {
                            divOtra.style.display = 'block';
                            inputOtra.setAttribute('required', 'required');
                        }
                    ">
                        <option value="">Seleccionar universidad</option>
                        <option value="Universidad Nacional Amazónica de Madre de Dios" <?php echo ($universidad ?? '') === 'Universidad Nacional Amazónica de Madre de Dios' ? 'selected' : ''; ?>>Universidad Nacional Amazónica de Madre de Dios</option>
                        <option value="Universidad Nacional de San Antonio Abad del Cusco" <?php echo ($universidad ?? '') === 'Universidad Nacional de San Antonio Abad del Cusco' ? 'selected' : ''; ?>>Universidad Nacional de San Antonio Abad del Cusco</option>
                        <option value="Universidad Andina del Cusco" <?php echo ($universidad ?? '') === 'Universidad Andina del Cusco' ? 'selected' : ''; ?>>Universidad Andina del Cusco</option>
                        <option value="otra" <?php echo ($universidad ?? '') === 'otra' ? 'selected' : ''; ?>>Otra</option>
                    </select>
                </div>
                
                <!-- Campo para otra universidad -->
                <div class="form-group" id="otra_universidad_div" style="display: <?php echo ($universidad ?? '') === 'otra' ? 'block' : 'none'; ?>;">
                    <label for="otra_universidad">Especifique su universidad:</label>
                    <input type="text" id="otra_universidad" name="otra_universidad" 
                           value="<?php echo htmlspecialchars($otra_universidad ?? ''); ?>"
                           placeholder="Nombre de su universidad"
                           <?php echo ($universidad ?? '') === 'otra' ? 'required' : ''; ?>>
                </div>
                
                <!-- Campo código de alumno -->
                <div class="form-group" id="codigo_alumno_div" style="display: <?php echo ($universidad ?? '') === 'Universidad Nacional Amazónica de Madre de Dios' ? 'block' : 'none'; ?>;">
                    <label for="codigo_alumno">Código de Alumno:</label>
                    <input type="text" id="codigo_alumno" name="codigo_alumno" 
                           value="<?php echo htmlspecialchars($codigo_alumno ?? ''); ?>"
                           placeholder="Ej: 16221031" maxlength="8"
                           oninput="this.value = this.value.replace(/[^0-9]/g, ''); validarCodigoTiempoReal();"
                           <?php echo ($universidad ?? '') === 'Universidad Nacional Amazónica de Madre de Dios' ? 'required' : ''; ?>>
                    <div id="error_codigo" style="color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem; display: none;"></div>
                </div>
                
                <div class="form-group">
                    <label for="carrera">Carrera:</label>
                    <select id="carrera" name="carrera" required>
                        <option value="">Seleccionar carrera</option>
                        <option value="administracion" <?php echo ($carrera ?? '') === 'administracion' ? 'selected' : ''; ?>>Administración de Empresas y Negocios Internacionales</option>
                        <option value="contabilidad" <?php echo ($carrera ?? '') === 'contabilidad' ? 'selected' : ''; ?>>Contabilidad</option>
                        <option value="ing-sistemas" <?php echo ($carrera ?? '') === 'ing-sistemas' ? 'selected' : ''; ?>>Ing. Sistemas e Informática</option>
                        <option value="ing-forestal" <?php echo ($carrera ?? '') === 'ing-forestal' ? 'selected' : ''; ?>>Ing. Forestal y Medio Ambiente</option>
                        <option value="ing-agroindustrial" <?php echo ($carrera ?? '') === 'ing-agroindustrial' ? 'selected' : ''; ?>>Ing. Agroindustrial</option>
                        <option value="derecho" <?php echo ($carrera ?? '') === 'derecho' ? 'selected' : ''; ?>>Derecho</option>
                        <option value="educacion" <?php echo ($carrera ?? '') === 'educacion' ? 'selected' : ''; ?>>Educación</option>
                        <option value="veterinaria" <?php echo ($carrera ?? '') === 'veterinaria' ? 'selected' : ''; ?>>Veterinaria</option>
                        <option value="enfermeria" <?php echo ($carrera ?? '') === 'enfermeria' ? 'selected' : ''; ?>>Enfermería</option>
                        <option value="ecoturismo" <?php echo ($carrera ?? '') === 'ecoturismo' ? 'selected' : ''; ?>>Ecoturismo</option>
                        <option value="otro" <?php echo ($carrera ?? '') === 'otro' ? 'selected' : ''; ?>>Otro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="como_entero">¿Cómo te enteraste del evento?</label>
                    <select id="como_entero" name="como_entero" required>
                        <option value="">Seleccionar opción</option>
                        <option value="redes_sociales" <?php echo ($como_entero ?? '') === 'redes_sociales' ? 'selected' : ''; ?>>Redes Sociales</option>
                        <option value="amigos" <?php echo ($como_entero ?? '') === 'amigos' ? 'selected' : ''; ?>>Amigos/Compañeros</option>
                        <option value="profesores" <?php echo ($como_entero ?? '') === 'profesores' ? 'selected' : ''; ?>>Profesores</option>
                        <option value="afiches" <?php echo ($como_entero ?? '') === 'afiches' ? 'selected' : ''; ?>>Afiches/Publicidad</option>
                        <option value="web_universidad" <?php echo ($como_entero ?? '') === 'web_universidad' ? 'selected' : ''; ?>>Página Web de la Universidad</option>
                        <option value="otro" <?php echo ($como_entero ?? '') === 'otro' ? 'selected' : ''; ?>>Otro</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit" id="btn_enviar">Registrarme</button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer id="contacto" class="footer">
        <div class="footer-content">
            <img src="img/img-unamad-oficial.png" alt="Logo UNAMAD" class="footer-logo">
            <div class="footer-info">
                <h3>Universidad Nacional Amazónica de Madre de Dios</h3>
                <p>Carrera Profesional de Administración de Empresas y Negocios Internacionales</p>
                <p>Av. Jorge Chávez 1160, Puerto Maldonado - Madre de Dios</p>
                <p>Whatsap: +51 975 844 881 </p>
                <p style="margin-top: 1rem; font-size: 0.9rem;">
                    Licenciada por SUNEDU - Resolución del Consejo Directivo N° 147-2017-SUNEDU/CD
                </p>
            </div>
            <img src="img/img-25-plata.png" alt="Logo SUNEDU" class="footer-logo">
        </div>
    </footer>

    <script>
        // Variables para controlar validación
        let emailValido = false;
        let codigoValido = true; // Por defecto true porque puede no ser necesario

        // Validación de email en tiempo real
        function validarEmailTiempoReal() {
            const emailInput = document.getElementById('email');
            const errorDiv = document.getElementById('error_email');
            const btnEnviar = document.getElementById('btn_enviar');
            const email = emailInput.value.trim();
            
            if (email === '') {
                errorDiv.style.display = 'none';
                emailValido = false;
                actualizarBotonEnviar();
                return;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                errorDiv.textContent = '❌ Email inválido. Ejemplo: usuario@correo.com';
                errorDiv.style.display = 'block';
                emailInput.style.borderColor = '#dc3545';
                emailValido = false;
            } else {
                errorDiv.style.display = 'none';
                emailInput.style.borderColor = '#28a745';
                emailValido = true;
            }
            
            actualizarBotonEnviar();
        }

        // Validación de código de alumno en tiempo real
        function validarCodigoTiempoReal() {
            const codigoInput = document.getElementById('codigo_alumno');
            const errorDiv = document.getElementById('error_codigo');
            const codigoDiv = document.getElementById('codigo_alumno_div');
            
            // Solo validar si el campo está visible
            if (codigoDiv.style.display === 'none') {
                codigoValido = true;
                actualizarBotonEnviar();
                return;
            }
            
            const codigo = codigoInput.value.trim();
            
            if (codigo === '') {
                errorDiv.style.display = 'none';
                codigoValido = false;
                actualizarBotonEnviar();
                return;
            }
            
            if (codigo.length !== 8) {
                errorDiv.textContent = '❌ El código debe tener exactamente 8 dígitos. Ejemplo: 16221031';
                errorDiv.style.display = 'block';
                codigoInput.style.borderColor = '#dc3545';
                codigoValido = false;
            } else {
                errorDiv.style.display = 'none';
                codigoInput.style.borderColor = '#28a745';
                codigoValido = true;
            }
            
            actualizarBotonEnviar();
        }

        // Actualizar estado del botón de enviar
        function actualizarBotonEnviar() {
            const btnEnviar = document.getElementById('btn_enviar');
            const universidad = document.getElementById('universidad').value;
            
            // Verificar si código es requerido
            const codigoRequerido = (universidad === 'Universidad Nacional Amazónica de Madre de Dios');
            
            // El formulario es válido si:
            // 1. Email es válido
            // 2. Si el código es requerido, debe ser válido también
            const formularioValido = emailValido && (codigoRequerido ? codigoValido : true);
            
            if (formularioValido) {
                btnEnviar.disabled = false;
                btnEnviar.style.opacity = '1';
                btnEnviar.textContent = 'Registrarme';
            } else {
                btnEnviar.disabled = true;
                btnEnviar.style.opacity = '0.5';
                btnEnviar.textContent = 'Complete los datos correctamente';
            }
        }

        // Scroll suave para navegación
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                
                // Evitar error si targetId es solo '#'
                if (targetId === '#' || !targetId) {
                    return;
                }
                
                const target = document.querySelector(targetId);
                if (target) {
                    const headerOffset = 100;
                    const elementPosition = target.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('modalRegistro');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Validar al cargar la página si hay datos previos
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('email').value) {
                validarEmailTiempoReal();
            }
            if (document.getElementById('codigo_alumno').value) {
                validarCodigoTiempoReal();
            }
        });
    </script>
</body>
</html>