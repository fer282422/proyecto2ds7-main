<?php
// Configuración de la base de datos
$servername = "localhost"; // Generalmente 'localhost'
$username = "root"; // ¡Cambia esto por tu usuario de la base de datos!
$password = ""; // ¡Cambia esto por tu contraseña de la base de datos!
$dbname = "articulos"; // ¡Cambia esto por el nombre de tu base de datos (ej. gaming_noticia_db)!

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si la solicitud es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger y limpiar los datos del formulario
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    // El número de tarjeta y CVV deben manejarse con mucho cuidado y idealmente no almacenarse directamente
    // Aquí se recogen para el procesamiento (ej. envío a un proveedor de pagos), no para almacenamiento seguro en esta forma.
    $numero_tarjeta = trim($_POST['numero_tarjeta'] ?? '');
    $vencimiento = trim($_POST['vencimiento'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    $metodo_pago = trim($_POST['metodo_pago'] ?? 'Tarjeta');
    $producto_comprado_nombre = trim($_POST['producto_comprado'] ?? '');
    $precio_producto = filter_var($_POST['precio'] ?? 0, FILTER_VALIDATE_FLOAT);

    // --- Validación de campos del lado del servidor ---
    // Esta validación complementa la del lado del cliente (HTML 'required' y 'pattern')
    $errors = [];
    if (empty($nombre_completo)) {
        $errors[] = "El nombre completo es requerido.";
    }
    // Validar formato de número de tarjeta (16 dígitos, opcionalmente con espacios)
    if (!preg_match('/^(\d{4}\s?){3}\d{4}$/', $numero_tarjeta)) {
        $errors[] = "El número de tarjeta no tiene el formato correcto (XXXX XXXX XXXX XXXX).";
    }
    // Validar formato de vencimiento (MM/AA)
    if (!preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2})$/', $vencimiento)) {
        $errors[] = "La fecha de vencimiento no tiene el formato correcto (MM/AA).";
    }
    // Validar formato de CVV (3 o 4 dígitos)
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        $errors[] = "El CVV no tiene el formato correcto (XXX o XXXX).";
    }
    if (empty($producto_comprado_nombre)) {
        $errors[] = "El nombre del producto es requerido.";
    }
    if ($precio_producto === false || $precio_producto <= 0) {
        $errors[] = "El precio del producto no es válido.";
    }

    if (!empty($errors)) {
        // Si hay errores de validación, se informa al usuario y se detiene la ejecución
        echo "<script>alert('Error en la validación de datos:\\n" . implode("\\n", $errors) . "'); window.history.back();</script>";
        exit;
    }

    // --- Paso 1: Insertar en la tabla 'transacciones' (detalles de pago) ---
    // Esta tabla registra la transacción financiera y los detalles del producto enviado por el formulario
    $stmt_transaccion = $conn->prepare("INSERT INTO transacciones (nombre_completo, numero_tarjeta, vencimiento, cvv, metodo_pago, monto_total) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt_transaccion === false) {
        die("Error al preparar la consulta de transacción: " . $conn->error);
    }
    $stmt_transaccion->bind_param("sssssd", $nombre_completo, $numero_tarjeta, $vencimiento, $cvv, $metodo_pago, $precio_producto);

    $transaccion_exitosa = false;
    $transaccion_id = null; // Para almacenar el ID de la transacción recién insertada
    if ($stmt_transaccion->execute()) {
        $transaccion_exitosa = true;
        $transaccion_id = $stmt_transaccion->insert_id; // Obtener el ID de la transacción insertada
    } else {
        echo "<script>alert('Error al guardar los detalles de la transacción: " . $stmt_transaccion->error . "'); window.location.href='index.html';</script>";
        $stmt_transaccion->close();
        $conn->close();
        exit;
    }
    $stmt_transaccion->close();

    // --- Paso 2: Preparar para insertar en 'articulos_comprados' ---
    // Necesitamos el articulo_id de la tabla 'articulos' y un usuario_id
    $articulo_id = null;
    $usuario_id = null; // Este debe ser el ID del usuario logueado.

    // 2.1: Buscar el 'articulo_id' basado en el 'nombre_producto_comprado'
    // ASUME que la tabla 'articulos' tiene una columna 'nombre'
    $stmt_get_articulo_id = $conn->prepare("SELECT id FROM articulos WHERE nombre = ? LIMIT 1");
    if ($stmt_get_articulo_id === false) {
        error_log("Error al preparar consulta para obtener articulo_id: " . $conn->error);
        // Podrías lanzar un error al usuario o usar un ID predeterminado
    } else {
        $stmt_get_articulo_id->bind_param("s", $producto_comprado_nombre);
        $stmt_get_articulo_id->execute();
        $stmt_get_articulo_id->bind_result($articulo_id);
        $stmt_get_articulo_id->fetch();
        $stmt_get_articulo_id->close();
    }


    // 2.2: Obtener el 'usuario_id'
    // En un sistema real, el usuario_id vendría de la sesión después de un login exitoso.
    // Ejemplo: session_start(); $usuario_id = $_SESSION['user_id'] ?? null;
    // Para este ejemplo, usaremos un ID de usuario fijo (debes tener al menos un usuario en tu tabla 'usuarios')
    // Asumiendo que el usuario con ID 1 existe en tu tabla 'usuarios'.
    $usuario_id = 1; // ¡Reemplaza esto con el ID real del usuario autenticado!

    // Verificar si tenemos un articulo_id y usuario_id válidos para insertar en 'articulos_comprados'
    if ($articulo_id !== null && $usuario_id !== null) {
        $stmt_articulo_comprado = $conn->prepare("INSERT INTO articulos_comprados (articulo_id, usuario_id) VALUES (?, ?)");
        if ($stmt_articulo_comprado === false) {
            error_log("Error al preparar consulta de articulos_comprados: " . $conn->error);
            echo "<script>alert('Error interno al preparar el registro del artículo comprado.');</script>";
        } else {
            $stmt_articulo_comprado->bind_param("ii", $articulo_id, $usuario_id);
            if ($stmt_articulo_comprado->execute()) {
                $articulo_comprado_id = $stmt_articulo_comprado->insert_id; // Obtener el ID del registro en articulos_comprados

                // Opcional: Actualizar la tabla 'transacciones' para enlazar con 'articulos_comprados'
                if ($transaccion_id !== null) {
                    $stmt_update_transaccion = $conn->prepare("UPDATE transacciones SET articulo_comprado_id = ? WHERE id = ?");
                    if ($stmt_update_transaccion === false) {
                        error_log("Error al preparar update de transacciones: " . $conn->error);
                    } else {
                        $stmt_update_transaccion->bind_param("ii", $articulo_comprado_id, $transaccion_id);
                        $stmt_update_transaccion->execute();
                        $stmt_update_transaccion->close();
                    }
                }
            } else {
                // Si falla la inserción en articulos_comprados, notificar pero no abortar la transacción de pago
                echo "<script>alert('Advertencia: La compra de pago se registró, pero hubo un error al registrar el artículo específico comprado: " . $stmt_articulo_comprado->error . "');</script>";
                error_log("Error al insertar en articulos_comprados: " . $stmt_articulo_comprado->error);
            }
            $stmt_articulo_comprado->close();
        }
    } else {
        // Esto ocurrirá si el articulo_id no se pudo encontrar o si usuario_id es null
        echo "<script>alert('Advertencia: La compra de pago se registró, pero no se pudo registrar el artículo comprado en detalle (articulo_id o usuario_id no válidos).');</script>";
        error_log("No se pudo registrar en articulos_comprados: Articulo_id o Usuario_id no válidos. Producto: " . $producto_comprado_nombre . ", Articulo_ID: " . ($articulo_id ?? 'N/A') . ", Usuario_ID: " . ($usuario_id ?? 'N/A'));
    }

    // Mensaje final de éxito
    echo "<script>alert('¡Compra realizada con éxito! Tu transacción ha sido procesada.'); window.location.href='index.html';</script>";

    // Cerrar la conexión a la base de datos
    $conn->close();
} else {
    // Si no es una solicitud POST, redirigir o mostrar un error
    echo "<script>alert('Acceso no autorizado.'); window.location.href='index.html';</script>";
}
?>