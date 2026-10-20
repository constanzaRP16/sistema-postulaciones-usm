<?php
//Aqui nos redireccionamos desde el index.php, validamos que el usuario sea un Administrador.
// luego mostramos la interfaz para asignar evaluadores a las postulaciones.

session_start(); //Esto recuerda quien inicio sesion.
error_reporting(E_ALL); // Configuración para mostrar errores.
ini_set('display_errors', 1); // Mostrar errores en pantalla.

// Validar que el usuario sea Admin (Rol 3)
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 3) {
    header("Location: index.php"); // Redirigimos al index si no es admin o no ha iniciado sesión.
    exit();
}

require 'conexion.php'; // Incluimos la conexión a la base de datos.
$mensaje = ""; // Variable para mostrar mensajes de éxito o error al usuario.
$tipo_mensaje = "";

// Lógica para ASIGNAR un Evaluador a una Postulación
// Si el formulario de asignación fue enviado y tiene el campo 'asignar_evaluador'
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['asignar_evaluador'])) {

    // Obtenemos el ID de la postulación y el nombre del evaluador seleccionado
    $id_postulacion = $_POST['id_postulacion'];
    $nombre_evaluador = $_POST['nombre_evaluador'];

    // Aqui asignamos el evaluador a la postulación
    try {
        // Actualizamos la postulación con el nombre del evaluador asignado reemplacando el campo 'coordinador_proyectos'
        $sqlUpdate = "UPDATE postulacion SET coordinador_proyectos = :evaluador WHERE id_postulacion = :id_postulacion";
        
        $stmtUpdate = $pdo->prepare($sqlUpdate); // Preparamos la consulta para evitar inyección SQL.
        
        $stmtUpdate->execute([ // Ejecutamos la consulta con los parámetros correspondientes.
            ':evaluador' => $nombre_evaluador, //Guardamos nombre de evaluador.
            ':id_postulacion' => $id_postulacion // Identificamos la postulación a actualizar.
        ]);
        
        $mensaje = "¡Evaluador asignado correctamente a la postulación N° $id_postulacion!";
        $tipo_mensaje = "success";

    } catch (PDOException $e) { // Si ocurre un error al actualizar, lo capturamos y mostramos un mensaje de error.
        $mensaje = "Error al asignar evaluador: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

try {
    // Obtener todas las postulaciones
    $stmtPost = $pdo->query("CALL obtener_postulaciones()");
    $postulaciones = $stmtPost->fetchAll(PDO::FETCH_ASSOC);
    $stmtPost->closeCursor();

    // Obtener la lista de usuarios que son Evaluadores (Rol 2)
    $stmtEval = $pdo->query("SELECT username FROM usuarios WHERE id_rol = 2");
    $evaluadores = $stmtEval->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { //Capturamos cualquier error que ocurra al consultar la base de datos y mostramos un mensaje de error.
    die("Error en la base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración - CT-USM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">CT-USM | Administrador Global</a>
        <div class="d-flex">
            <span class="navbar-text me-3 text-white">
                Admin: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestión de Asignaciones y Evaluadores</h2>
        <a href="buscar.php" class="btn btn-outline-danger">Búsqueda con Filtros</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow border-danger">
        <div class="card-header bg-danger text-white fw-bold">
            Asignación de Evaluadores a Proyectos
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>N°</th>
                            <th>Iniciativa</th>
                            <th>Empresa</th>
                            <th>Estado</th>
                            <th>Evaluador Asignado</th>
                            <th>Reasignar Evaluador</th>
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
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($row['estado']) ?></span></td>
                                    
                                    <?php
                                        $stmtAsig = $pdo->prepare("SELECT coordinador_proyectos FROM postulacion WHERE id_postulacion = ?");
                                        $stmtAsig->execute([$row['id_postulacion']]);
                                        $asignado = $stmtAsig->fetchColumn();
                                    ?>
                                    <td class="fw-bold text-danger"><?= htmlspecialchars($asignado) ?></td>
                                    
                                    <td>
                                        <form method="POST" action="" class="d-flex gap-2">
                                            <input type="hidden" name="id_postulacion" value="<?= $row['id_postulacion'] ?>">
                                            <select name="nombre_evaluador" class="form-select form-select-sm" required>
                                                <option value="">Seleccione...</option>
                                                <?php foreach ($evaluadores as $ev): ?>
                                                    <option value="<?= htmlspecialchars($ev['username']) ?>"><?= htmlspecialchars($ev['username']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="asignar_evaluador" class="btn btn-sm btn-danger">Asignar</button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="ver_postulacion.php?id=<?= $row['id_postulacion'] ?>" class="btn btn-sm btn-outline-dark">Ver</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan=\"7\" class="text-center text-muted">No existen postulaciones.</td>
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