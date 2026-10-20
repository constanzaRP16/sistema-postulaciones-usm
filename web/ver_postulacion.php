<?php
// Este archivo se encarga de mostrar el detalle completo de una postulación específica.
// Se accede a este archivo desde los dashboards (Responsable, Evaluador, Admin)

session_start(); //Esto recuerda quien inicio sesion.
error_reporting(E_ALL); // Configuración para mostrar errores.
ini_set('display_errors', 1); // Mostrar errores en pantalla.

// Validar que el usuario haya iniciado sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php"); // Redirigimos al index si no ha iniciado sesión.
    exit();
}

require 'conexion.php'; // Incluimos la conexión a la base de datos 

// Obtener el ID de la postulación desde la URL
$id_postulacion = $_GET['id'] ?? null;

if (!$id_postulacion) { // Si no se especifica un ID de postulación, mostramos un error.
    die("Error: ID de postulación no especificado.");
}

// Aquí realizaremos varias consultas para obtener toda la información relacionada con la postulación:
try { 
    // Obtener datos generales de la Postulación y llamar a la FUNCIÓN SQL calcular_iva()
    // Usamos JOIN para obtener información relacionada de la sede, región, estado y tipo de iniciativa en una sola consulta.
    // La función SQL calcular_iva() se encarga de calcular el IVA basado en el presupuesto total registrado en la postulación.
    // De postulación obtenemos campos como nombre de la iniciativa, objetivo, descripción, resultados esperados, presupuesto total, etc. para mostrar en el detalle.
    $sqlPost = "SELECT p.*, s.nombre_sede, 
                       re.nombre_region AS region_ejecucion, 
                       ri.nombre_region AS region_impacto, 
                       ep.descripcion AS estado_desc, 
                       ti.des_iniciativa,
                       calcular_iva(p.presupuesto_total) AS iva_calculado              
                FROM postulacion p            
                JOIN sede s ON p.id_sede = s.id_sede                                   
                JOIN region re ON p.id_region_ejecucion = re.id_region                
                JOIN region ri ON p.id_region_impacto = ri.id_region                  
                JOIN estado_postulacion ep ON p.id_estado = ep.id_estado              
                JOIN tipo_iniciativa ti ON p.id_tipo_iniciativa = ti.id_tipo_iniciativa
                WHERE p.id_postulacion = :id";
                
    $stmt = $pdo->prepare($sqlPost); // Preparamos la consulta para evitar inyección SQL.
    $stmt->execute(['id' => $id_postulacion]); // Ejecutamos la consulta con el ID de postulación como parámetro.
    $postulacion = $stmt->fetch(PDO::FETCH_ASSOC); // Obtenemos la información de la postulación como un array asociativo.

    if (!$postulacion) { // Si no se encuentra la postulación con el ID especificado, mostramos un error.
        die("Error: La postulación solicitada no existe.");
    }

    // Obtener datos de la Empresa asociada
    // Usamos el RUT de la empresa registrado en la postulación para obtener toda la información de la empresa y su tamaño.
    $sqlEmp = "SELECT e.*, t.des_empresa 
               FROM empresa e  
               JOIN tamano_empresa t ON e.id_tamano = t.id_tamano 
               WHERE e.rut_empresa = :rut";

    $stmtEmp = $pdo->prepare($sqlEmp);
    $stmtEmp->execute(['rut' => $postulacion['rut_empresa']]);
    $empresa = $stmtEmp->fetch(PDO::FETCH_ASSOC);

    // Obtener el Equipo de Trabajo e Integrantes
    // Aquí obtenemos la información de los integrantes del equipo de trabajo asociados a esta postulación, incluyendo su rol, departamento y sede.
    $sqlTeam = "SELECT i.*, ti.des_integrante, s.nombre_sede AS sede_integrante_nom
                FROM integrante i
                JOIN tipo_integrante ti ON i.id_tipo_integrante = ti.id_tipo_integrante
                JOIN sede s ON i.id_sede = s.id_sede
                JOIN equipo_de_trabajo eq ON i.id_equipo = eq.id_equipo
                WHERE eq.id_postulacion = :id";

    $stmtTeam = $pdo->prepare($sqlTeam);
    $stmtTeam->execute(['id' => $id_postulacion]);
    $integrantes = $stmtTeam->fetchAll(PDO::FETCH_ASSOC);

    // Obtener las Etapas del Cronograma
    // Aquí obtenemos las etapas del cronograma de trabajo asociadas a esta postulación, incluyendo el nombre de la etapa, plazos y entregables.
    $sqlEtapas = "SELECT * FROM etapa WHERE id_postulacion = :id";
    $stmtEtapas = $pdo->prepare($sqlEtapas);
    $stmtEtapas->execute(['id' => $id_postulacion]);
    $etapas = $stmtEtapas->fetchAll(PDO::FETCH_ASSOC);



} catch (PDOException $e) { // Capturamos cualquier error que ocurra al consultar la base de datos y mostramos un mensaje de error.
    die("Error al cargar los detalles: " . $e->getMessage());
}

