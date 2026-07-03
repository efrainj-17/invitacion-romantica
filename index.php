<?php
session_start();
require_once 'config/database.php';

// Mostrar mensaje de limpieza si existe
$mensaje_limpieza = '';
if(isset($_SESSION['mensaje_limpieza'])) {
    $mensaje_limpieza = $_SESSION['mensaje_limpieza'];
    unset($_SESSION['mensaje_limpieza']);
}
// Mostrar mensaje de correo enviado
$mensaje_correo = '';
if(isset($_GET['correo'])) {
    $mensaje_correo = $_GET['correo'] == '1' ? '📧 Correo enviado correctamente' : '📧 Error al enviar correo';
}
// Si ya está logueado, redirigir según rol
if(isset($_SESSION['usuario_id'])) {
    if($_SESSION['rol'] == 'admin') {
        header("Location: admin.php");
        exit();
    } else {
        header("Location: espera.php");
        exit();
    }
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];
    
    if(empty($usuario) || empty($password)) {
        $error = '💕 Completa todos los campos';
    } else {
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $usuario_data = $result->fetch_assoc();
            
            if($usuario_data['activo'] == 0) {
                $error = '💔 Usuario desactivado';
            } elseif(password_verify($password, $usuario_data['password'])) {
                $_SESSION['usuario_id'] = $usuario_data['id'];
                $_SESSION['nombre'] = $usuario_data['nombre'];
                $_SESSION['usuario'] = $usuario_data['usuario'];
                $_SESSION['rol'] = $usuario_data['rol'];
                
                if($usuario_data['rol'] == 'admin') {
                    header("Location: admin.php");
                    exit();
                } else {
                    header("Location: espera.php");
                    exit();
                }
            } else {
                $error = '💔 Contraseña incorrecta';
            }
        } else {
            $error = '💔 Usuario no encontrado';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💕 Mi Amor - Iniciar Sesión</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Georgia', serif;
            background: #1a0a0a;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(rgba(26,10,10,0.7), rgba(26,10,10,0.8)), url('img/login-fondo.jpg') center/cover no-repeat;
            z-index: 0;
            animation: fondoAnim 20s ease-in-out infinite alternate;
        }
        @keyframes fondoAnim {
            0% { transform: scale(1); }
            100% { transform: scale(1.05); }
        }
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
            animation: aparecer 1s ease;
        }
        @keyframes aparecer {
            from { opacity: 0; transform: translateY(-30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .login-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 40px 35px;
            border: 2px solid rgba(255,107,157,0.2);
            box-shadow: 0 30px 80px rgba(0,0,0,0.8);
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(135deg, #ff6b9d, #d63384, #ff6b9d);
            background-size: 300% 300%;
            border-radius: 32px;
            z-index: -1;
            animation: bordeBrillo 4s ease-in-out infinite;
            opacity: 0.3;
        }
        @keyframes bordeBrillo {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        .foto-container {
            text-align: center;
            margin-bottom: 25px;
            position: relative;
        }
        .foto-perfil {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ff6b9d;
            box-shadow: 0 0 30px rgba(255,107,157,0.3);
            transition: all 0.5s ease;
            animation: latido 3s ease-in-out infinite;
        }
        @keyframes latido {
            0%, 100% { transform: scale(1); box-shadow: 0 0 30px rgba(255,107,157,0.3); }
            50% { transform: scale(1.03); box-shadow: 0 0 50px rgba(255,107,157,0.5); }
        }
        .foto-perfil:hover { transform: scale(1.05) rotate(-5deg); }
        .corazon-flotante {
            position: absolute;
            font-size: 24px;
            animation: flotarCorazon 3s ease-in-out infinite;
        }
        .corazon-flotante:nth-child(2) { top: -10px; right: 10px; animation-delay: 0.5s; }
        .corazon-flotante:nth-child(3) { bottom: -10px; left: 10px; animation-delay: 1s; }
        .corazon-flotante:nth-child(4) { top: 10px; left: -15px; animation-delay: 1.5s; }
        .corazon-flotante:nth-child(5) { bottom: 10px; right: -15px; animation-delay: 2s; }
        @keyframes flotarCorazon {
            0%, 100% { transform: translateY(0) scale(1); opacity: 0.6; }
            50% { transform: translateY(-10px) scale(1.2); opacity: 1; }
        }
        .login-title {
            text-align: center;
            color: #fff;
            font-size: 2em;
            margin-bottom: 5px;
            text-shadow: 0 0 30px rgba(255,107,157,0.3);
        }
        .login-subtitle {
            text-align: center;
            color: rgba(255,255,255,0.6);
            font-size: 1em;
            margin-bottom: 30px;
            font-style: italic;
        }
        .login-subtitle span { color: #ff6b9d; font-weight: bold; }
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }
        .form-group .icono {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            opacity: 0.5;
        }
        .form-group input {
            width: 100%;
            padding: 14px 15px 14px 50px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            color: #fff;
            font-size: 1em;
            font-family: 'Georgia', serif;
            transition: all 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #ff6b9d;
            background: rgba(255,107,157,0.05);
            box-shadow: 0 0 30px rgba(255,107,157,0.1);
        }
        .form-group input::placeholder {
            color: rgba(255,255,255,0.3);
            font-style: italic;
        }
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ff6b9d, #d63384);
            border: none;
            border-radius: 15px;
            color: #fff;
            font-size: 1.2em;
            font-weight: bold;
            font-family: 'Georgia', serif;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(255,107,157,0.3);
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255,107,157,0.5);
        }
        .error-message {
            background: rgba(255,0,0,0.1);
            border: 1px solid rgba(255,0,0,0.2);
            border-radius: 12px;
            padding: 12px 15px;
            color: #ff6b9d;
            text-align: center;
            margin-bottom: 20px;
            animation: temblar 0.5s ease;
        }
        @keyframes temblar {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .mensaje-exito {
            background: rgba(0,200,83,0.1);
            border: 1px solid rgba(0,200,83,0.2);
            border-radius: 12px;
            padding: 12px 15px;
            color: #00c853;
            text-align: center;
            margin-bottom: 20px;
            animation: aparecerMensaje 0.5s ease;
        }
        @keyframes aparecerMensaje {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-footer {
            text-align: center;
            margin-top: 25px;
            color: rgba(255,255,255,0.3);
            font-size: 0.9em;
        }
        .login-footer .corazon {
            color: #ff6b9d;
            display: inline-block;
            animation: latidoFooter 1.5s ease-in-out infinite;
        }
        @keyframes latidoFooter {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="foto-container">
                <img src="img/login-foto.jpg" alt="Mi Amor" class="foto-perfil" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%22120%22%3E%3Ccircle cx=%2260%22 cy=%2260%22 r=%2250%22 fill=%22%23ff6b9d%22/%3E%3Ctext x=%2260%22 y=%2270%22 text-anchor=%22middle%22 font-size=%2230%22 fill=%22%23fff%22%3E❤️%3C/text%3E%3C/svg%3E'">
                <span class="corazon-flotante">💕</span>
                <span class="corazon-flotante">💖</span>
                <span class="corazon-flotante">💗</span>
                <span class="corazon-flotante">💝</span>
            </div>
            <h1 class="login-title">💕 Mi Amor</h1>
            <p class="login-subtitle">Inicia sesión para ver tu <span>invitación especial</span> 💌</p>
            
            <?php if(!empty($mensaje_limpieza)): ?>
                <div class="mensaje-exito"><?php echo $mensaje_limpieza; ?></div>
            <?php endif; ?>
            <?php if(!empty($mensaje_correo)): ?>
                <div class="mensaje-exito"><?php echo $mensaje_correo; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <span class="icono">👤</span>
                    <input type="text" name="usuario" placeholder="Tu usuario, Apodo" required>
                </div>
                <div class="form-group">
                    <span class="icono">🔐</span>
                    <input type="password" name="password" placeholder="4 digitos dia y mes 0*0*" required>
                </div>
                <button type="submit" class="btn-login">💖 Entrar 💖</button>
            </form>
            
            <div class="login-footer">
                Hecho con <span class="corazon">❤️</span> para ti, mi amor
            </div>
        </div>
    </div>
</body>
</html>s