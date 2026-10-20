<?php
// Aqui es donde agregamos más integrantes a un equipo de trabajo ya creado para una postulación específica. 
// Solo el Responsable Académico (Rol 1) puede acceder a esta página para gestionar su equipo.

session_start(); //Esto recuerda quien inicio sesion.
error_reporting(E_ALL); // Configuración para mostrar errores.
ini_set('display_errors', 1); // Mostrar errores en pantalla.


// Solo el Responsable Académico (Rol 1) puede gestionar su equipo
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header("Location: index.php");
    exit();
}

require 'conexion.php'; // Incluimos la conexión a la base de datos.
$mensaje = "";
$tipo_mensaje = "";

$id_postulacion = $_GET['id_postulacion'] ?? null; // Obtenemos el ID de la postulación

if (!$id_postulacion) { // Validamos que se haya proporcionado un ID de postulación
    die("Error: ID de postulación no especificado."); 
}

// Conseguir el id_equipo que corresponde a la postulación
try {
    $stmtEquipo = $pdo->prepare("SELECT id_equipo FROM equipo_de_trabajo WHERE id_postulacion = ?");
    $stmtEquipo->execute([$id_postulacion]); // Ejecutamos la consulta para obtener el equipo asociado a esta postulación
    $equipo = $stmtEquipo->fetch(PDO::FETCH_ASSOC);

    if (!$equipo) { // Validamos que la postulación tenga un equipo de trabajo asociado
        die("Error: Esta postulación no tiene configurado un equipo de trabajo.");
    }
    $id_equipo = $equipo['id_equipo'];

} catch (PDOException $e) { // Capturamos cualquier error que ocurra al consultar el equipo y mostramos un mensaje de error.
    die("Error al consultar el equipo: " . $e->getMessage());
}

// Al enviar el formulario de nuevo integrante
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $stmtIns = $pdo->prepare("INSERT INTO integrante (id_equipo, rut_integrante, nombre, departamento_area, id_sede, email, telefono, id_tipo_integrante) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        // Ejecutamos la inserción con los datos del formulario y el id_equipo obtenido previamente
        $stmtIns->execute([
            $id_equipo, 
            $_POST['rut_integrante'],
            $_POST['nombre'],
            $_POST['departamento_area'],
            $_POST['id_sede'],
            $_POST['email'],
            $_POST['telefono'],
            $_POST['id_tipo_integrante']
        ]);

        $mensaje = "¡Integrante agregado con éxito al equipo!";
        $tipo_mensaje = "success";
    } catch (PDOException $e) {
        $mensaje = "Error al agregar integrante: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Integrante - CT-USM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width: 600px;">
    <div class="card shadow">
        <div class="card-header bg-primary text-white fw-bold">Añadir Nuevo Miembro al Equipo</div>
        <div class="card-body">
            
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje ?>"><?= $mensaje ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">RUT Integrante</label>
                    <input type="text" name="rut_integrante" class="form-control" placeholder="12.345.678-9" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Departamento o Área</label>
                    <input type="text" name="departamento_area" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sede / Campus</label>
                    <select name="id_sede" class="form-select" required>
                        <option value="1">Campus Casa Central Valparaíso</option>
                        <option value="2">Campus San Joaquín</option>
                        <option value="3">Campus Vitacura</option>
                        <option value="4">Sede Viña del Mar</option>
                        <option value="5">Sede Concepción</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Rol que cumple</label>
                    <select name="id_tipo_integrante" class="form-select" required>
                        <option value="1">Alumno Postulante</option>
                        <option value="2">Profesor Co-investigador</option>
                        <option value="3">Asesor Técnico Externo</option>
                    </select>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="ver_postulacion.php?id=<?= $id_postulacion ?>" class="btn btn-secondary">Volver al Detalle</a>
                    <button type="submit" class="btn btn-success">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>