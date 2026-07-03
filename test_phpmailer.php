<?php
require_once 'includes/PHPMailer_config.php';
require_once 'config/correo_config.php';

echo "<h2>🧪 Probando PHPMailer</h2>";

echo "<p>📁 Ruta de PHPMailer: " . __DIR__ . "/includes/src/</p>";

// Verificar que los archivos existen
$archivos = [
    'includes/src/PHPMailer.php',
    'includes/src/SMTP.php',
    'includes/src/Exception.php'
];

foreach($archivos as $archivo) {
    if(file_exists($archivo)) {
        echo "<p style='color:green;'>✅ $archivo existe</p>";
    } else {
        echo "<p style='color:red;'>❌ $archivo NO existe</p>";
    }
}

echo "<hr>";

$correo_enviado = enviarCorreo(
    CORREO_DESTINO,
    NOMBRE_DESTINO,
    "🧪 Prueba de correo",
    "Este es un mensaje de prueba de PHPMailer.\n\nSi ves esto, ¡funciona! 🎉",
    "<h1>🧪 Prueba</h1><p>Este es un mensaje de prueba.</p><p>Si ves esto, ¡funciona! 🎉</p>"
);

if($correo_enviado) {
    echo "<p style='color:green;font-size:1.5em;'>✅ Correo enviado correctamente. Revisa tu bandeja.</p>";
} else {
    echo "<p style='color:red;font-size:1.5em;'>❌ Error al enviar correo. Revisa la configuración.</p>";
}
?>