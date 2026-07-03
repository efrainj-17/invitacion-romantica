<?php
session_start();
require_once 'config/database.php';

// ============================================
// VERIFICAR QUE SEA USUARIO (NOVIA)
// ============================================
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

if($_SESSION['rol'] == 'admin') {
    header("Location: admin.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$nombre = $_SESSION['nombre'];

// Obtener invitación activa
$invitacion = $conn->query("SELECT * FROM invitacion WHERE activa = 1 ORDER BY id DESC LIMIT 1")->fetch_assoc();

// Verificar si ya respondió
$respondio = false;
if($invitacion) {
    $check = $conn->prepare("SELECT * FROM respuestas WHERE usuario_id = ? AND invitacion_id = ?");
    $check->bind_param("ii", $usuario_id, $invitacion['id']);
    $check->execute();
    $respondio = $check->get_result()->num_rows > 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💕 ¡Hola, mi amor!</title>
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
            background: linear-gradient(rgba(26,10,10,0.6), rgba(26,10,10,0.8)), url('img/fondo-espera.jpg') center/cover no-repeat;
            z-index: 0;
            animation: fondoAnim 20s ease-in-out infinite alternate;
        }
        
        @keyframes fondoAnim {
            0% { transform: scale(1); }
            100% { transform: scale(1.05); }
        }
        
        .container {
            position: relative;
            z-index: 1;
            max-width: 700px;
            width: 100%;
            padding: 20px;
            animation: aparecer 1.5s ease;
        }
        
        @keyframes aparecer {
            from { opacity: 0; transform: scale(0.9) translateY(30px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        
        .card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(30px);
            border-radius: 40px;
            padding: 50px 40px;
            border: 2px solid rgba(255,107,157,0.15);
            box-shadow: 0 40px 100px rgba(0,0,0,0.8);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(135deg, #ff6b9d, #d63384, #ff6b9d);
            background-size: 300% 300%;
            border-radius: 42px;
            z-index: -1;
            animation: bordeBrillo 4s ease-in-out infinite;
            opacity: 0.3;
        }
        
        @keyframes bordeBrillo {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .emoji-big {
            font-size: 4em;
            display: block;
            margin-bottom: 15px;
            animation: latido 2s ease-in-out infinite;
        }
        
        @keyframes latido {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .titulo {
            color: #ffd6e0;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .titulo span {
            color: #ff6b9d;
        }
        
        .subtitulo {
            color: rgba(255,255,255,0.5);
            font-size: 1.1em;
            font-style: italic;
            margin-bottom: 30px;
        }
        
        .mensaje {
            color: rgba(255,255,255,0.8);
            font-size: 1.2em;
            line-height: 1.8;
            margin-bottom: 30px;
        }
        
        .btn-siguiente {
            display: inline-block;
            padding: 18px 50px;
            background: linear-gradient(135deg, #ff6b9d, #d63384);
            color: #fff;
            border: none;
            border-radius: 50px;
            font-size: 1.3em;
            font-weight: bold;
            font-family: 'Georgia', serif;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(255,107,157,0.3);
            text-decoration: none;
        }
        
        .btn-siguiente:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 50px rgba(255,107,157,0.5);
        }
        
        .btn-siguiente:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .volumen-control {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 10;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 12px 20px;
            border: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .volumen-control input[type="range"] {
            width: 80px;
            accent-color: #ff6b9d;
            cursor: pointer;
            background: transparent;
        }
        
        .volumen-control .icono {
            font-size: 1.2em;
            color: rgba(255,255,255,0.5);
        }
        
        .footer {
            margin-top: 20px;
            color: rgba(255,255,255,0.15);
            font-size: 0.8em;
        }
        
        .floating-heart {
            position: fixed;
            font-size: 20px;
            opacity: 0.05;
            z-index: 0;
            pointer-events: none;
            animation: floatHeart 20s linear infinite;
        }
        
        @keyframes floatHeart {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.05; }
            90% { opacity: 0.05; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }
        
        .estado-respuesta {
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
        }
        
        .estado-respuesta.aceptada {
            background: rgba(0,200,83,0.1);
            border: 1px solid rgba(0,200,83,0.2);
        }
        
        .estado-respuesta.rechazada {
            background: rgba(255,0,0,0.1);
            border: 1px solid rgba(255,0,0,0.2);
        }
        
        .estado-respuesta .icono {
            font-size: 2.5em;
            display: block;
            margin-bottom: 10px;
        }
        
        .estado-respuesta .texto {
            color: #fff;
            font-size: 1.1em;
        }
        
        .estado-respuesta .sub {
            color: rgba(255,255,255,0.4);
            font-size: 0.9em;
        }
        
        @media (max-width: 600px) {
            .card { padding: 30px 20px; }
            .titulo { font-size: 1.8em; }
            .mensaje { font-size: 1em; }
            .btn-siguiente { padding: 14px 30px; font-size: 1em; }
            .volumen-control { bottom: 15px; right: 15px; padding: 10px 15px; }
            .volumen-control input[type="range"] { width: 60px; }
        }
    </style>
</head>
<body data-rol="novia">
    
    <!-- ====== MÚSICA ====== -->
    <audio id="musicaFondo" loop preload="auto">
        <source src="music/cancion.mp3" type="audio/mpeg">
        <source src="music/cancion.ogg" type="audio/ogg">
        Tu navegador no soporta audio.
    </audio>
    
    <!-- ====== CORAZONES FLOTANTES ====== -->
    <div id="floatingHearts"></div>
    
    <!-- ====== CONTENEDOR ====== -->
    <div class="container">
        <div class="card">
            <span class="emoji-big">💕</span>
            <h1 class="titulo">¡Hola, <span><?php echo htmlspecialchars($nombre); ?></span>!</h1>
            <p class="subtitulo">✨ Tengo algo especial para ti ✨</p>
            
            <div class="mensaje">
                He preparado algo muy bonito para ti.<br>
                ¿Me acompañas a verlo? 💝
            </div>
            
            <?php if($invitacion && !$respondio): ?>
                <a href="invitacion.php" class="btn-siguiente">
                    Siguiente ➜
                </a>
            <?php elseif($respondio): ?>
                <div class="estado-respuesta aceptada">
                    <span class="icono">💖</span>
                    <div class="texto">Ya respondiste a mi invitación.</div>
                    <div class="sub">Gracias, mi amor ❤️</div>
                </div>
                <a href="invitacion.php" class="btn-siguiente" style="margin-top:15px;">
                    Ver invitación
                </a>
            <?php else: ?>
                <div style="color:rgba(255,255,255,0.3);padding:20px;">
                    <div style="font-size:2em;margin-bottom:10px;">⏳</div>
                    <p>No hay invitación activa en este momento.</p>
                    <p style="font-size:0.8em;">Espera a que la active 💕</p>
                </div>
            <?php endif; ?>
            
            <div class="footer">
                Hecho con ❤️ para ti, mi amor
            </div>
        </div>
    </div>
    
    <!-- ====== CONTROL DE VOLUMEN ====== -->
    <div class="volumen-control">
        <span class="icono">🔊</span>
        <input type="range" id="controlVolumen" min="0" max="1" step="0.01" value="0.5">
    </div>
    
    <script src="js/musica.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const contenedor = document.getElementById('floatingHearts');
            const corazones = ['❤️', '💕', '💖', '💗', '💝'];
            
            for(let i = 0; i < 25; i++) {
                const corazon = document.createElement('div');
                corazon.className = 'floating-heart';
                corazon.textContent = corazones[Math.floor(Math.random() * corazones.length)];
                corazon.style.left = Math.random() * 100 + '%';
                corazon.style.fontSize = (Math.random() * 25 + 10) + 'px';
                corazon.style.animationDuration = (Math.random() * 20 + 15) + 's';
                corazon.style.animationDelay = (Math.random() * 20) + 's';
                contenedor.appendChild(corazon);
            }
        });
    </script>
</body>
</html>