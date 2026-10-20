<?php
//Aqui nos redireccionamos desde el index.php, validamos que el usuario sea un Evaluador.
// luego mostramos la interfaz para gestionar las postulaciones.

session_start(); //Esto recuerda quien inicio sesion.
error_reporting(E_ALL); // Configuración para mostrar errores.
ini_set('display_errors', 1); // Mostrar errores en pantalla.

// Validar que el usuario sea Evaluador (Rol 2)
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 2) {
    header("Location: index.php"); // Redirigimos al index si no es evaluador o no ha iniciado sesión.
    exit();
}

require 'conexion.php'; // Incluimos la conexión a la base de datos.
$mensaje = ""; // Variable para mostrar mensajes de éxito o error al usuario.
$tipo_mensaje = "";

// Lógica para CAMBIAR el estado de una Postulación (En Revisión, Aprobada, Rechazada, Cerrada)
// Si el formulario de cambio de estado fue enviado y tiene el campo 'cambiar_estado'
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cambiar_estado'])) {

    // Obtenemos el ID de la postulación y el nuevo estado seleccionado
    $id_postulacion = $_POST['id_postulacion'];
    $nuevo_estado = $_POST['id_estado'];

    // Aqui actualizamos el estado de la postulación en la base de datos si pertenece al evaluador
    try {
        // SEGURIDAD: Consultamos primero a la base de datos para verificar que el coordinador de la postulación coincida con el usuario en sesión
        $stmtCheck = $pdo->prepare("SELECT coordinador_proyectos FROM postulacion WHERE id_postulacion = ?");
        $stmtCheck->execute([$id_postulacion]);
        $post = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($post && $post['coordinador_proyectos'] === $_SESSION['username']) {
            // Si coincide el evaluador asignado, procedemos a realizar la actualización
            // Actualizamos el estado de la postulación usando el campo 'id_estado' en la tabla 'postulacion'
            $sqlUpdate = "UPDATE postulacion SET id_estado = :id_estado WHERE id_postulacion = :id_postulacion";
            
            $stmtUpdate = $pdo->prepare($sqlUpdate); // Preparamos la consulta para evitar inyección SQL.
            $stmtUpdate->execute([ 
                ':id_estado' => $nuevo_estado, //Guardamos el nuevo estado seleccionado.
                ':id_postulacion' => $id_postulacion // Identificamos la postulación a actualizar.
            ]);
            
            $mensaje = "¡Estado de la postulación N° $id_postulacion actualizado con éxito!";
            $tipo_mensaje = "success";
        } else {
            // Si un usuario malintencionado intenta forzar el POST desde otra postulación, bloqueamos el cambio
            $mensaje = "Error: No tienes permisos para gestionar postulaciones que no te corresponden.";
            $tipo_mensaje = "danger";
        }

    } catch (PDOException $e) { // Si ocurre un error al actualizar, lo capturamos y mostramos un mensaje de error.
        $mensaje = "Error al actualizar el estado: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtenemos todas las postulaciones para mostrarlas en la tabla del dashboard
try {
    // Llamamos al obtener_postulaciones() que es un procedimiento almacenado en la base de datos que devuelve
    //  todas las postulaciones con su información relacionada (empresa, sede, estado, etc.)
    $sql = "SELECT p.*, e.nombre_empresa, s.nombre_sede, ep.descripcion AS estado
            FROM postulacion p
            JOIN empresa e ON p.rut_empresa = e.rut_empresa
            JOIN sede s ON p.id_sede = s.id_sede
            JOIN estado_postulacion ep ON p.id_estado = ep.id_estado";
            
    $stmt = $pdo->query($sql); // Ejecutamos la consulta para obtener todas las postulaciones con su información relacionada.
    $postulaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { //Capturamos cualquier error que ocurra al consultar la base de datos y mostramos un mensaje de error.
    die("Error al consultar las postulaciones: " . $e->getMessage());
}
?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Evaluación - CT-USM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-secondary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">CT-USM | Panel Evaluador</a>
        <div class="d-flex">
            <span class="navbar-text me-3 text-white">
                Evaluador: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Control y Evaluación de Iniciativas</h2>
        <a href="buscar.php" class="btn btn-primary">Búsqueda con Filtros</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>N°</th>
                            <th>Iniciativa</th>
                            <th>Empresa</th>
                            <th>Sede</th>
                            <th>Presupuesto Total</th>
                            <th>Estado Actual</th>
                            <th>Gestionar Estado</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($postulaciones) > 0): ?>
                            <?php foreach ($postulaciones as $row): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['id_postulacion']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['nombre_iniciativa']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre_empresa']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre_sede']) ?></td>
                                    <td>$<?= number_format($row['presupuesto_total'], 0, ',', '.') ?></td>
                                    <td>
                                        <?php 
                                            $badge_class = "bg-secondary";
                                            if($row['estado'] == 'Aprobada') $badge_class = "bg-success";
                                            if($row['estado'] == 'Rechazada') $badge_class = "bg-danger";
                                            if($row['estado'] == 'Cerrada') $badge_class = "bg-dark";
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($row['estado']) ?></span>
                                    </td>
                                    <td>
                                        <?php if (isset($row['coordinador_proyectos']) && $row['coordinador_proyectos'] === $_SESSION['username']): ?>
                                            <form method="POST" action="" class="d-flex gap-2">
                                                <input type="hidden" name="id_postulacion" value="<?= $row['id_postulacion'] ?>">
                                                <select name="id_estado" class="form-select form-select-sm" style="min-width: 130px;" required>
                                                    <option value="1" <?= $row['estado'] == 'En Revisión' ? 'selected' : '' ?>>En Revisión</option>
                                                    <option value="2" <?= $row['estado'] == 'Aprobada' ? 'selected' : '' ?>>Aprobada</option>
                                                    <option value="3" <?= $row['estado'] == 'Rechazada' ? 'selected' : '' ?>>Rechazada</option>
                                                    <option value="4" <?= $row['estado'] == 'Cerrada' ? 'selected' : '' ?>>Cerrada</option>
                                                </select>
                                                <button type="submit" name="cambiar_estado" class="btn btn-sm btn-success">✓</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small"><i>No asignado a ti</i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="ver_postulacion.php?id=<?= $row['id_postulacion'] ?>" class="btn btn-sm btn-outline-info">Ver Todo</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No existen postulaciones en el sistema.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>