<?php
require "conexion.php";

$mensaje = "";

if (isset($_POST["guardar"])) {
    $nombres   = trim($_POST["nombres"]);
    $apellidos = trim($_POST["apellidos"]);
    $documento = trim($_POST["documento"]);
    $correo    = trim($_POST["correo"]);
    $telefono  = trim($_POST["telefono"]);

    if ($nombres === "" || $apellidos === "" || $documento === "") {
        $mensaje = "Nombres, apellidos y documento son obligatorios.";
    } else {
        try {
            $sql = "INSERT INTO estudiantes (nombres, apellidos, documento, correo, telefono) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombres, $apellidos, $documento, $correo, $telefono]);
            $mensaje = "Estudiante registrado correctamente.";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $mensaje = "Ya existe un estudiante con ese documento.";
            } else {
                $mensaje = "Error: " . $e->getMessage();
            }
        }
    }
}

if (isset($_POST["desactivar"])) {
    $stmt = $pdo->prepare("UPDATE estudiantes SET activo = 0 WHERE id_estudiante = ?");
    $stmt->execute([$_POST["desactivar"]]);
    $mensaje = "Estudiante desactivado.";
}

$estudiantes = $pdo->query("SELECT * FROM estudiantes WHERE activo = 1 ORDER BY apellidos, nombres")
               ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estudiantes</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include "menu.php"; ?>

    <h1>Registrar estudiante</h1>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje"><strong><?= htmlspecialchars($mensaje) ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <p>Nombres: <input type="text" name="nombres" required></p>
        <p>Apellidos: <input type="text" name="apellidos" required></p>
        <p>Documento: <input type="text" name="documento" required></p>
        <p>Correo: <input type="email" name="correo"></p>
        <p>Teléfono: <input type="text" name="telefono"></p>
        <button type="submit" name="guardar">Guardar</button>
    </form>

    <h2>Estudiantes registrados</h2>
    <table border="1" cellpadding="5">
        <tr><th>ID</th><th>Nombre</th><th>Documento</th><th>Correo</th><th>Teléfono</th><th>Acciones</th></tr>
        <?php foreach ($estudiantes as $d): ?>
        <tr>
            <td><?= $d["id_estudiante"] ?></td>
            <td><?= htmlspecialchars($d["nombres"] . " " . $d["apellidos"]) ?></td>
            <td><?= htmlspecialchars($d["documento"]) ?></td>
            <td><?= htmlspecialchars($d["correo"] ?? "") ?></td>
            <td><?= htmlspecialchars($d["telefono"] ?? "") ?></td>
            <td>
                <a href="editar_estudiante.php?id=<?= $d["id_estudiante"] ?>">Editar</a>
                <form method="post" style="display:inline" onsubmit="return confirm('¿Desactivar este estudiante?');">
                    <input type="hidden" name="desactivar" value="<?= $d["id_estudiante"] ?>">
                    <button type="submit">Desactivar</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
