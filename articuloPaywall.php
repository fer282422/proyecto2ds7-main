<?php
// articulo.php

// Configuración de la base de datos
$servername = "localhost";
$username = "root"; // ¡Cambia esto por tu usuario de base de datos!
$password = ""; // ¡Cambia esto por tu contraseña de base de datos!
$dbname = "articulos"; // ¡Cambia esto por el nombre de tu base de datos!

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// ID del artículo que se está viendo en esta página
// Puedes hacer que este ID sea dinámico (ej. de la URL ?id=X)
// Por ahora, lo fijamos al ID del artículo que insertamos de prueba
// Para hacerlo dinámico desde la URL, usarías algo como:
// $current_article_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$current_article_id = 1; // Asumiendo que 'Entrevista exclusiva con Yuji Naka' tiene ID 1

$is_logged_in = false;
$has_purchased = false;
$user_id = null;
$article_title = "Cargando..."; // Para el título del artículo
$article_description = "Cargando..."; // Para la descripción del artículo
$article_image = ""; // Para la imagen del artículo
$article_content = ""; // Variable para almacenar el contenido a mostrar

// 1. Verificar si el usuario está logueado (usando cookies o sesiones)
// Es más seguro usar sesiones para manejar el estado de login. Si aún no las usas:
session_start(); // Inicia la sesión al principio de tu script

if (isset($_COOKIE['logged_in']) && isset($_COOKIE['user_id'])) {
    $is_logged_in = true;
    $user_id = (int)$_COOKIE['user_id'];
}
// Si estás usando cookies como en tu ejemplo original y no sesiones:
// if (isset($_COOKIE['logged_in']) && $_COOKIE['logged_in'] === 'true' && isset($_COOKIE['user_id'])) {
//     $is_logged_in = true;
//     $user_id = (int)$_COOKIE['user_id']; // Convertir a entero para seguridad
// }

// 2. Si el usuario está logueado, verificar si ha comprado el artículo
if ($is_logged_in) {
    $stmt_check_purchase = $conn->prepare("SELECT COUNT(*) FROM articulos_comprados WHERE usuario_id = ? AND articulo_id = ?");
    $stmt_check_purchase->bind_param("ii", $user_id, $current_article_id);
    $stmt_check_purchase->execute();
    $stmt_check_purchase->bind_result($purchase_count);
    $stmt_check_purchase->fetch();
    $stmt_check_purchase->close();

    if ($purchase_count > 0) {
        $has_purchased = true;
    }
}

// 3. Obtener los detalles del artículo de la base de datos
$stmt_get_article = $conn->prepare("SELECT nombre, precio, contenido FROM articulos WHERE id = ?");
$stmt_get_article->bind_param("i", $current_article_id);
$stmt_get_article->execute();
$stmt_get_article->bind_result($db_nombre, $db_precio, $db_contenido);
$stmt_get_article->fetch();
$stmt_get_article->close();

$article_title = $db_nombre;
$article_description = 'Precio: $' . number_format($db_precio, 2);
$article_image = ''; // No hay campo imagen en la tabla

// Decidir qué contenido mostrar
if ($has_purchased) { // Mostrar contenido si ha comprado
    $article_content = $db_contenido;
} else {
    $article_content = 'Este artículo es premium. Debes comprarlo para ver el contenido.';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=yes" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title><?php echo htmlspecialchars($article_title); ?> - Gaming Noticia</title>
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css?family=Merriweather+Sans:400,700" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css?family=Merriweather:400,300,300italic,400italic,700,700italic" rel="stylesheet" type="text/css" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/SimpleLightbox/2.1.0/simpleLightbox.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
    </head>
    <body id="page-top">
        <nav class="navbar navbar-expand-lg navbar-light fixed-top py-3" id="mainNav">
        <div class="container px-4 px-lg-5">
            <a class="navbar-brand" href="index.html#page-top">
                <img src="assets/favicon.ico" style="height: 30px; margin-right: 10px;" />
                Gaming Noticia
            </a>
            <button class="navbar-toggler navbar-toggler-right" type="button" data-bs-toggle="collapse" 
                data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" 
                aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto my-2 my-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.html#noticiasrelevantes">Noticias</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.html#portfolio">Reviews</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.html#juego">Juegos</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.html">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

        <header class="masthead" style="background: linear-gradient(to bottom, rgba(92, 77, 66, 0.8) 0%, rgba(92, 77, 66, 0.8) 100%), url(<?php echo htmlspecialchars($article_image); ?>); 
        background-repeat: no-repeat; background-size: cover;">               
            <div class="row gx-4 gx-lg-5 h-100 align-items-center text-center">
                <div class="col-lg-6">
                    <h1 class="text-white font-weight-bold"><?php echo htmlspecialchars($article_title); ?></h1>
                    <hr class="divider" />
                    <p class="text-white-75 mb-5"><?php echo htmlspecialchars($article_description); ?></p>
                </div>
                <div class="col-lg-6" style="padding-left: 5%;">
                    <img src="<?php echo htmlspecialchars($article_image); ?>" width="100%" class="img-fluid" alt="Imagen del artículo">
                </div>
            </div>
        </header>

        <section class="page-section" id="article-content">
            <div class="container px-4 px-lg-5">
                <?php if ($has_purchased || $is_logged_in === false): // Mostrar contenido premium si se ha comprado, o el contenido gratuito si no está logueado ?>
                    <div class="row gx-4 gx-lg-5 justify-content-center">
                        <div class="col-lg-8 text-center">
                            <p class="text-dark-75 mb-4 text-start">
                                <?php echo nl2br(htmlspecialchars($article_content)); ?>
                            </p>
                        </div>
                    </div>
                <?php else: // Si no ha comprado y está logueado (para comprar el premium) ?>
                    <h2 class="text-center mt-0">
                        Este artículo es contenido premium. Para acceder a él, puedes:
                        <br><br>
                        1. **Adquirir una suscripción:** Esto te dará acceso ilimitado a todo nuestro contenido premium.
                        <br>
                        2. **Comprar este artículo:** Paga $2 una sola vez y accede a este artículo cuando quieras.
                        <br><br>
                        <a class="btn btn-primary btn-xl" href="compra_suscripcion.html">Adquirir Suscripción</a>
                        <a class="btn btn-info btn-xl" href="comprar_articulo.php?id=<?php echo $current_article_id; ?>">Comprar este Artículo ($2)</a>
                    </h2>
                    <hr class="divider" />
                <?php endif; ?>

                <?php if (!$is_logged_in): // Mostrar el mensaje para iniciar sesión solo si no está logueado ?>
                    <h2 class="text-center mt-0">
                        Entra ahora para acceder al artículo o comprarlo.
                        <br><br>
                        <a class="btn btn-primary btn-xl" href="login.html">Entrar</a>
                    </h2>
                    <hr class="divider" />
                <?php endif; ?>
            </div>
        </section>

        <footer class="bg-light py-5">
            <div class="container px-4 px-lg-5"><div class="small text-center text-muted">Copyright &copy; 2023 - Gaming Noticia</div></div>
        </footer>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/SimpleLightbox/2.1.0/simpleLightbox.min.js"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdn.startbootstrap.com/sb-forms-latest.js"></script>
    </body>
</html>