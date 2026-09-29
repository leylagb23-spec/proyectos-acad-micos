<?php
require "conexion.php";

$id = (int)($_GET["id"] ?? 0);
$mensaje = "";

if (isset($_POST["actualizar"])) {
    $nombres   = trim($_POST["nombres"]);
    $apellidos = trim($_POST["apellidos"]);
    $documento = trim($_POST["documento"]);
    $correo    = trim($_POST["correo"]);
    $telefono  = trim($_POST["telefono"]);

    if ($nombres === "" || $apellidos === "" || $documento === "") {
        $mensaje = "Nombres, apellidos y documento son obligatorios.";
    } else {
        try {
            $sql = "UPDATE docentes SET nombres = ?, apellidos = ?, documento = ?, correo = ?, telefono = ? WHERE id_docente = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombres, $apellidos, $documento, $correo, $telefono, $id]);
            $mensaje = "Docente actualizado correctamente.";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $mensaje = "Ya existe otro docente con ese documento.";
            } else {
                $mensaje = "Error: " . $e->getMessage();
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM docentes WHERE id_docente = ? AND activo = 1");
$stmt->execute([$id]);
$docente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$docente) {
    die("Docente no encontrado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar docente</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include "menu.php"; ?>

    <h1>Editar docente</h1>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje"><strong><?= htmlspecialchars($mensaje) ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <p>Nombres: <input type="text" name="nombres" required value="<?= htmlspecialchars($docente["nombres"]) ?>"></p>
        <p>Apellidos: <input type="text" name="apellidos" required value="<?= htmlspecialchars($docente["apellidos"]) ?>"></p>
        <p>Documento: <input type="text" name="documento" required value="<?= htmlspecialchars($docente["documento"]) ?>"></p>
        <p>Correo: <input type="email" name="correo" value="<?= htmlspecialchars($docente["correo"] ?? "") ?>"></p>
        <p>Teléfono: <input type="text" name="telefono" value="<?= htmlspecialchars($docente["telefono"] ?? "") ?>"></p>
        <button type="submit" name="actualizar">Actualizar</button>
    </form>

    <p><a href="docentes.php">Volver a la lista</a></p>
</body>
</html>
