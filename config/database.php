<?php
// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================

$host = 'localhost';
$dbname = 'invitacion_romantica';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("💔 Error: " . $e->getMessage());
}
?>