<?php
// Configuración inicial de PHP para muestra de errores
error_reporting(E_ALL);        
ini_set('display_errors', 1); 

session_start(); //Nos servira para guardar datos del usuario que ha iniciado sesión.

require 'conexion.php'; // Incluimos el archivo de conexión a la base de datos.

$error_msg = ""; // Creamos una variable vacía para guardar mensajes de error (ej: "contraseña incorrecta").

// Inicio de Sesion y Validación de Credenciales
// Verificamos si la página fue cargada porque el usuario apretó el botón "Ingresar"
if ($_SERVER["REQUEST_METHOD"] == "POST") { // Si el método de la solicitud es POST, significa que el formulario fue enviado.
    
    // Obtenemos los datos que el usuario escribió.
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Consultamos estos datos en la base de datos para verificar la información.
    $stmt = $pdo->prepare("SELECT id_usuario, username, password, id_rol FROM usuarios WHERE username = :username");
    $stmt->execute(['username' => $username]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // Si el usuario existe, $user será un array con sus datos. Si no, será false.

    // Verificamos las credenciales del usuario.
    // Comprobamos que $user exista y que la contraseña ingresada coincida con la de la base de datos.
    if ($user && $password === $user['password']) {
        
        // Credenciales correctas, guardamos los datos del usuario en la Sesión.
        $_SESSION['id_usuario'] = $user['id_usuario'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['id_rol']     = $user['id_rol'];

        // Redirigimos al usuario a su dashboard correspondiente según su rol.
        if ($user['id_rol'] == 1) {
            header("Location: dashboard_responsable.php"); // Rol 1: Responsable
        } elseif ($user['id_rol'] == 2) {
            header("Location: dashboard_evaluador.php");   // Rol 2: Evaluador
        } elseif ($user['id_rol'] == 3) {
            header("Location: dashboard_admin.php");       // Rol 3: Administrador
        }
        
        exit(); // Detenemos la ejecución del script aquí para que la redirección funcione correctamente.
        
    } else {
        // Si el usuario no existe o la clave es incorrecta, guardamos este mensaje.
        $error_msg = "Usuario o contraseña incorrectos.";
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - CT-USM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light"> <div class="container mt-5"> <div class="row justify-content-center">
            <div class="col-md-4"> <div class="card shadow">
                    <div class="card-header text-center bg-primary text-white">
                        <h4>Ingreso CT-USM</h4>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($error_msg): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Usuario</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>