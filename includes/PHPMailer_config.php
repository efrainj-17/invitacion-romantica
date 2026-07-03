<?php
// ============================================
// CONFIGURACIÓN DE PHPMailer
// ============================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ⚠️ RUTAS CORRECTAS PARA TU ESTRUCTURA
require_once __DIR__ . '/src/Exception.php';
require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';

function enviarCorreo($destinatario, $nombre_destino, $asunto, $mensaje_texto, $mensaje_html = '') {
    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP de Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'juanefrain2003@gmail.com';   // 🔴 TU CORREO
        $mail->Password   = 'ppiz tqmc lpvm pdfc';        // 🔴 CONTRASEÑA DE APLICACIÓN
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Remitente y destinatario
        $mail->setFrom('juanefrain2003@gmail.com', 'Invitaciones Románticas');
        $mail->addAddress($destinatario, $nombre_destino);
        $mail->addReplyTo('juanefrain2003@gmail.com', 'Invitaciones Románticas');

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje_html ?: nl2br($mensaje_texto);
        $mail->AltBody = $mensaje_texto;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("❌ Error al enviar correo: " . $mail->ErrorInfo);
        return false;
    }
}
?>