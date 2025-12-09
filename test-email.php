<?php
/**
 * Script de prueba para verificar que PHP y la función mail() funcionan correctamente
 * Accede a este archivo desde el navegador para probar
 */

echo "<h1>Prueba de Configuración PHP</h1>";

// Verificar PHP
echo "<h2>1. Versión de PHP:</h2>";
echo "<p>PHP " . phpversion() . "</p>";

// Verificar función mail()
echo "<h2>2. Función mail():</h2>";
if (function_exists('mail')) {
    echo "<p style='color: green;'>✅ La función mail() está disponible</p>";
} else {
    echo "<p style='color: red;'>❌ La función mail() NO está disponible</p>";
}

// Verificar configuración de mail
echo "<h2>3. Configuración de mail:</h2>";
$mailConfig = ini_get('sendmail_path');
if ($mailConfig) {
    echo "<p>sendmail_path: " . $mailConfig . "</p>";
} else {
    echo "<p style='color: orange;'>⚠️ sendmail_path no está configurado</p>";
}

// Probar envío de email
echo "<h2>4. Prueba de envío:</h2>";
if (isset($_GET['test'])) {
    $to = 'sanchezespinosa1998@gmail.com';
    $subject = 'Prueba de email desde PHP';
    $message = 'Este es un email de prueba para verificar que PHP funciona correctamente.';
    $headers = "From: prueba@hiperorb.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    $result = @mail($to, $subject, $message, $headers);
    
    if ($result) {
        echo "<p style='color: green;'>✅ Email de prueba enviado. Revisa tu correo (incluyendo spam).</p>";
    } else {
        echo "<p style='color: red;'>❌ Error al enviar email de prueba.</p>";
        $error = error_get_last();
        if ($error) {
            echo "<p>Error: " . htmlspecialchars($error['message']) . "</p>";
        }
    }
} else {
    echo "<p><a href='?test=1' style='padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Enviar Email de Prueba</a></p>";
}

// Información del servidor
echo "<h2>5. Información del servidor:</h2>";
echo "<pre>";
echo "SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'No disponible') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'No disponible') . "\n";
echo "</pre>";

echo "<hr>";
echo "<p><strong>Nota:</strong> Si la función mail() no funciona, es posible que necesites configurar SMTP en tu servidor o usar un servicio externo.</p>";
?>

