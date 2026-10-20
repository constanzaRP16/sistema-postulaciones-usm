<?php
session_start(); //Esto recuerda quien inicio sesion.
session_destroy(); // Destruye la sesión actual, cerrando la sesión del usuario.
header("Location: index.php"); // Redirige al usuario de vuelta al index.php después de cerrar sesión.
exit();
?>