// Determinar a qué panel regresar según el rol del usuario conectado
$panel_regreso = "dashboard_responsable.php";
if ($_SESSION['id_rol'] == 2) $panel_regreso = "dashboard_evaluador.php";
if ($_SESSION['id_rol'] == 3) $panel_regreso = "dashboard_admin.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Postulación N° <?= $postulacion['id_postulacion'] ?> - CT-USM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= $panel_regreso ?>">← Volver al Panel</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Postulación N° <?= $postulacion['id_postulacion'] ?>: <span class="text-primary"><?= htmlspecialchars($postulacion['nombre_iniciativa']) ?></span></h2>
        <span class="fs-5 badge bg-primary p-2">Estado: <?= htmlspecialchars($postulacion['estado_desc']) ?></span>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white fw-bold">1. Antecedentes del Formulario</div>
                <div class="card-body bg-white">
                    <table class="table table-bordered">
                        <tr>
                            <th class="w-30 bg-light">Fecha Postulación</th>
                            <td><?= htmlspecialchars($postulacion['fecha_postulacion']) ?></td>
                        </tr>
                        <tr>
                            <th class="bg-light">Sede Universitaria</th>
                            <td><?= htmlspecialchars($postulacion['nombre_sede']) ?></td>
                        </tr>
                        <tr>
                            <th class="bg-light">Tipo Iniciativa</th>
                            <td><?= htmlspecialchars($postulacion['des_iniciativa']) ?></td>
                        </tr>
                        <tr>
                            <th class="bg-light">Región Ejecución</th>
                            <td><?= htmlspecialchars($postulacion['region_ejecucion']) ?></td>
                        </tr>
                        <tr>
                            <th class="bg-light">Región Impacto</th>
                            <td><?= htmlspecialchars($postulacion['region_impacto']) ?></td>
                        </tr>
                        <tr>
                            <th class="bg-light">Jefe de Carrera / Coordinador</th>
                            <td><?= htmlspecialchars($postulacion['jefe_carrera']) ?> / <?= htmlspecialchars($postulacion['coordinador_proyectos']) ?></td>
                        </tr>
                    </table>

                    <h5 class="mt-4 fw-bold text-secondary">Objetivo</h5>
                    <p class="border p-2 bg-light rounded"><?= nl2br(htmlspecialchars($postulacion['objetivo'])) ?></p>

                    <h5 class="fw-bold text-secondary">Descripción de la Solución</h5>
                    <p class="border p-2 bg-light rounded"><?= nl2br(htmlspecialchars($postulacion['descripcion_solucion'])) ?></p>

                    <h5 class="fw-bold text-secondary">Resultados Esperados</h5>
                    <p class="border p-2 bg-light rounded"><?= nl2br(htmlspecialchars($postulacion['resultados_esperados'])) ?></p>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white fw-bold">2. Cronograma de Trabajo (Etapas)</div>
                <div class="card-body bg-white">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nombre Etapa</th>
                                <th>Plazo (Semanas)</th>
                                <th>Entregable Asociado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($etapas as $etapa): ?>
                                <tr>
                                    <td><?= htmlspecialchars($etapa['nombre_etapa']) ?></td>
                                    <td><?= htmlspecialchars($etapa['plazos_semanas']) ?> semanas</td>
                                    <td><?= htmlspecialchars($etapa['entregable']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4 border-primary">
                <div class="card-header bg-primary text-white fw-bold">3. Aspectos Financieros</div>
                <div class="card-body bg-white text-center">
                    <h6 class="text-muted">Presupuesto Neto</h6>
                    <h3 class="text-dark fw-bold">$<?= number_format($postulacion['presupuesto_total'], 0, ',', '.') ?></h3>
                    <hr>
                    <h6 class="text-muted">IVA Calculado (Vía Función SQL)</h6>
                    <h5 class="text-danger fw-bold">+$<?= number_format($postulacion['iva_calculado'], 0, ',', '.') ?></h5>
                    <hr>
                    <h6 class="text-muted">Total Estimado</h6>
                    <h4 class="text-success fw-bold">$<?= number_format($postulacion['presupuesto_total'] + $postulacion['iva_calculado'], 0, ',', '.') ?></h4>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white fw-bold">4. Entidad External Asociada</div>
                <div class="card-body bg-white">
                    <?php if ($empresa): ?>
                        <h5 class="fw-bold text-success"><?= htmlspecialchars($empresa['nombre_empresa']) ?></h5>
                        <p class="mb-1"><strong>RUT:</strong> <?= htmlspecialchars($empresa['rut_empresa']) ?></p>
                        <p class="mb-1"><strong>Tamaño:</strong> <?= htmlspecialchars($empresa['des_empresa']) ?></p>
                        <p class="mb-1"><strong>Convenio Marco:</strong> <?= $empresa['convenio_marco'] ? 'Sí tiene' : 'No tiene' ?></p>
                        <hr>
                        <p class="mb-1"><strong>Representante:</strong> <?= htmlspecialchars($empresa['nombre_representante']) ?></p>
                        <p class="mb-1"><strong>Contacto:</strong> <?= htmlspecialchars($empresa['telefono_representante']) ?></p>
                        <p class="mb-0"><small class="text-muted"><?= htmlspecialchars($empresa['mail_representante']) ?></small></p>
                    <?php else: ?>
                        <p class="text-muted">No hay información de la empresa registrada.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between align-items-center">
                    <span>5. Integrantes del Equipo</span>
                    
                    <?php if ($_SESSION['id_rol'] == 1): ?>
                        <a href="agregar_integrante.php?id_postulacion=<?= $postulacion['id_postulacion'] ?>" class="btn btn-sm btn-outline-dark fw-bold">+ Agregar</a>
                    <?php endif; ?>
                </div>
                <div class="card-body bg-white">
                    <?php if (count($integrantes) > 0): ?>
                        <?php foreach ($integrantes as $int): ?>
                            <div class="p-2 mb-2 bg-light border rounded">
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($int['nombre']) ?></h6>
                                <p class="mb-0 card-text text-muted" style="font-size: 0.85rem;">
                                    <strong>Rol:</strong> <?= htmlspecialchars($int['des_integrante']) ?><br>
                                    <strong>Depto:</strong> <?= htmlspecialchars($int['departamento_area']) ?><br>
                                    <strong>Sede:</strong> <?= htmlspecialchars($int['sede_integrante_nom']) ?><br>
                                    <strong>Email:</strong> <?= htmlspecialchars($int['email']) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No hay integrantes registrados en el equipo.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>