<?php
require "conexion.php";

$mensaje = "";

// ---------- ÁREAS ACADÉMICAS ----------
if (isset($_POST["guardar_area"])) {
    $nombre = trim($_POST["nombre_area"]);
    if ($nombre === "") {
        $mensaje = "El nombre del área es obligatorio.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO areas_academicas (nombre) VALUES (?)");
            $stmt->execute([$nombre]);
            $mensaje = "Área registrada correctamente.";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $mensaje = "Ya existe un área con ese nombre.";
            } else {
                $mensaje = "Error: " . $e->getMessage();
            }
        }
    }
}

// ---------- PERÍODOS ACADÉMICOS ----------
if (isset($_POST["guardar_periodo"])) {
    $nombre  = trim($_POST["nombre_periodo"]);
    $inicio  = $_POST["fecha_inicio"];
    $fin     = $_POST["fecha_fin"];

    if ($nombre === "" || $inicio === "" || $fin === "") {
        $mensaje = "Todos los campos del período son obligatorios.";
    } elseif ($fin < $inicio) {
        $mensaje = "La fecha de fin no puede ser anterior a la de inicio.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO periodos_academicos (nombre, fecha_inicio, fecha_fin) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $inicio, $fin]);
            $mensaje = "Período registrado correctamente.";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $mensaje = "Ya existe un período con ese nombre.";
            } else {
                $mensaje = "Error: " . $e->getMessage();
            }
        }
    }
}

// ---------- DESACTIVAR ----------
if (isset($_POST["desactivar_periodo"])) {
    $stmt = $pdo->prepare("UPDATE periodos_academicos SET activo = 0 WHERE id_periodo = ?");
    $stmt->execute([$_POST["desactivar_periodo"]]);
    $mensaje = "Período desactivado.";
}

$areas = $pdo->query("SELECT * FROM areas_academicas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$periodos = $pdo->query("SELECT * FROM periodos_academicos WHERE activo = 1 ORDER BY fecha_inicio DESC")
                ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Áreas y períodos</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include "menu.php"; ?>

    <h1>Áreas académicas y períodos</h1>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje"><strong><?= htmlspecialchars($mensaje) ?></strong></p>
    <?php endif; ?>

    <h2>Registrar área académica</h2>
    <form method="post">
        <p>Nombre: <input type="text" name="nombre_area" required></p>
        <button type="submit" name="guardar_area">Guardar área</button>
    </form>

    <table border="1" cellpadding="5">
        <tr><th>ID</th><th>Nombre</th></tr>
        <?php foreach ($areas as $a): ?>
        <tr>
            <td><?= $a["id_area"] ?></td>
            <td><?= htmlspecialchars($a["nombre"]) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Registrar período académico</h2>
    <form method="post">
        <p>Nombre (ej: 2026-2): <input type="text" name="nombre_periodo" required></p>
        <p>Fecha de inicio: <input type="date" name="fecha_inicio" required></p>
        <p>Fecha de fin: <input type="date" name="fecha_fin" required></p>
        <button type="submit" name="guardar_periodo">Guardar período</button>
    </form>

    <table border="1" cellpadding="5">
        <tr><th>ID</th><th>Nombre</th><th>Inicio</th><th>Fin</th><th>Acciones</th></tr>
        <?php foreach ($periodos as $p): ?>
        <tr>
            <td><?= $p["id_periodo"] ?></td>
            <td><?= htmlspecialchars($p["nombre"]) ?></td>
            <td><?= $p["fecha_inicio"] ?></td>
            <td><?= $p["fecha_fin"] ?></td>
            <td>
                <form method="post" style="display:inline"
                      onsubmit="return confirm('¿Desactivar este período?');">
                    <input type="hidden" name="desactivar_periodo" value="<?= $p["id_periodo"] ?>">
                    <button type="submit">Desactivar</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
