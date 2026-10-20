<?php
// Este archivo nos permite editar una postulación existente desde el dashboard del Responsable (Rol 1).
// Aquí se cargan los datos actuales de la postulación para modificarlos y guardarlos en la base de datos.

session_start(); //Esto recuerda quien inicio sesion.
error_reporting(E_ALL); // Configuración para mostrar errores.
ini_set('display_errors', 1); // Mostrar errores en pantalla.

// Validar que solo el Responsable (Rol 1) pueda entrar
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header("Location: index.php");
    exit();
}

require 'conexion.php'; // Incluimos la conexión a la base de datos.
$mensaje = ""; // Variable para mostrar mensajes de éxito o error al usuario.
$tipo_mensaje = ""; 

// Obtener el ID de la postulación desde la URL
$id_postulacion = $_GET['id'] ?? null;

if (!$id_postulacion) { // Si no se especifica un ID, detenemos la ejecución.
    die("Error: ID de postulación no especificado.");
}

// Cargar los datos actuales de la postulación para rellenar los campos y verificar su estado
try {
    $stmt = $pdo->prepare("SELECT * FROM postulacion WHERE id_postulacion = ?");
    $stmt->execute([$id_postulacion]);
    $postulacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$postulacion) {
        die("Error: La postulación solicitada no existe.");
    }

    // CONTROL DE FLUJO: Si la postulación ya fue Aprobada (2), Rechazada (3) o Cerrada (4), bloqueamos la edición
    if ($postulacion['id_estado'] == 2 || $postulacion['id_estado'] == 3 || $postulacion['id_estado'] == 4) {
        die("Error: No está permitido editar postulaciones que ya han sido aprobadas, rechazadas o cerradas por el comité evaluador.");
    }

} catch (PDOException $e) {
    die("Error al cargar datos: " . $e->getMessage());
}

// Si el formulario de edición fue enviado, procesamos la actualización
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Ejecutamos la sentencia UPDATE para modificar la cabecera de la postulación
        $sqlUpdate = "UPDATE postulacion SET 
                        nombre_iniciativa = ?, 
                        id_tipo_iniciativa = ?, 
                        id_sede = ?, 
                        presupuesto_total = ?, 
                        jefe_carrera = ?, 
                        coordinador_proyectos = ?, 
                        id_region_ejecucion = ?, 
                        id_region_impacto = ?, 
                        objetivo = ?, 
                        descripcion_solucion = ?, 
                        resultados_esperados = ? 
                      WHERE id_postulacion = ?";
        
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            $_POST['nombre_iniciativa'],
            $_POST['tipo_iniciativa'],
            $_POST['id_sede'],
            $_POST['presupuesto'],
            $_POST['jefe_carrera'],
            $_POST['coordinador'],
            $_POST['id_region_ejecucion'],
            $_POST['id_region_impacto'],
            $_POST['objetivo'],
            $_POST['descripcion'],
            $_POST['resultados'],
            $id_postulacion
        ]);

        $mensaje = "¡Postulación N° $id_postulacion actualizada con éxito!";
        $tipo_mensaje = "success";
        
        // Recargamos los datos recién actualizados para que se muestren vigentes en el formulario
        $stmt->execute([$id_postulacion]);
        $postulacion = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $mensaje = "Error al actualizar la postulación: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Postulación N° <?= $id_postulacion ?> - CT-USM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard_responsable.php">← Volver al Dashboard</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <h2 class="mb-4">Modificar Formulario de Postulación (N° <?= $id_postulacion ?>)</h2>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white fw-bold">1. Antecedentes de la Iniciativa</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Nombre de la Iniciativa</label>
                        <input type="text" name="nombre_iniciativa" class="form-control" value="<?= htmlspecialchars($postulacion['nombre_iniciativa']) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tipo de Iniciativa</label>
                        <select name="tipo_iniciativa" class="form-select" required>
                            <option value="1" <?= $postulacion['id_tipo_iniciativa'] == 1 ? 'selected' : '' ?>>Nueva</option>
                            <option value="2" <?= $postulacion['id_tipo_iniciativa'] == 2 ? 'selected' : '' ?>>Existente</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sede / Campus</label>
                        <select name="id_sede" class="form-select" required>
                            <option value="1" <?= $postulacion['id_sede'] == 1 ? 'selected' : '' ?>>Campus Casa Central Valparaíso</option>
                            <option value="2" <?= $postulacion['id_sede'] == 2 ? 'selected' : '' ?>>Campus San Joaquín</option>
                            <option value="3" <?= $postulacion['id_sede'] == 3 ? 'selected' : '' ?>>Campus Vitacura</option>
                            <option value="4" <?= $postulacion['id_sede'] == 4 ? 'selected' : '' ?>>Sede Viña del Mar</option>
                            <option value="5" <?= $postulacion['id_sede'] == 5 ? 'selected' : '' ?>>Sede Concepción</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Presupuesto Total (CLP)</label>
                        <input type="number" name="presupuesto" class="form-control" value="<?= htmlspecialchars($postulacion['presupuesto_total']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jefe de Carrera</label>
                        <input type="text" name="jefe_carrera" class="form-control" value="<?= htmlspecialchars($postulacion['jefe_carrera']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Coordinador de Proyectos</label>
                        <input type="text" name="coordinador" class="form-control" value="<?= htmlspecialchars($postulacion['coordinador_proyectos']) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Región de Ejecución (ID 1-16)</label>
                        <input type="number" name="id_region_ejecucion" class="form-control" min="1" max="16" value="<?= htmlspecialchars($postulacion['id_region_ejecucion']) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Región de Impacto (ID 1-16)</label>
                        <input type="number" name="id_region_impacto" class="form-control" min="1" max="16" value="<?= htmlspecialchars($postulacion['id_region_impacto']) ?>" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Objetivo</label>
                        <textarea name="objetivo" class="form-control" rows="2" required><?= htmlspecialchars($postulacion['objetivo']) ?></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Descripción de la Solución</label>
                        <textarea name="descripcion" class="form-control" rows="2" required><?= htmlspecialchars($postulacion['descripcion_solucion']) ?></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Resultados Esperados</label>
                        <textarea name="resultados" class="form-control" rows="2" required><?= htmlspecialchars($postulacion['resultados_esperados']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="dashboard_responsable.php" class="btn btn-secondary">← Volver al Panel</a>
            <button type="submit" class="btn btn-warning fw-bold text-dark">Guardar Cambios</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>