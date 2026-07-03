<?php
// ============================================
// CERRAR SESIÓN - CON LIMPIEZA COMPLETA
// ============================================

session_start();
require_once 'config/database.php';

// Si se pidió limpiar
if(isset($_GET['limpiar']) && $_GET['limpiar'] == 1) {
    
    // ============================================
    // 1. ELIMINAR RESPUESTA DE LA BASE DE DATOS
    // ============================================
    if(isset($_SESSION['usuario_id'])) {
        $usuario_id = $_SESSION['usuario_id'];
        
        // Eliminar todas las respuestas del usuario
        $stmt = $conn->prepare("DELETE FROM respuestas WHERE usuario_id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
    }
    
    // ============================================
    // 2. ELIMINAR ARCHIVOS TEMPORALES
    // ============================================
    $archivos = ['confirmaciones.txt', 'error_log', 'debug.log'];
    foreach($archivos as $archivo) {
        if(file_exists($archivo)) {
            unlink($archivo);
        }
    }
    
    // ============================================
    // 3. GUARDAR MENSAJE DE CONFIRMACIÓN
    // ============================================
    $_SESSION['mensaje_limpieza'] = '🧹 Todo limpiado. Puedes responder la invitación de nuevo.';
}

// ============================================
// 4. CERRAR SESIÓN
// ============================================
$_SESSION = array();
session_destroy();

// Redirigir al login
header("Location: index.php");
exit();
?>