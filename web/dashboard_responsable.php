<?php
//Aqui nos redireccionamos desde el index.php, validamos que el usuario sea un Responsable.
// luego mostramos la interfaz para gestionar las postulaciones.

session_start(); //Esto recuerda quien inicio sesion.
error_reporting(E_ALL); // Configuración para mostrar errores.
ini_set('display_errors', 1); // Mostrar errores en pantalla.

// Validar que el usuario sea Responsable (Rol 1)
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header("Location: index.php"); // Redirigimos al index si no es responsable o no ha iniciado sesión.
    exit();
}

require 'conexion.php'; // Incluimos la conexión a la base de datos.

// Obtenemos todas las postulaciones para mostrarlas en la tabla del dashboard
try {
    // Llamamos al obtener_postulaciones() que es un procedimiento almacenado en la base de datos que devuelve
    //  todas las postulaciones con su información relacionada (empresa, sede, estado, etc.)
    $stmt = $pdo->query("CALL obtener_postulaciones()");
    $postulaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { //Capturamos cualquier error que ocurra al consultar la base de datos y mostramos un mensaje de error.
    die("Error al consultar la base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Responsable Académico</title>
    <!-- Bonus Frontend: Bootstrap[cite: 4] -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Barra de Navegación -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">CT-USM | Panel Responsable</a>
        <div class="d-flex">
            <span class="navbar-text me-3">
                Bienvenido, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
            </span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Listado General de Postulaciones</h2>
        <a href="buscar.php" class="btn btn-primary">Búsqueda con Filtros</a>
        <a href="crear_postulacion.php" class="btn btn-success">Nueva Postulación</a>
    </div>

    <!-- Tabla de resultados -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>N°</th>
                            <th>Iniciativa</th>
                            <th>Empresa</th>
                            <th>Sede</th>
                            <th>Región Ejecución</th>
                            <th>Presupuesto Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($postulaciones) > 0): ?>
                            <?php foreach ($postulaciones as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id_postulacion']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre_iniciativa']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre_empresa']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre_sede']) ?></td>
                                    <td><?= htmlspecialchars($row['region_ejecucion']) ?></td>
                                    <td>$<?= number_format($row['presupuesto_total'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($row['estado']) ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="ver_postulacion.php?id=<?= $row['id_postulacion'] ?>" class="btn btn-sm btn-info">Ver detalle</a>
                                            <a href="editar_postulacion.php?id=<?= $row['id_postulacion'] ?>" class="btn btn-sm btn-warning text-dark">Editar</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No hay postulaciones registradas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>