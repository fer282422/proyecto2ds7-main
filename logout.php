<?php
// logout.php

// Asegurarse de que las cookies existan antes de intentar borrarlas
if (isset($_COOKIE['logged_in'])) {
    setcookie("logged_in", "", time() - 3600, "/"); // Establece una fecha en el pasado para expirar la cookie
}
if (isset($_COOKIE['user_id'])) {
    setcookie("user_id", "", time() - 3600, "/"); // Establece una fecha en el pasado
}

// Redirigir al usuario a la página de inicio o login
header("Location: index.html"); // O a login.html
exit;
?>