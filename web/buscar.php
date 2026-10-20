<?php
// Este archivo se encarga de manejar la lógica de búsqueda avanzada de postulaciones con filtros
// Usaremos filtros de nombre, sede y estado.

session_start(); //Esto recuerda quien inicio sesion.
error_reporting(E_ALL); // Configuración para mostrar errores.
ini_set('display_errors', 1); // Mostrar errores en pantalla.

require 'conexion.php'; // Incluimos la conexión a la base de datos.

// Validar que el usuario haya iniciado sesión (cualquier rol) para acceder a esta página de búsqueda avanzada
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php"); // Redirigimos al index si no ha iniciado sesión.
    exit();
}

// Variables para mantener los valores seleccionados en el formulario
$filtro_nombre = $_GET['nombre'] ?? ''; 
$filtro_sede = $_GET['sede'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';

// Construcción dinámica de la consulta SQL
// Seleccionamos de la vista 'vista_postulaciones' que ya tiene toda la información necesaria para mostrar en los resultados
$sql = "SELECT * FROM vista_postulaciones WHERE 1=1";
$parametros = []; // Array para almacenar los parámetros de la consulta

// Si el usuario escribió algo en "Nombre de Iniciativa"
if (!empty($filtro_nombre)) {
    $sql .= " AND nombre_iniciativa LIKE :nombre"; //Busqueda por coincidencia en el nombre de la iniciativa usando LIKE.
    $parametros[':nombre'] = "%$filtro_nombre%"; // Agregamos los comodines % para buscar cualquier parte del texto.
}

// Si el usuario seleccionó una sede específica
if (!empty($filtro_sede)) {
    $sql .= " AND nombre_sede = :sede"; //Busqueda por coincidencia exacta en el nombre de la sede.
    $parametros[':sede'] = $filtro_sede; // Agregamos el valor seleccionado al array de parámetros.
}

// Si el usuario seleccionó un estado específico
if (!empty($filtro_estado)) {
    $sql .= " AND estado = :estado"; //Busqueda por coincidencia exacta en el estado de la postulación.
    $parametros[':estado'] = $filtro_estado; // Agregamos el valor seleccionado al array de parámetros.
}

// Ejecutamos la consulta con los filtros aplicados y obtenemos los resultados
try {
    $stmt = $pdo->prepare($sql); // Preparamos la consulta para evitar inyección SQL.
    $stmt->execute($parametros); // Ejecutamos la consulta con los parámetros correspondientes.
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC); // Obtenemos todos los resultados que coinciden con los filtros aplicados.

} catch (PDOException $e) { //Capturamos cualquier error que ocurra al ejecutar la consulta y mostramos un mensaje de error.
    die("Error en la búsqueda: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Búsqueda Avanzada - CT-USM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <?php
            $volver = "index.php";
            if($_SESSION['id_rol'] == 1) $volver = "dashboard_responsable.php";
            if($_SESSION['id_rol'] == 2) $volver = "dashboard_evaluador.php";
            if($_SESSION['id_rol'] == 3) $volver = "dashboard_admin.php";
        ?>
        <a class="navbar-brand" href="<?= $volver ?>">← Volver al Dashboard</a>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4">Búsqueda Avanzada de Postulaciones</h2>

    <div class="card shadow mb-4">
        <div class="card-body bg-white">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nombre de Iniciativa</label>
                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($filtro_nombre) ?>" placeholder="Ej: Sistema...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sede / Campus</label>
                        <select name="sede" class="form-select">
                            <option value="">Todas las sedes</option>
                            <option value="Campus Casa Central Valparaíso" <?= $filtro_sede == 'Campus Casa Central Valparaíso' ? 'selected' : '' ?>>Casa Central Valparaíso</option>
                            <option value="Campus San Joaquín" <?= $filtro_sede == 'Campus San Joaquín' ? 'selected' : '' ?>>Campus San Joaquín</option>
                            <option value="Campus Vitacura" <?= $filtro_sede == 'Campus Vitacura' ? 'selected' : '' ?>>Campus Vitacura</option>
                            <option value="Sede Viña del Mar" <?= $filtro_sede == 'Sede Viña del Mar' ? 'selected' : '' ?>>Sede Viña del Mar</option>
                            <option value="Sede Concepción" <?= $filtro_sede == 'Sede Concepción' ? 'selected' : '' ?>>Sede Concepción</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="En Revisión" <?= $filtro_estado == 'En Revisión' ? 'selected' : '' ?>>En Revisión</option>
                            <option value="Aprobada" <?= $filtro_estado == 'Aprobada' ? 'selected' : '' ?>>Aprobada</option>
                            <option value="Rechazada" <?= $filtro_estado == 'Rechazada' ? 'selected' : '' ?>>Rechazada</option>
                            <option value="Cerrada" <?= $filtro_estado == 'Cerrada' ? 'selected' : '' ?>>Cerrada</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <h5 class="card-title">Resultados (<?= count($resultados) ?> encontrados)</h5>
            <div class="table-responsive">
                <table class="table table-hover table-striped mt-3">
                    <thead class="table-dark">
                        <tr>
                            <th>N°</th>
                            <th>Iniciativa</th>
                            <th>Empresa</th>
                            <th>Sede</th>
                            <th>Regiones (Ejec/Imp)</th>
                            <th>Presupuesto</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($resultados) > 0): ?>
                            <?php foreach ($resultados as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id_postulacion']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre_iniciativa']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre_empresa']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre_sede']) ?></td>
                                    <td><small><?= htmlspecialchars($row['region_ejecucion']) ?> / <?= htmlspecialchars($row['region_impacto']) ?></small></td>
                                    <td>$<?= number_format($row['presupuesto_total'], 0, ',', '.') ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($row['estado']) ?></span></td>
                                    <td><a href="ver_postulacion.php?id=<?= $row['id_postulacion'] ?>" class="btn btn-sm btn-info">Ver</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No se encontraron postulaciones con esos filtros.</td>
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