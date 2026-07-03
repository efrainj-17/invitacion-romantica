<?php
session_start();
require_once 'config/database.php';

// ============================================
// VERIFICAR QUE SEA ADMIN
// ============================================
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

if($_SESSION['rol'] != 'admin') {
    header("Location: espera.php");
    exit();
}

$nombre = $_SESSION['nombre'];

// ============================================
// ESTADÍSTICAS
// ============================================
$total_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
$total_respuestas = $conn->query("SELECT COUNT(*) as total FROM respuestas")->fetch_assoc()['total'];
$aceptadas = $conn->query("SELECT COUNT(*) as total FROM respuestas WHERE respuesta = 'aceptada'")->fetch_assoc()['total'];
$rechazadas = $conn->query("SELECT COUNT(*) as total FROM respuestas WHERE respuesta = 'rechazada'")->fetch_assoc()['total'];

// Últimas respuestas
$respuestas = $conn->query("
    SELECT r.*, u.nombre, u.usuario, u.email 
    FROM respuestas r 
    JOIN usuarios u ON r.usuario_id = u.id 
    ORDER BY r.fecha_respuesta DESC 
    LIMIT 5
");

// Obtener invitación activa
$invitacion = $conn->query("SELECT * FROM invitacion WHERE activa = 1 ORDER BY id DESC LIMIT 1")->fetch_assoc();

// ============================================
// PROCESAR FORMULARIOS
// ============================================

// Crear/Editar usuario
if(isset($_POST['action']) && $_POST['action'] == 'guardar_usuario') {
    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $rol = $_POST['rol'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if(empty($nombre) || empty($usuario) || empty($email)) {
        $error_usuario = '⚠️ Nombre, usuario y email son obligatorios';
    } else {
        // Verificar usuario único
        $check = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
        $check->bind_param("si", $usuario, $id);
        $check->execute();
        if($check->get_result()->num_rows > 0) {
            $error_usuario = '⚠️ El usuario ya existe';
        } else {
            if($id > 0) {
                // Actualizar
                if(!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, email = ?, password = ?, rol = ?, activo = ? WHERE id = ?");
                    $stmt->bind_param("sssssii", $nombre, $usuario, $email, $hash, $rol, $activo, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, email = ?, rol = ?, activo = ? WHERE id = ?");
                    $stmt->bind_param("ssssii", $nombre, $usuario, $email, $rol, $activo, $id);
                }
                $mensaje_usuario = '✅ Usuario actualizado';
            } else {
                // Crear nuevo
                if(empty($password)) {
                    $error_usuario = '⚠️ La contraseña es obligatoria para nuevos usuarios';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, email, password, rol, activo) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssi", $nombre, $usuario, $email, $hash, $rol, $activo);
                    $mensaje_usuario = '✅ Usuario creado';
                }
            }
            
            if(isset($stmt) && $stmt->execute()) {
                $total_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
                $_POST = array();
            } elseif(isset($stmt) && !isset($error_usuario)) {
                $error_usuario = '⚠️ Error: ' . $conn->error;
            }
        }
    }
}

// Eliminar usuario
if(isset($_GET['eliminar_usuario']) && is_numeric($_GET['eliminar_usuario'])) {
    $id = intval($_GET['eliminar_usuario']);
    if($id != $_SESSION['usuario_id']) {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()) {
            $mensaje_usuario = '✅ Usuario eliminado';
            $total_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
        }
    }
}

// Guardar invitación
if(isset($_POST['action']) && $_POST['action'] == 'guardar_invitacion') {
    $id = intval($_POST['id'] ?? 0);
    $titulo = trim($_POST['titulo']);
    $mensaje = trim($_POST['mensaje']);
    $evento = trim($_POST['evento']);
    $fecha = $_POST['fecha'];
    $lugar = trim($_POST['lugar']);
    $activa = isset($_POST['activa']) ? 1 : 0;
    
    if(empty($titulo) || empty($evento) || empty($fecha) || empty($lugar)) {
        $error_invitacion = '⚠️ Todos los campos son obligatorios';
    } else {
        if($id > 0) {
            $stmt = $conn->prepare("UPDATE invitacion SET titulo = ?, mensaje = ?, evento = ?, fecha = ?, lugar = ?, activa = ? WHERE id = ?");
            $stmt->bind_param("sssssii", $titulo, $mensaje, $evento, $fecha, $lugar, $activa, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO invitacion (titulo, mensaje, evento, fecha, lugar, activa) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $titulo, $mensaje, $evento, $fecha, $lugar, $activa);
        }
        
        if($stmt->execute()) {
            $mensaje_invitacion = '✅ Invitación guardada';
            $invitacion = $conn->query("SELECT * FROM invitacion WHERE activa = 1 ORDER BY id DESC LIMIT 1")->fetch_assoc();
        } else {
            $error_invitacion = '⚠️ Error: ' . $conn->error;
        }
    }
}

// Obtener usuarios para la tabla
$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY id");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👑 Panel Admin</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0a0a0a; color: #fff; }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .header h1 { font-size: 2em; color: #ffd6e0; }
        .header h1 span { color: #ff6b9d; }
        .header .user { display: flex; align-items: center; gap: 15px; }
        .header .user .avatar { width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #ff6b9d, #d63384); display: flex; align-items: center; justify-content: center; font-size: 1.2em; }
        .btn-logout { padding: 10px 25px; background: rgba(255,0,0,0.1); border: 1px solid rgba(255,0,0,0.2); border-radius: 10px; color: #ff6b9d; text-decoration: none; transition: all 0.3s; }
        .btn-logout:hover { background: rgba(255,0,0,0.2); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 15px; padding: 20px; text-align: center; }
        .stat-card .number { font-size: 2.5em; font-weight: bold; color: #ffd6e0; }
        .stat-card .label { color: rgba(255,255,255,0.3); font-size: 0.9em; margin-top: 5px; }
        .stat-card.aceptadas .number { color: #00c853; }
        .stat-card.rechazadas .number { color: #ff1744; }
        .admin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        @media (max-width: 900px) { .admin-grid { grid-template-columns: 1fr; } }
        .card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 15px; padding: 25px; margin-bottom: 25px; }
        .card-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; color: #ffd6e0; font-size: 1.1em; }
        .card-title .badge { background: rgba(255,107,157,0.15); color: #ff6b9d; padding: 4px 12px; border-radius: 20px; font-size: 0.8em; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: rgba(255,255,255,0.5); margin-bottom: 5px; font-size: 0.9em; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px 15px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 10px; color: #fff; font-family: inherit; transition: all 0.3s; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #ff6b9d; background: rgba(255,107,157,0.03); }
        .form-group textarea { min-height: 70px; resize: vertical; }
        .form-group select option { background: #1a0a0a; color: #fff; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 500px) { .form-row { grid-template-columns: 1fr; } }
        .checkbox-group { display: flex; align-items: center; gap: 10px; padding: 8px 0; }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; accent-color: #ff6b9d; cursor: pointer; }
        .checkbox-group label { color: rgba(255,255,255,0.6); cursor: pointer; }
        .btn-submit { padding: 12px 30px; background: linear-gradient(135deg, #ff6b9d, #d63384); border: none; border-radius: 10px; color: #fff; font-size: 1em; font-weight: bold; font-family: inherit; cursor: pointer; transition: all 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(255,107,157,0.3); }
        .btn-small { padding: 5px 12px; border: none; border-radius: 6px; color: #fff; cursor: pointer; font-size: 0.8em; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn-small.edit { background: rgba(41,121,255,0.2); color: #2979ff; }
        .btn-small.delete { background: rgba(255,0,0,0.15); color: #ff1744; }
        .btn-small.delete:hover { background: rgba(255,0,0,0.25); }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th { text-align: left; padding: 10px; color: rgba(255,255,255,0.3); font-weight: normal; font-size: 0.75em; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        table td { padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.02); color: rgba(255,255,255,0.7); }
        table tr:hover td { background: rgba(255,255,255,0.02); }
        .status-badge { padding: 3px 12px; border-radius: 20px; font-size: 0.75em; }
        .status-badge.aceptada { background: rgba(0,200,83,0.15); color: #00c853; }
        .status-badge.rechazada { background: rgba(255,0,0,0.15); color: #ff1744; }
        .status-badge.activo { background: rgba(0,200,83,0.15); color: #00c853; }
        .status-badge.inactivo { background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.3); }
        .mensaje-exito { background: rgba(0,200,83,0.1); border: 1px solid rgba(0,200,83,0.2); border-radius: 10px; padding: 12px 15px; color: #00c853; margin-bottom: 15px; }
        .mensaje-error { background: rgba(255,0,0,0.1); border: 1px solid rgba(255,0,0,0.2); border-radius: 10px; padding: 12px 15px; color: #ff1744; margin-bottom: 15px; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0a0a0a; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #ff6b9d, #d63384); border-radius: 10px; }
        @media (max-width: 600px) { .header h1 { font-size: 1.4em; } .stats-grid { grid-template-columns: 1fr 1fr; } .card { padding: 15px; } }
    </style>
</head>
<body>

    <div class="container">

        <header class="header">
            <h1>👑 Panel de <span>Administrador</span></h1>
            <div class="user">
                <div class="avatar">👤</div>
                <span><?php echo htmlspecialchars($nombre); ?></span>
                <a href="logout.php" class="btn-logout">🚪 Salir</a>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card"><div class="number"><?php echo $total_usuarios; ?></div><div class="label">👤 Usuarios</div></div>
            <div class="stat-card"><div class="number"><?php echo $total_respuestas; ?></div><div class="label">💌 Respuestas</div></div>
            <div class="stat-card aceptadas"><div class="number"><?php echo $aceptadas; ?></div><div class="label">✅ Aceptadas</div></div>
            <div class="stat-card rechazadas"><div class="number"><?php echo $rechazadas; ?></div><div class="label">❌ Rechazadas</div></div>
        </div>

        <div class="admin-grid">

            <!-- COLUMNA IZQUIERDA -->
            <div>

                <!-- Crear Usuario -->
                <div class="card">
                    <div class="card-title"><span>👤 Crear / Editar Usuario</span></div>

                    <?php if(isset($mensaje_usuario)): ?>
                        <div class="mensaje-exito"><?php echo $mensaje_usuario; ?></div>
                    <?php endif; ?>
                    <?php if(isset($error_usuario)): ?>
                        <div class="mensaje-error"><?php echo $error_usuario; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="guardar_usuario">
                        <input type="hidden" name="id" id="usuario_id" value="0">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre</label>
                                <input type="text" name="nombre" id="usuario_nombre" placeholder="Nombre" required>
                            </div>
                            <div class="form-group">
                                <label>Usuario</label>
                                <input type="text" name="usuario" id="usuario_usuario" placeholder="Usuario" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="usuario_email" placeholder="Email" required>
                        </div>

                        <div class="form-group">
                            <label>Contraseña <span style="color:rgba(255,255,255,0.2);font-size:0.8em;">(dejar vacío para no cambiar)</span></label>
                            <input type="password" name="password" id="usuario_password" placeholder="Nueva contraseña...">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Rol</label>
                                <select name="rol" id="usuario_rol">
                                    <option value="novia">💕 Novia</option>
                                    <option value="admin">👑 Admin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="checkbox-group">
                                    <input type="checkbox" name="activo" id="usuario_activo" checked>
                                    <label for="usuario_activo">✅ Activo</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">💾 Guardar Usuario</button>
                        <button type="button" class="btn-submit" style="background:rgba(255,255,255,0.05);margin-left:10px;" onclick="limpiarFormulario()">✕ Cancelar</button>
                    </form>
                </div>

                <!-- Lista Usuarios -->
                <div class="card">
                    <div class="card-title"><span>👥 Usuarios</span><span class="badge"><?php echo $total_usuarios; ?> total</span></div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $usuarios->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($user['usuario']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo $user['rol'] == 'admin' ? '👑 Admin' : '💕 Novia'; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $user['activo'] ? 'activo' : 'inactivo'; ?>">
                                                <?php echo $user['activo'] ? '✅ Activo' : '⏸️ Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-small edit" onclick="editarUsuario(<?php echo $user['id']; ?>, '<?php echo addslashes($user['nombre']); ?>', '<?php echo addslashes($user['usuario']); ?>', '<?php echo addslashes($user['email']); ?>', '<?php echo $user['rol']; ?>', <?php echo $user['activo']; ?>)">✏️</button>
                                            <?php if($user['id'] != $_SESSION['usuario_id']): ?>
                                                <a href="admin.php?eliminar_usuario=<?php echo $user['id']; ?>" class="btn-small delete" onclick="return confirm('¿Eliminar este usuario?')">🗑️</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA -->
            <div>

                <!-- Configurar Invitación -->
                <div class="card">
                    <div class="card-title"><span>💌 Configurar Invitación</span><span class="badge"><?php echo $invitacion ? '✅ Activa' : '⏸️ Inactiva'; ?></span></div>

                    <?php if(isset($mensaje_invitacion)): ?>
                        <div class="mensaje-exito"><?php echo $mensaje_invitacion; ?></div>
                    <?php endif; ?>
                    <?php if(isset($error_invitacion)): ?>
                        <div class="mensaje-error"><?php echo $error_invitacion; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="guardar_invitacion">
                        <input type="hidden" name="id" value="<?php echo $invitacion['id'] ?? 0; ?>">

                        <div class="form-group">
                            <label>Título</label>
                            <input type="text" name="titulo" value="<?php echo htmlspecialchars($invitacion['titulo'] ?? 'Invitación Especial 💕'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Mensaje</label>
                            <textarea name="mensaje"><?php echo htmlspecialchars($invitacion['mensaje'] ?? 'Cari ❤️, quisiera invitarte a ver conmigo el partido de fútbol.'); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Evento</label>
                            <input type="text" name="evento" value="<?php echo htmlspecialchars($invitacion['evento'] ?? 'Portugal vs Croacia'); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Fecha y Hora</label>
                                <input type="datetime-local" name="fecha" value="<?php echo $invitacion ? date('Y-m-d\TH:i', strtotime($invitacion['fecha'])) : date('Y-m-d\TH:i'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Lugar</label>
                                <input type="text" name="lugar" value="<?php echo htmlspecialchars($invitacion['lugar'] ?? 'En nuestra casa 🏠'); ?>" required>
                            </div>
                        </div>

                        <div class="checkbox-group">
                            <input type="checkbox" name="activa" id="invitacion_activa" <?php echo ($invitacion && $invitacion['activa']) ? 'checked' : ''; ?>>
                            <label for="invitacion_activa">✅ Activa (visible para la novia)</label>
                        </div>

                        <button type="submit" class="btn-submit">💾 Guardar Invitación</button>
                    </form>
                </div>

                <!-- Últimas Respuestas -->
                <div class="card">
                    <div class="card-title"><span>📋 Últimas Respuestas</span><span class="badge"><?php echo $total_respuestas; ?> total</span></div>

                    <?php if($respuestas->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Respuesta</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $respuestas->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $row['respuesta']; ?>">
                                                    <?php echo $row['respuesta'] == 'aceptada' ? '✅ Aceptó' : '❌ Rechazó'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_respuesta'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center;padding:30px;color:rgba(255,255,255,0.2);">
                            <div style="font-size:3em;margin-bottom:10px;">💌</div>
                            <p>No hay respuestas aún</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </div>

    <script>
        function editarUsuario(id, nombre, usuario, email, rol, activo) {
            document.getElementById('usuario_id').value = id;
            document.getElementById('usuario_nombre').value = nombre;
            document.getElementById('usuario_usuario').value = usuario;
            document.getElementById('usuario_email').value = email;
            document.getElementById('usuario_rol').value = rol;
            document.getElementById('usuario_activo').checked = activo == 1;
            document.getElementById('usuario_password').value = '';
            document.getElementById('usuario_password').placeholder = 'Nueva contraseña...';
            document.querySelector('.card').scrollIntoView({ behavior: 'smooth' });
        }

        function limpiarFormulario() {
            document.getElementById('usuario_id').value = 0;
            document.getElementById('usuario_nombre').value = '';
            document.getElementById('usuario_usuario').value = '';
            document.getElementById('usuario_email').value = '';
            document.getElementById('usuario_password').value = '';
            document.getElementById('usuario_rol').value = 'novia';
            document.getElementById('usuario_activo').checked = true;
            document.getElementById('usuario_password').placeholder = 'Nueva contraseña...';
        }
    </script>

</body>
</html>