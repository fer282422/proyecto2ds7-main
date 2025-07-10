<?php 
ob_start(); // Previene problemas de salida antes de setcookie

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "articulos";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === 'POST') {
    $user_input_username = trim($_POST['email'] ?? '');
    $user_input_password = trim($_POST['password'] ?? '');

    if (empty($user_input_username) || empty($user_input_password)) {
        header("Location: login.html?error=empty");
        exit;
    }

    $stmt = $conn->prepare("SELECT id, nombre_usuario, password_hash FROM usuarios WHERE nombre_usuario = ?");
    $stmt->bind_param("s", $user_input_username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $db_username, $db_password_hash);
        $stmt->fetch();

        if (password_verify($user_input_password, $db_password_hash)) {
            setcookie("logged_in", "true", time() + (86400 * 30), "/");
            setcookie("user_id", $user_id, time() + (86400 * 30), "/");

            header("Location: articuloPaywall.php");
            exit;
        } else {
            header("Location: login.html?error=login");
            exit;
        }
    } else {
        header("Location: login.html?error=login");
        exit;
    }

    $stmt->close();
} else {
    header("Location: login.html");
    exit;
}
$conn->close();
ob_end_flush(); 
?>
