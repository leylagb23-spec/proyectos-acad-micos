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
            $sql = "UPDATE estudiantes SET nombres = ?, apellidos = ?, documento = ?, correo = ?, telefono = ? WHERE id_estudiante = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombres, $apellidos, $documento, $correo, $telefono, $id]);
            $mensaje = "Estudiante actualizado correctamente.";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $mensaje = "Ya existe otro estudiante con ese documento.";
            } else {
                $mensaje = "Error: " . $e->getMessage();
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE id_estudiante = ? AND activo = 1");
$stmt->execute([$id]);
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    die("Estudiante no encontrado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar estudiante</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include "menu.php"; ?>

    <h1>Editar estudiante</h1>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje"><strong><?= htmlspecialchars($mensaje) ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <p>Nombres: <input type="text" name="nombres" required value="<?= htmlspecialchars($estudiante["nombres"]) ?>"></p>
        <p>Apellidos: <input type="text" name="apellidos" required value="<?= htmlspecialchars($estudiante["apellidos"]) ?>"></p>
        <p>Documento: <input type="text" name="documento" required value="<?= htmlspecialchars($estudiante["documento"]) ?>"></p>
        <p>Correo: <input type="email" name="correo" value="<?= htmlspecialchars($estudiante["correo"] ?? "") ?>"></p>
        <p>Teléfono: <input type="text" name="telefono" value="<?= htmlspecialchars($estudiante["telefono"] ?? "") ?>"></p>
        <button type="submit" name="actualizar">Actualizar</button>
    </form>

    <p><a href="estudiantes.php">Volver a la lista</a></p>
</body>
</html>
