<?php
// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight de CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$recurso = isset($_POST['recurso']) ? trim($_POST['recurso']) : 'Recurso desconocido';
$fecha = isset($_POST['fecha']) ? $_POST['fecha'] : date('d/m/Y H:i:s');

// Validar campos requeridos
if (empty($nombre) || empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nombre y email son requeridos']);
    exit;
}

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

// Configuración SMTP de Gmail
// IMPORTANTE: Necesitas crear una "Contraseña de aplicación" en tu cuenta de Google
// Ve a: https://myaccount.google.com/apppasswords
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_username = 'sanchezespinosa1998@gmail.com'; // Tu email de Gmail
$smtp_password = 'xfay gyxy wbnb yrmt
'; // Contraseña de aplicación de Google (NO tu contraseña normal)
$smtp_from_email = 'sanchezespinosa1998@gmail.com';
$smtp_from_name = 'Formulario Hiperorb';

// Destinatario
$to_email = 'sanchezespinosa1998@gmail.com';
$subject = 'Nueva descarga de recurso: ' . $recurso;

// Crear el cuerpo del mensaje
$message_body = "Nueva descarga de recurso desde Hiperorb\n\n";
$message_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
$message_body .= "Nombre: " . $nombre . "\n";
$message_body .= "Email: " . $email . "\n";
$message_body .= "Recurso descargado: " . $recurso . "\n";
$message_body .= "Fecha: " . $fecha . "\n";
$message_body .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Intentar enviar usando SMTP
try {
    // Verificar si las funciones de socket están disponibles
    if (!function_exists('fsockopen')) {
        throw new Exception('Las funciones de socket no están disponibles. Contacta a tu proveedor de hosting.');
    }
    
    // Crear conexión SMTP
    $smtp_connection = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 30);
    
    if (!$smtp_connection) {
        throw new Exception("No se pudo conectar al servidor SMTP: $errstr ($errno)");
    }
    
    // Leer respuesta inicial
    $response = fgets($smtp_connection, 515);
    
    // Enviar comandos SMTP
    fputs($smtp_connection, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
    $response = fgets($smtp_connection, 515);
    
    // Iniciar TLS
    fputs($smtp_connection, "STARTTLS\r\n");
    $response = fgets($smtp_connection, 515);
    
    // Habilitar cifrado
    stream_socket_enable_crypto($smtp_connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    
    // EHLO después de TLS
    fputs($smtp_connection, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
    $response = fgets($smtp_connection, 515);
    
    // Autenticación
    fputs($smtp_connection, "AUTH LOGIN\r\n");
    $response = fgets($smtp_connection, 515);
    
    fputs($smtp_connection, base64_encode($smtp_username) . "\r\n");
    $response = fgets($smtp_connection, 515);
    
    fputs($smtp_connection, base64_encode($smtp_password) . "\r\n");
    $response = fgets($smtp_connection, 515);
    
    if (strpos($response, '235') === false) {
        throw new Exception('Error de autenticación SMTP. Verifica tu usuario y contraseña.');
    }
    
    // Enviar email
    fputs($smtp_connection, "MAIL FROM: <$smtp_from_email>\r\n");
    $response = fgets($smtp_connection, 515);
    
    fputs($smtp_connection, "RCPT TO: <$to_email>\r\n");
    $response = fgets($smtp_connection, 515);
    
    fputs($smtp_connection, "DATA\r\n");
    $response = fgets($smtp_connection, 515);
    
    // Headers del email
    $email_headers = "From: $smtp_from_name <$smtp_from_email>\r\n";
    $email_headers .= "Reply-To: $email\r\n";
    $email_headers .= "MIME-Version: 1.0\r\n";
    $email_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $email_headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // Enviar contenido
    fputs($smtp_connection, "Subject: $subject\r\n");
    fputs($smtp_connection, $email_headers);
    fputs($smtp_connection, "\r\n");
    fputs($smtp_connection, $message_body);
    fputs($smtp_connection, "\r\n.\r\n");
    
    $response = fgets($smtp_connection, 515);
    
    // Cerrar conexión
    fputs($smtp_connection, "QUIT\r\n");
    fclose($smtp_connection);
    
    if (strpos($response, '250') !== false || strpos($response, '354') !== false) {
        error_log("Email enviado exitosamente a $to_email desde $email");
        
        echo json_encode([
            'success' => true,
            'message' => 'Email enviado correctamente'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('El servidor SMTP rechazó el mensaje: ' . $response);
    }
    
} catch (Exception $e) {
    error_log("Error al enviar email: " . $e->getMessage());
    
    // Intentar método alternativo con mail() nativo como respaldo
    try {
        $headers = "From: $smtp_from_name <$smtp_from_email>\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        $mailSent = @mail($to_email, $subject, $message_body, $headers);
        
        if ($mailSent) {
            echo json_encode([
                'success' => true,
                'message' => 'Email enviado usando método alternativo'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('Ambos métodos fallaron');
        }
    } catch (Exception $e2) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al enviar el email: ' . $e->getMessage(),
            'debug' => 'Verifica la configuración SMTP en send-email.php'
        ], JSON_UNESCAPED_UNICODE);
    }
}
?>
