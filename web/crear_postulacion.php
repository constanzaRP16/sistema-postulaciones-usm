<?php
// Este archivo nos permite crear una nueva postulación desde el dashboard del Responsable (Rol 1). 
// Aquí se maneja un formulario completo para ingresar toda la información de la postulación, empresa, equipo de trabajo y cronograma.
// Al enviar el formulario, se guardan todos los datos relacionados en la base de datos utilizando transacciones para asegurar la integridad de los datos.

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

// Si el formulario fue enviado, procesamos los datos para crear la postulación
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {
        // Iniciamos la transacción para asegurar que todos los datos se guarden correctamente o se reviertan en caso de error
        $pdo->beginTransaction();


        // Guardar o actualizar la Empresa asociada a la postulación
        $stmtEmpresa = $pdo->prepare("INSERT INTO empresa (rut_empresa, nombre_empresa, id_tamano, nombre_representante, mail_representante, telefono_representante, convenio_marco) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?) 
                                      ON DUPLICATE KEY UPDATE nombre_empresa = VALUES(nombre_empresa)");
        
        $stmtEmpresa->execute([ // Guardamos estos datos
            $_POST['rut_empresa'], $_POST['nombre_empresa'], $_POST['id_tamano_empresa'],  
            $_POST['rep_empresa'], $_POST['mail_empresa'], $_POST['tel_empresa'], $_POST['convenio']
        ]);


        // Guardar la Postulación y obtener su ID para relacionarla con el equipo de trabajo y cronograma
        $stmtPost = $pdo->prepare("INSERT INTO postulacion (fecha_postulacion, id_sede, id_region_ejecucion, id_region_impacto, jefe_carrera, coordinador_proyectos, rut_empresa, id_tipo_iniciativa, nombre_iniciativa, objetivo, descripcion_solucion, resultados_esperados, id_estado, presupuesto_total) 
                                   VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
        
        $stmtPost->execute([ // Guardamos estos datos
            $_POST['id_sede'], $_POST['id_region_ejecucion'], $_POST['id_region_impacto'],
            $_POST['jefe_carrera'], $_POST['coordinador'], $_POST['rut_empresa'],
            $_POST['tipo_iniciativa'], $_POST['nombre_iniciativa'], $_POST['objetivo'],
            $_POST['descripcion'], $_POST['resultados'], $_POST['presupuesto']
        ]);
        
        // Obtenemos el ID de la postulación recién creada
        $id_postulacion = $pdo->lastInsertId();


        // Crear el Equipo de Trabajo
        $stmtEquipo = $pdo->prepare("INSERT INTO equipo_de_trabajo (id_postulacion) VALUES (?)");
        $stmtEquipo->execute([$id_postulacion]); // Creamos un nuevo equipo de trabajo asociado a esta postulación y obtenemos su ID para relacionar a los integrantes.
        $id_equipo = $pdo->lastInsertId();


        // Guardar Integrante del Equipo de Trabajo
        $stmtInt = $pdo->prepare("INSERT INTO integrante (id_equipo, rut_integrante, nombre, departamento_area, id_sede, email, telefono, id_tipo_integrante) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                                  ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
        $stmtInt->execute([ // Guardamos estos datos del integrante principal del equipo de trabajo
            $id_equipo, $_POST['rut_integrante'], $_POST['nom_integrante'], $_POST['depto_integrante'],
            $_POST['sede_integrante'], $_POST['email_integrante'], $_POST['tel_integrante'], $_POST['tipo_integrante']
        ]);

        // 4. Guardar Etapa del Cronograma (Ejemplo para 1 etapa)
        $stmtEtapa = $pdo->prepare("INSERT INTO etapa (id_postulacion, nombre_etapa, plazos_semanas, entregable) VALUES (?, ?, ?, ?)");
        $stmtEtapa->execute([
            $id_postulacion, $_POST['nom_etapa'], $_POST['plazo_etapa'], $_POST['entregable_etapa']
        ]);

        // Si todo salió bien, confirmamos los cambios en la base de datos
        $pdo->commit();
        $mensaje = "¡Postulación creada con éxito! N° de registro: " . $id_postulacion;
        $tipo_mensaje = "success";

    } catch (Exception $e) { 
        // Si algo falló, revertmos todos los cambios
        $pdo->rollBack(); //usamos un rollback como vimos en clases el otro dia
        $mensaje = "Error al guardar la postulación: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Postulación - CT-USM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard_responsable.php">← Volver al Dashboard</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <h2 class="mb-4">Formulario de Postulación de Iniciativas</h2>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?>"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <!-- SECCIÓN 1: DATOS DE LA INICIATIVA -->
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">1. Antecedentes de la Iniciativa</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label>Nombre de la Iniciativa</label>
                        <input type="text" name="nombre_iniciativa" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Tipo de Iniciativa</label>
                        <select name="tipo_iniciativa" class="form-select" required>
                            <option value="1">Nueva</option>
                            <option value="2">Existente</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Sede / Campus</label>
                        <select name="id_sede" class="form-select" required>
                            <option value="1">Campus Casa Central Valparaíso</option>
                            <option value="2">Campus San Joaquín</option>
                            <option value="3">Campus Vitacura</option>
                            <option value="4">Sede Viña del Mar</option>
                            <option value="5">Sede Concepción</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Presupuesto Total (CLP)</label>
                        <input type="number" name="presupuesto" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Jefe de Carrera</label>
                        <input type="text" name="jefe_carrera" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Coordinador de Proyectos</label>
                        <input type="text" name="coordinador" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Región de Ejecución (ID 1-16)</label>
                        <input type="number" name="id_region_ejecucion" class="form-control" min="1" max="16" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Región de Impacto (ID 1-16)</label>
                        <input type="number" name="id_region_impacto" class="form-control" min="1" max="16" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label>Objetivo</label>
                        <textarea name="objetivo" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label>Descripción de la Solución</label>
                        <textarea name="descripcion" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label>Resultados Esperados</label>
                        <textarea name="resultados" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: ENTIDAD EXTERNA -->
        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white">2. Entidad Externa (Empresa)</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>RUT Empresa</label>
                        <input type="text" name="rut_empresa" class="form-control" placeholder="Ej: 70.111.111-1" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Nombre Empresa</label>
                        <input type="text" name="nombre_empresa" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Tamaño</label>
                        <select name="id_tamano_empresa" class="form-select" required>
                            <option value="1">Microempresa</option>
                            <option value="2">Mediana</option>
                            <option value="3">Grande</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Representante</label>
                        <input type="text" name="rep_empresa" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Email Representante</label>
                        <input type="email" name="mail_empresa" class="form-control" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label>Teléfono</label>
                        <input type="text" name="tel_empresa" class="form-control" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label>Convenio</label>
                        <select name="convenio" class="form-select" required>
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 3 y 4: EQUIPO Y CRONOGRAMA -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-warning text-dark">3. Equipo de Trabajo (Principal)</div>
                    <div class="card-body">
                        <input type="text" name="rut_integrante" class="form-control mb-2" placeholder="RUT Integrante" required>
                        <input type="text" name="nom_integrante" class="form-control mb-2" placeholder="Nombre Completo" required>
                        <input type="text" name="depto_integrante" class="form-control mb-2" placeholder="Departamento/Área" required>
                        <input type="email" name="email_integrante" class="form-control mb-2" placeholder="Email" required>
                        <input type="text" name="tel_integrante" class="form-control mb-2" placeholder="Teléfono" required>
                        <select name="sede_integrante" class="form-select mb-2" required>
                            <option value="1">Sede Valparaíso</option>
                            <option value="2">Sede San Joaquín</option>
                        </select>
                        <select name="tipo_integrante" class="form-select" required>
                            <option value="1">Profesor</option>
                            <option value="2">Estudiante</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-info text-white">4. Cronograma (Etapa Inicial)</div>
                    <div class="card-body">
                        <input type="text" name="nom_etapa" class="form-control mb-2" placeholder="Nombre de la Etapa" required>
                        <input type="number" name="plazo_etapa" class="form-control mb-2" placeholder="Plazo en Semanas" required>
                        <input type="text" name="entregable_etapa" class="form-control mb-2" placeholder="Entregable" required>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100 shadow">Guardar Postulación Completa</button>
    </form>
</div>

</body>
</html>