<?php
session_start();
require_once 'config/database.php';
require_once 'includes/PHPMailer_config.php';
require_once 'config/correo_config.php';

// ============================================
// RECIBIR DATOS
// ============================================
if(!isset($_POST['respuesta']) || !isset($_POST['usuario_id']) || !isset($_POST['invitacion_id'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

$respuesta = $_POST['respuesta'];
$mensaje = trim($_POST['mensaje'] ?? '');
$usuario_id = intval($_POST['usuario_id']);
$invitacion_id = intval($_POST['invitacion_id']);

if(!in_array($respuesta, ['aceptada', 'rechazada'])) {
    echo json_encode(['success' => false, 'error' => 'Respuesta no válida']);
    exit();
}

// ============================================
// GUARDAR EN BASE DE DATOS
// ============================================
try {
    $check = $conn->prepare("SELECT id FROM respuestas WHERE usuario_id = ? AND invitacion_id = ?");
    $check->bind_param("ii", $usuario_id, $invitacion_id);
    $check->execute();
    $existe = $check->get_result()->num_rows > 0;
    
    if($existe) {
        $stmt = $conn->prepare("UPDATE respuestas SET respuesta = ?, mensaje_adicional = ? WHERE usuario_id = ? AND invitacion_id = ?");
        $stmt->bind_param("ssii", $respuesta, $mensaje, $usuario_id, $invitacion_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO respuestas (usuario_id, invitacion_id, respuesta, mensaje_adicional) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $usuario_id, $invitacion_id, $respuesta, $mensaje);
    }
    
    if($stmt->execute()) {
        // Obtener datos del usuario
        $user_stmt = $conn->prepare("SELECT nombre, email FROM usuarios WHERE id = ?");
        $user_stmt->bind_param("i", $usuario_id);
        $user_stmt->execute();
        $usuario = $user_stmt->get_result()->fetch_assoc();
        
        $inv_stmt = $conn->prepare("SELECT * FROM invitacion WHERE id = ?");
        $inv_stmt->bind_param("i", $invitacion_id);
        $inv_stmt->execute();
        $invitacion = $inv_stmt->get_result()->fetch_assoc();
        
        if($usuario && $invitacion) {
            // Construir mensaje
            $asunto = ($respuesta == 'aceptada' ? '✅ ¡ACEPTÓ!' : '❌ Rechazó') . ' - ' . $usuario['nombre'];
            
            $mensaje_texto = "
========================================
💌 CONFIRMACIÓN DE INVITACIÓN 💌
========================================

👤 Novia: " . $usuario['nombre'] . "
📧 Email: " . $usuario['email'] . "

❤️ Respuesta: " . ($respuesta == 'aceptada' ? '✅ ACEPTÓ CON AMOR' : '❌ RECHAZÓ') . "

📝 Mensaje: " . ($mensaje ? $mensaje : 'No escribió mensaje') . "

========================================
📊 DETALLES DEL EVENTO
========================================
⚽ Evento: " . $invitacion['evento'] . "
📅 Fecha: " . date('d/m/Y', strtotime($invitacion['fecha'])) . "
⏰ Hora: " . date('h:i A', strtotime($invitacion['fecha'])) . "
📍 Lugar: " . $invitacion['lugar'] . "
========================================
🌟 ¡El amor siempre gana! 🌟
";
            
            // Enviar correo (intentar)
            $correo_enviado = enviarCorreo(
                CORREO_DESTINO,
                NOMBRE_DESTINO,
                $asunto,
                $mensaje_texto,
                nl2br($mensaje_texto)
            );
            
            // Guardar en archivo SIEMPRE (respaldo)
            file_put_contents('confirmaciones.txt', 
                date('Y-m-d H:i:s') . "\n" . $mensaje_texto . "\n" . str_repeat('=', 50) . "\n\n", 
                FILE_APPEND
            );
        }
        
        echo json_encode(['success' => true, 'respuesta' => $respuesta]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>