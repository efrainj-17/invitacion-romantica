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

if(!$invitacion) {
    header("Location: espera.php");
    exit();
}

// Verificar si ya respondió
$check = $conn->prepare("SELECT * FROM respuestas WHERE usuario_id = ? AND invitacion_id = ?");
$check->bind_param("ii", $usuario_id, $invitacion['id']);
$check->execute();
$respondio = $check->get_result()->fetch_assoc();

// Procesar respuesta (AJAX)
if(isset($_POST['respuesta'])) {
    $respuesta = $_POST['respuesta'];
    $mensaje_adicional = trim($_POST['mensaje'] ?? '');
    
    if(in_array($respuesta, ['aceptada', 'rechazada'])) {
        $stmt = $conn->prepare("INSERT INTO respuestas (usuario_id, invitacion_id, respuesta, mensaje_adicional) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $usuario_id, $invitacion['id'], $respuesta, $mensaje_adicional);
        
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'respuesta' => $respuesta]);
            exit();
        }
    }
    echo json_encode(['success' => false, 'error' => 'Error al guardar']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💌 Invitación Especial</title>
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
            overflow-x: hidden;
            padding: 20px;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(rgba(26,10,10,0.75), rgba(26,10,10,0.85)), url('img/invitacion-fondo.jpg') center/cover no-repeat;
            z-index: 0;
            animation: fondoAnim 25s ease-in-out infinite alternate;
        }

        @keyframes fondoAnim {
            0% { transform: scale(1); }
            100% { transform: scale(1.05); }
        }

        .invite-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 750px;
            animation: aparecer 1s ease;
        }

        @keyframes aparecer {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .invite-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(30px);
            border-radius: 40px;
            padding: 50px 45px;
            border: 2px solid rgba(255,107,157,0.15);
            box-shadow: 0 40px 100px rgba(0,0,0,0.8);
            position: relative;
            overflow: hidden;
        }

        .invite-card::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(135deg, #ff6b9d, #d63384, #ff6b9d, #d63384);
            background-size: 400% 400%;
            border-radius: 42px;
            z-index: -1;
            animation: bordeBrillo 6s ease-in-out infinite;
            opacity: 0.3;
        }

        @keyframes bordeBrillo {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .sello {
            text-align: center;
            font-size: 3.5em;
            margin-bottom: 15px;
            display: block;
            animation: selloGirar 10s linear infinite;
        }

        @keyframes selloGirar {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .invite-title {
            text-align: center;
            font-size: 2.5em;
            color: #ffd6e0;
            margin-bottom: 5px;
            text-shadow: 0 0 40px rgba(255,107,157,0.2);
        }

        .invite-title span {
            color: #ff6b9d;
        }

        .invite-subtitle {
            text-align: center;
            color: rgba(255,255,255,0.4);
            font-size: 1em;
            margin-bottom: 25px;
            font-style: italic;
        }

        .carta-contenido {
            background: rgba(255,255,255,0.02);
            border-radius: 25px;
            padding: 30px;
            border: 1px solid rgba(255,107,157,0.05);
            margin: 15px 0;
        }

        .carta-saludo {
            font-size: 1.2em;
            color: #ffd6e0;
            margin-bottom: 12px;
        }

        .carta-saludo span {
            color: #ff6b9d;
        }

        .carta-texto {
            color: rgba(255,255,255,0.85);
            line-height: 2;
            font-size: 1.1em;
        }

        .carta-texto .destacado {
            color: #ff6b9d;
            font-weight: bold;
        }

        .carta-texto .emoji {
            font-style: normal;
        }

        .detalles-evento {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin: 20px 0;
        }

        @media (max-width: 500px) {
            .detalles-evento {
                grid-template-columns: 1fr;
            }
        }

        .detalle-item {
            background: rgba(255,255,255,0.03);
            border-radius: 15px;
            padding: 15px 20px;
            border: 1px solid rgba(255,107,157,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
        }

        .detalle-item:hover {
            background: rgba(255,107,157,0.05);
            border-color: rgba(255,107,157,0.15);
        }

        .detalle-icono {
            font-size: 1.8em;
            width: 45px;
            text-align: center;
        }

        .detalle-info h4 {
            color: rgba(255,255,255,0.4);
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .detalle-info p {
            color: #fff;
            font-size: 0.95em;
            font-weight: bold;
        }

        .mensaje-personal {
            background: rgba(255,107,157,0.05);
            border-radius: 20px;
            padding: 25px;
            border-left: 4px solid #ff6b9d;
            margin: 20px 0;
            font-style: italic;
            color: rgba(255,255,255,0.75);
            font-size: 1.05em;
            line-height: 1.8;
            position: relative;
        }

        .mensaje-personal::before {
            content: '"';
            font-size: 3em;
            color: #ff6b9d;
            opacity: 0.2;
            position: absolute;
            top: -5px;
            left: 12px;
        }

        .mensaje-personal .firma {
            display: block;
            margin-top: 10px;
            color: #ff6b9d;
            font-style: normal;
            font-weight: bold;
            text-align: right;
        }

        .respuesta-section {
            margin: 25px 0 15px;
            text-align: center;
        }

        .respuesta-title {
            color: rgba(255,255,255,0.5);
            font-size: 0.95em;
            margin-bottom: 15px;
        }

        .respuesta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-respuesta {
            padding: 16px 40px;
            font-size: 1.1em;
            font-weight: bold;
            font-family: 'Georgia', serif;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.4s;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 160px;
            justify-content: center;
        }

        .btn-respuesta:hover {
            transform: translateY(-4px) scale(1.02);
        }

        .btn-aceptar {
            background: linear-gradient(135deg, #00c853, #00e676);
            color: #fff;
            box-shadow: 0 10px 30px rgba(0,200,83,0.3);
        }

        .btn-aceptar:hover {
            box-shadow: 0 15px 50px rgba(0,200,83,0.5);
        }

        .btn-rechazar {
            background: linear-gradient(135deg, #d50000, #ff1744);
            color: #fff;
            box-shadow: 0 10px 30px rgba(213,0,0,0.3);
        }

        .btn-rechazar:hover {
            box-shadow: 0 15px 50px rgba(213,0,0,0.5);
        }

        .btn-deshabilitado {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        .mensaje-input {
            margin: 15px 0;
        }

        .mensaje-input textarea {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255,255,255,0.03);
            border: 2px solid rgba(255,255,255,0.05);
            border-radius: 15px;
            color: #fff;
            font-family: 'Georgia', serif;
            font-size: 1em;
            resize: vertical;
            min-height: 70px;
            transition: all 0.3s;
        }

        .mensaje-input textarea:focus {
            outline: none;
            border-color: #ff6b9d;
            background: rgba(255,107,157,0.03);
        }

        .mensaje-input textarea::placeholder {
            color: rgba(255,255,255,0.2);
            font-style: italic;
        }

        .mensaje-input label {
            color: rgba(255,255,255,0.4);
            font-size: 0.9em;
            display: block;
            margin-bottom: 8px;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.85);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(145deg, #1a0a0a, #2a1a1a);
            border-radius: 40px;
            padding: 50px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            border: 3px solid rgba(255,107,157,0.2);
            animation: popIn 0.6s ease;
        }

        @keyframes popIn {
            from { transform: scale(0.7) rotate(-5deg); opacity: 0; }
            to { transform: scale(1) rotate(0deg); opacity: 1; }
        }

        .modal-icono {
            font-size: 4em;
            display: block;
            margin-bottom: 15px;
        }

        .modal-content h2 {
            color: #ffd6e0;
            font-size: 2em;
            margin-bottom: 10px;
        }

        .modal-content p {
            color: rgba(255,255,255,0.6);
            font-size: 1.05em;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .btn-modal {
            padding: 15px 50px;
            border: none;
            border-radius: 30px;
            color: #fff;
            font-size: 1.1em;
            font-weight: bold;
            font-family: 'Georgia', serif;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(255,107,157,0.3);
        }

        .btn-modal:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255,107,157,0.5);
        }

        .btn-modal.volver {
            background: linear-gradient(135deg, #ff6b9d, #d63384);
        }

        .btn-modal.salir {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: none;
        }

        .btn-modal.salir:hover {
            background: rgba(255,0,0,0.15);
            border-color: #ff1744;
        }

        .invite-footer {
            text-align: center;
            margin-top: 20px;
            color: rgba(255,255,255,0.12);
            font-size: 0.8em;
        }

        .invite-footer .corazon {
            color: #ff6b9d;
            display: inline-block;
            animation: latidoFooter 1.5s ease-in-out infinite;
        }

        @keyframes latidoFooter {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }

        .floating-heart {
            position: fixed;
            font-size: 18px;
            opacity: 0.04;
            z-index: 0;
            pointer-events: none;
            animation: floatHeart 20s linear infinite;
        }

        @keyframes floatHeart {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.04; }
            90% { opacity: 0.04; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }

        .confeti {
            position: fixed;
            width: 10px;
            height: 10px;
            pointer-events: none;
            z-index: 999;
            animation: confetiFall linear forwards;
        }

        @keyframes confetiFall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(110vh) rotate(720deg); opacity: 0; }
        }

        /* Modal de Salir */
        .modal-salir .modal-content {
            max-width: 450px;
        }
        .modal-salir .btn-opciones {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .modal-salir .btn-opciones button {
            padding: 12px 35px;
            border: none;
            border-radius: 25px;
            font-size: 1em;
            font-weight: bold;
            font-family: 'Georgia', serif;
            cursor: pointer;
            transition: all 0.3s;
        }
        .modal-salir .btn-opciones .btn-guardar {
            background: linear-gradient(135deg, #00c853, #00e676);
            color: #fff;
            box-shadow: 0 10px 30px rgba(0,200,83,0.3);
        }
        .modal-salir .btn-opciones .btn-guardar:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,200,83,0.5);
        }
        .modal-salir .btn-opciones .btn-limpiar {
            background: linear-gradient(135deg, #ff1744, #d50000);
            color: #fff;
            box-shadow: 0 10px 30px rgba(255,0,0,0.3);
        }
        .modal-salir .btn-opciones .btn-limpiar:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255,0,0,0.5);
        }
        .modal-salir .btn-opciones .btn-cancelar {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.6);
        }
        .modal-salir .btn-opciones .btn-cancelar:hover {
            background: rgba(255,255,255,0.1);
        }

        /* Control de volumen */
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

        @media (max-width: 600px) {
            .invite-card { padding: 30px 20px; border-radius: 30px; }
            .invite-title { font-size: 2em; }
            .carta-contenido { padding: 20px; }
            .carta-texto { font-size: 0.95em; }
            .btn-respuesta { width: 100%; max-width: 250px; padding: 14px 30px; font-size: 1em; }
            .modal-content { padding: 30px 20px; }
            .detalle-item { padding: 12px 15px; }
            .btn-modal { padding: 12px 30px; font-size: 0.95em; }
            .modal-salir .btn-opciones button { padding: 10px 25px; font-size: 0.9em; }
            .volumen-control { bottom: 15px; right: 15px; padding: 10px 15px; }
            .volumen-control input[type="range"] { width: 60px; }
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #1a0a0a; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #ff6b9d, #d63384); border-radius: 10px; }
    </style>
</head>
<body>

    <!-- ====== MÚSICA DE FONDO ====== -->
    <audio id="musicaFondo" loop preload="auto">
        <source src="music/cancion.mp3" type="audio/mpeg">
        <source src="music/cancion.ogg" type="audio/ogg">
        Tu navegador no soporta audio.
    </audio>

    <!-- ====== CORAZONES FLOTANTES ====== -->
    <div id="floatingHearts"></div>

    <!-- ====== CONTENEDOR PRINCIPAL ====== -->
    <div class="invite-wrapper">

        <div class="invite-card">

            <span class="sello">💌</span>

            <h1 class="invite-title">💕 <span>Invitación</span> Especial</h1>
            <p class="invite-subtitle">✨ Para la persona más especial de mi vida ✨</p>

            <div class="carta-contenido">
                <p class="carta-saludo">Mi querida <span><?php echo htmlspecialchars($nombre); ?></span>,</p>
                
                <div class="carta-texto">
                    <p>
                        Desde que llegaste a mi vida, cada día ha sido un regalo lleno de <span class="destacado">amor</span> 
                        y <span class="destacado">alegría</span>. 💖
                    </p>
                    <br>
                    <p>
                        Por eso, quiero invitarte a compartir un momento especial conmigo.
                    </p>
                    <br>
                    <p>
                        <span class="emoji">⚽</span> <span class="destacado"><?php echo htmlspecialchars($invitacion['evento']); ?></span><br>
                        <span class="emoji">📅</span> <?php echo date('d \d\e F \d\e Y', strtotime($invitacion['fecha'])); ?><br>
                        <span class="emoji">⏰</span> <?php echo date('h:i A', strtotime($invitacion['fecha'])); ?>
                    </p>
                </div>
            </div>

            <div class="detalles-evento">
                <div class="detalle-item">
                    <span class="detalle-icono">⚽</span>
                    <div class="detalle-info">
                        <h4>Evento</h4>
                        <p><?php echo htmlspecialchars($invitacion['evento']); ?></p>
                    </div>
                </div>
                <div class="detalle-item">
                    <span class="detalle-icono">📅</span>
                    <div class="detalle-info">
                        <h4>Fecha</h4>
                        <p><?php echo date('d/m/Y', strtotime($invitacion['fecha'])); ?></p>
                    </div>
                </div>
                <div class="detalle-item">
                    <span class="detalle-icono">⏰</span>
                    <div class="detalle-info">
                        <h4>Hora</h4>
                        <p><?php echo date('h:i A', strtotime($invitacion['fecha'])); ?></p>
                    </div>
                </div>
                <div class="detalle-item">
                    <span class="detalle-icono">📍</span>
                    <div class="detalle-info">
                        <h4>Lugar</h4>
                        <p><?php echo htmlspecialchars($invitacion['lugar']); ?></p>
                    </div>
                </div>
            </div>

            <div class="mensaje-personal">
                <?php echo nl2br(htmlspecialchars($invitacion['mensaje'])); ?>
                <span class="firma">— Con todo mi amor ❤️</span>
            </div>

            <?php if(!$respondio): ?>
            <div class="respuesta-section">
                <p class="respuesta-title">💕 ¿Aceptas esta invitación?</p>

                <div class="mensaje-input">
                    <label>💬 Escribe un mensaje para mí (opcional)</label>
                    <textarea id="mensajePersonal" placeholder="Escribe algo bonito... 💕"></textarea>
                </div>

                <div class="respuesta-buttons">
                    <button class="btn-respuesta btn-aceptar" onclick="enviarRespuesta('aceptada')">
                        ✅ Sí, acepto
                    </button>
                    <button class="btn-respuesta btn-rechazar" onclick="enviarRespuesta('rechazada')">
                        ❌ No puedo
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:20px;">
                <div style="font-size:3em;margin-bottom:10px;">
                    <?php echo $respondio['respuesta'] == 'aceptada' ? '🎉' : '💔'; ?>
                </div>
                <h3 style="color:<?php echo $respondio['respuesta'] == 'aceptada' ? '#00c853' : '#ff1744'; ?>;">
                    <?php echo $respondio['respuesta'] == 'aceptada' ? '¡Aceptaste la invitación! ❤️' : 'Rechazaste la invitación'; ?>
                </h3>
                <?php if($respondio['mensaje_adicional']): ?>
                <p style="color:rgba(255,255,255,0.5);font-style:italic;margin-top:10px;">
                    "<?php echo htmlspecialchars($respondio['mensaje_adicional']); ?>"
                </p>
                <?php endif; ?>
                <div style="display:flex; gap:15px; justify-content:center; flex-wrap:wrap; margin-top:20px;">
                    <a href="espera.php" class="btn-modal volver" style="text-decoration:none;display:inline-block;">
                        💕 Volver
                    </a>
                    <button class="btn-modal salir" onclick="mostrarModalSalir()" style="text-decoration:none;display:inline-block;">
                        🚪 Salir
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="invite-footer">
                Hecho con <span class="corazon">❤️</span> para ti, mi amor
            </div>

        </div>
    </div>

    <!-- ====== CONTROL DE VOLUMEN ====== -->
    <div class="volumen-control">
        <span class="icono">🔊</span>
        <input type="range" id="controlVolumen" min="0" max="1" step="0.01" value="0.5">
    </div>

    <!-- ====== MODAL DE CONFIRMACIÓN ====== -->
    <div class="modal-overlay" id="modalConfirmacion">
        <div class="modal-content">
            <span class="modal-icono" id="modalIcono">💖</span>
            <h2 id="modalTitulo">¡Gracias, mi amor!</h2>
            <p id="modalDescripcion">Tu respuesta ha sido guardada con mucho amor.</p>
            <div style="display:flex; gap:15px; justify-content:center; flex-wrap:wrap; margin-top:15px;">
                <button class="btn-modal volver" onclick="cerrarModal()">
                    💕 Volver
                </button>
                <button class="btn-modal salir" onclick="mostrarModalSalir()">
                    🚪 Salir
                </button>
            </div>
        </div>
    </div>

    <!-- ====== MODAL DE SALIR - GUARDAR O LIMPIAR ====== -->
    <div class="modal-overlay modal-salir" id="modalSalir">
        <div class="modal-content">
            <span style="font-size:3em;display:block;margin-bottom:10px;">🧹</span>
            <h2 style="color:#ffd6e0;font-size:1.8em;margin-bottom:10px;">¿Qué deseas hacer?</h2>
            <p style="color:rgba(255,255,255,0.6);font-size:1em;line-height:1.6;margin-bottom:10px;">
                Al salir, puedes guardar tu progreso o limpiar todos los datos.
            </p>
            <div class="btn-opciones">
                <button class="btn-guardar" onclick="salirGuardar()">
                    💾 Guardar y salir
                </button>
                <button class="btn-limpiar" onclick="salirLimpiar()">
                    🗑️ Limpiar y salir
                </button>
                <button class="btn-cancelar" onclick="cerrarModalSalir()">
                    ✕ Cancelar
                </button>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // CORAZONES FLOTANTES
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            const contenedor = document.getElementById('floatingHearts');
            const corazones = ['❤️', '💕', '💖', '💗', '💝', '💘', '💓'];
            
            for(let i = 0; i < 30; i++) {
                const corazon = document.createElement('div');
                corazon.className = 'floating-heart';
                corazon.textContent = corazones[Math.floor(Math.random() * corazones.length)];
                corazon.style.left = Math.random() * 100 + '%';
                corazon.style.fontSize = (Math.random() * 30 + 10) + 'px';
                corazon.style.animationDuration = (Math.random() * 20 + 15) + 's';
                corazon.style.animationDelay = (Math.random() * 20) + 's';
                contenedor.appendChild(corazon);
            }
        });

        // ============================================
        // ENVIAR RESPUESTA
        // ============================================
        function enviarRespuesta(respuesta) {
            const mensaje = document.getElementById('mensajePersonal').value;
            const botones = document.querySelectorAll('.btn-respuesta');
            const modal = document.getElementById('modalConfirmacion');
            const icono = document.getElementById('modalIcono');
            const titulo = document.getElementById('modalTitulo');
            const descripcion = document.getElementById('modalDescripcion');

            botones.forEach(btn => {
                btn.classList.add('btn-deshabilitado');
                btn.disabled = true;
            });

            modal.classList.add('active');
            icono.textContent = '⏳';
            titulo.textContent = 'Guardando...';
            descripcion.textContent = 'Estamos guardando tu respuesta 💕';

            const usuario_id = <?php echo $usuario_id; ?>;
            const invitacion_id = <?php echo $invitacion['id']; ?>;

            const datos = new URLSearchParams();
            datos.append('respuesta', respuesta);
            datos.append('mensaje', mensaje);
            datos.append('usuario_id', usuario_id);
            datos.append('invitacion_id', invitacion_id);

            console.log('📤 Enviando datos:', {
                respuesta: respuesta,
                mensaje: mensaje,
                usuario_id: usuario_id,
                invitacion_id: invitacion_id
            });

            fetch('confirmar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: datos.toString()
            })
            .then(response => response.json())
            .then(data => {
                console.log('📥 Respuesta del servidor:', data);
                
                if(data.success) {
                    if(respuesta === 'aceptada') {
                        icono.textContent = '🎉';
                        titulo.textContent = '¡GRACIAS, MI AMOR! ❤️';
                        descripcion.textContent = 'Has hecho que mi corazón lata más fuerte. ¡Te espero con la ilusión más grande!';
                        lanzarConfeti();
                    } else {
                        icono.textContent = '💔';
                        titulo.textContent = 'Entiendo, mi amor';
                        descripcion.textContent = 'Siempre habrá un nuevo momento para celebrar juntos.';
                    }
                } else {
                    icono.textContent = '😢';
                    titulo.textContent = 'Hubo un error';
                    descripcion.textContent = data.error || 'Por favor, intenta de nuevo.';
                    
                    botones.forEach(btn => {
                        btn.classList.remove('btn-deshabilitado');
                        btn.disabled = false;
                    });
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                icono.textContent = '😢';
                titulo.textContent = 'Error de conexión';
                descripcion.textContent = 'Revisa tu conexión e intenta de nuevo.';
                
                botones.forEach(btn => {
                    btn.classList.remove('btn-deshabilitado');
                    btn.disabled = false;
                });
            });
        }

        // ============================================
        // CERRAR MODAL - VOLVER A ESPERA
        // ============================================
        function cerrarModal() {
            document.getElementById('modalConfirmacion').classList.remove('active');
            window.location.href = 'espera.php';
        }

        // ============================================
        // MODAL DE SALIR - GUARDAR O LIMPIAR
        // ============================================
        function mostrarModalSalir() {
            document.getElementById('modalSalir').classList.add('active');
        }

        function cerrarModalSalir() {
            document.getElementById('modalSalir').classList.remove('active');
        }

        function salirGuardar() {
            window.location.href = 'logout.php';
        }

        function salirLimpiar() {
            window.location.href = 'logout.php?limpiar=1';
        }

        // ============================================
        // CONFETI
        // ============================================
        function lanzarConfeti() {
            const colores = ['#ff6b9d', '#ffd700', '#00c853', '#ff3366', '#33ccff', '#ffd6e0'];
            const formas = ['●', '■', '▲', '★', '♦', '♥'];
            
            for(let i = 0; i < 80; i++) {
                setTimeout(() => {
                    const confeti = document.createElement('div');
                    confeti.className = 'confeti';
                    confeti.textContent = formas[Math.floor(Math.random() * formas.length)];
                    confeti.style.left = Math.random() * 100 + '%';
                    confeti.style.color = colores[Math.floor(Math.random() * colores.length)];
                    confeti.style.fontSize = (Math.random() * 20 + 10) + 'px';
                    confeti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                    confeti.style.animationDelay = (Math.random() * 2) + 's';
                    document.body.appendChild(confeti);
                    
                    setTimeout(() => confeti.remove(), 5000);
                }, i * 50);
            }
        }

        // ============================================
        // MÚSICA - INICIAR Y CONTROL DE VOLUMEN
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            const audio = document.getElementById('musicaFondo');
            const controlVolumen = document.getElementById('controlVolumen');
            
            if(audio) {
                audio.volume = 0.5;
                
                // Intentar reproducir automáticamente
                const playPromise = audio.play();
                
                if(playPromise !== undefined) {
                    playPromise.catch(function(error) {
                        console.log('⚠️ Autoplay bloqueado. Esperando interacción...');
                        
                        const playOnClick = function() {
                            audio.play();
                            document.removeEventListener('click', playOnClick);
                            document.removeEventListener('touchstart', playOnClick);
                        };
                        
                        document.addEventListener('click', playOnClick);
                        document.addEventListener('touchstart', playOnClick);
                    });
                }
            }
            
            if(controlVolumen) {
                controlVolumen.addEventListener('input', function(e) {
                    if(audio) {
                        audio.volume = parseFloat(e.target.value);
                    }
                });
            }
        });

        // Cerrar modales con ESC
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                const modalConfirmacion = document.getElementById('modalConfirmacion');
                const modalSalir = document.getElementById('modalSalir');
                
                if(modalSalir.classList.contains('active')) {
                    cerrarModalSalir();
                } else if(modalConfirmacion.classList.contains('active')) {
                    cerrarModal();
                }
            }
        });

        // Cerrar modal de salir al hacer clic fuera
        document.getElementById('modalSalir').addEventListener('click', function(e) {
            if(e.target === this) {
                cerrarModalSalir();
            }
        });
    </script>
</body>
</html>