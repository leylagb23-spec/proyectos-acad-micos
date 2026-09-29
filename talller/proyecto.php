<?php
require "conexion.php";

$estados = ["Propuesta", "En revisión", "Aprobado", "En desarrollo", "Suspendido", "Finalizado"];

$id = (int)($_GET["id"] ?? 0);
$mensaje = "";

function cargarProyecto($pdo, $id) {
    $sql = "SELECT p.*, a.nombre AS area, per.nombre AS periodo,
                   d.id_docente, d.nombres AS doc_nombres, d.apellidos AS doc_apellidos
            FROM proyectos p
            JOIN areas_academicas a ON a.id_area = p.id_area
            JOIN periodos_academicos per ON per.id_periodo = p.id_periodo
            JOIN docentes d ON d.id_docente = p.id_docente
            WHERE p.id_proyecto = ? AND p.activo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$proyecto = cargarProyecto($pdo, $id);
if (!$proyecto) {
    die("Proyecto no encontrado.");
}
$finalizado = ($proyecto["estado"] === "Finalizado");

// ---------- ACCIONES ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($finalizado) {
        $mensaje = "El proyecto ya está finalizado y no se puede modificar.";

    } elseif (isset($_POST["cambiar_estado"])) {
        $nuevo = $_POST["estado"];

        if (!in_array($nuevo, $estados)) {
            $mensaje = "Estado no válido.";
        } elseif ($nuevo === "Finalizado") {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM avances WHERE id_proyecto = ?");
            $stmt->execute([$id]);
            $tieneAvances = $stmt->fetchColumn() > 0;

            if (!$tieneAvances) {
                $mensaje = "No se puede finalizar: el proyecto debe tener al menos un avance registrado.";
            } else {
                $stmt = $pdo->prepare("UPDATE proyectos SET estado = ? WHERE id_proyecto = ?");
                $stmt->execute([$nuevo, $id]);
                $mensaje = "Estado actualizado a Finalizado.";
            }
        } else {
            $stmt = $pdo->prepare("UPDATE proyectos SET estado = ? WHERE id_proyecto = ?");
            $stmt->execute([$nuevo, $id]);
            $mensaje = "Estado actualizado.";
        }

    } elseif (isset($_POST["agregar_estudiante"])) {
        $idEst = (int)$_POST["id_estudiante"];
        $rol   = trim($_POST["rol"]);

        if ($idEst === 0 || $rol === "") {
            $mensaje = "Seleccione un estudiante y un rol.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO proyecto_estudiantes (id_proyecto, id_estudiante, rol) VALUES (?, ?, ?)");
                $stmt->execute([$id, $idEst, $rol]);
                $mensaje = "Estudiante agregado al proyecto.";
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) {
                    $mensaje = "Ese estudiante ya tiene ese rol en este proyecto.";
                } else {
                    $mensaje = "Error: " . $e->getMessage();
                }
            }
        }

    } elseif (isset($_POST["quitar_estudiante"])) {
        // No se permite dejar el proyecto sin ningún estudiante
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM proyecto_estudiantes WHERE id_proyecto = ?");
        $stmt->execute([$id]);
        $total = $stmt->fetchColumn();

        if ($total <= 1) {
            $mensaje = "El proyecto debe conservar al menos un estudiante responsable.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM proyecto_estudiantes WHERE id_proyecto_estudiante = ? AND id_proyecto = ?");
            $stmt->execute([$_POST["quitar_estudiante"], $id]);
            $mensaje = "Estudiante retirado del proyecto.";
        }

    } elseif (isset($_POST["agregar_avance"])) {
        $fecha = $_POST["fecha_avance"];
        $desc  = trim($_POST["descripcion_avance"]);

        if ($fecha === "" || $desc === "") {
            $mensaje = "La fecha y la descripción del avance son obligatorias.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO avances (id_proyecto, fecha_avance, descripcion) VALUES (?, ?, ?)");
            $stmt->execute([$id, $fecha, $desc]);
            $mensaje = "Avance registrado.";
        }

    } elseif (isset($_POST["agregar_observacion"])) {
        $idAvance = !empty($_POST["id_avance"]) ? (int)$_POST["id_avance"] : null;
        $desc     = trim($_POST["descripcion_obs"]);

        if ($desc === "") {
            $mensaje = "La observación no puede estar vacía.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO observaciones (id_proyecto, id_avance, id_docente, descripcion)
                                   VALUES (?, ?, ?, ?)");
            $stmt->execute([$id, $idAvance, $proyecto["id_docente"], $desc]);
            $mensaje = "Observación registrada.";
        }
    }

    $proyecto = cargarProyecto($pdo, $id);
    $finalizado = ($proyecto["estado"] === "Finalizado");
}

// ---------- DATOS PARA MOSTRAR ----------
$stmt = $pdo->prepare("SELECT pe.*, e.nombres, e.apellidos
                       FROM proyecto_estudiantes pe
                       JOIN estudiantes e ON e.id_estudiante = pe.id_estudiante
                       WHERE pe.id_proyecto = ?
                       ORDER BY e.apellidos, e.nombres");
$stmt->execute([$id]);
$participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM avances WHERE id_proyecto = ? ORDER BY fecha_avance");
$stmt->execute([$id]);
$avances = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT o.*, av.fecha_avance
                       FROM observaciones o
                       LEFT JOIN avances av ON av.id_avance = o.id_avance
                       WHERE o.id_proyecto = ?
                       ORDER BY o.fecha_registro");
$stmt->execute([$id]);
$observaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$listaEstudiantes = $pdo->query("SELECT * FROM estudiantes WHERE activo = 1 ORDER BY apellidos, nombres")
                        ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proyecto <?= $proyecto["id_proyecto"] ?></title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include "menu.php"; ?>

    <h1><?= htmlspecialchars($proyecto["nombre"]) ?></h1>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje"><strong><?= htmlspecialchars($mensaje) ?></strong></p>
    <?php endif; ?>

    <h2>Información general</h2>
    <p>Área: <?= htmlspecialchars($proyecto["area"]) ?> — Período: <?= htmlspecialchars($proyecto["periodo"]) ?></p>
    <p>Docente asesor: <?= htmlspecialchars($proyecto["doc_nombres"] . " " . $proyecto["doc_apellidos"]) ?></p>
    <p>Fechas: <?= $proyecto["fecha_inicio"] ?> a <?= $proyecto["fecha_fin_prevista"] ?></p>
    <p>Descripción: <?= nl2br(htmlspecialchars($proyecto["descripcion"])) ?></p>
    <p>Problema o necesidad: <?= nl2br(htmlspecialchars($proyecto["problema_necesidad"])) ?></p>
    <p>Objetivos: <?= nl2br(htmlspecialchars($proyecto["objetivos"])) ?></p>
    <p>Estado actual: <strong><?= $proyecto["estado"] ?></strong></p>

    <?php if ($finalizado): ?>
        <p><em>Este proyecto ya está finalizado: solo se puede consultar.</em></p>
    <?php else: ?>
        <h2>Cambiar estado</h2>
        <form method="post">
            <select name="estado">
                <?php foreach ($estados as $est): ?>
                    <option value="<?= $est ?>" <?= $proyecto["estado"] === $est ? "selected" : "" ?>><?= $est ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="cambiar_estado">Cambiar estado</button>
        </form>
        <p><em>Para pasar a Finalizado debe existir al menos un avance registrado.</em></p>
    <?php endif; ?>

    <h2>Estudiantes y roles</h2>
    <table border="1" cellpadding="5">
        <tr><th>Estudiante</th><th>Rol</th><?php if (!$finalizado): ?><th></th><?php endif; ?></tr>
        <?php foreach ($participantes as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p["nombres"] . " " . $p["apellidos"]) ?></td>
            <td><?= htmlspecialchars($p["rol"]) ?></td>
            <?php if (!$finalizado): ?>
            <td>
                <form method="post" style="display:inline" onsubmit="return confirm('¿Quitar este estudiante del proyecto?');">
                    <input type="hidden" name="quitar_estudiante" value="<?= $p["id_proyecto_estudiante"] ?>">
                    <button type="submit">Quitar</button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if (!$finalizado): ?>
    <form method="post">
        Estudiante:
        <select name="id_estudiante">
            <option value="">-- Seleccione --</option>
            <?php foreach ($listaEstudiantes as $e): ?>
                <option value="<?= $e["id_estudiante"] ?>"><?= htmlspecialchars($e["nombres"] . " " . $e["apellidos"]) ?></option>
            <?php endforeach; ?>
        </select>
        Rol:
        <select name="rol">
            <option value="">-- Seleccione --</option>
            <option value="Líder">Líder</option>
            <option value="Investigador">Investigador</option>
            <option value="Diseñador">Diseñador</option>
            <option value="Programador">Programador</option>
            <option value="Documentador">Documentador</option>
        </select>
        <button type="submit" name="agregar_estudiante">Agregar estudiante</button>
    </form>
    <?php endif; ?>

    <h2>Avances</h2>
    <table border="1" cellpadding="5">
        <tr><th>Fecha</th><th>Descripción</th></tr>
        <?php foreach ($avances as $a): ?>
        <tr>
            <td><?= $a["fecha_avance"] ?></td>
            <td><?= nl2br(htmlspecialchars($a["descripcion"])) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if (!$finalizado): ?>
    <form method="post">
        <p>Fecha: <input type="date" name="fecha_avance" required></p>
        <p>Descripción:<br><textarea name="descripcion_avance" rows="3" cols="50" required></textarea></p>
        <button type="submit" name="agregar_avance">Registrar avance</button>
    </form>
    <?php endif; ?>

    <h2>Observaciones del docente</h2>
    <table border="1" cellpadding="5">
        <tr><th>Fecha</th><th>Avance relacionado</th><th>Observación</th></tr>
        <?php foreach ($observaciones as $o): ?>
        <tr>
            <td><?= $o["fecha_registro"] ?></td>
            <td><?= $o["fecha_avance"] ?? "General" ?></td>
            <td><?= nl2br(htmlspecialchars($o["descripcion"])) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if (!$finalizado): ?>
    <form method="post">
        <p>Avance relacionado (opcional):
            <select name="id_avance">
                <option value="">-- Observación general --</option>
                <?php foreach ($avances as $a): ?>
                    <option value="<?= $a["id_avance"] ?>"><?= $a["fecha_avance"] ?> — <?= htmlspecialchars(mb_substr($a["descripcion"], 0, 40)) ?>...</option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>Observación:<br><textarea name="descripcion_obs" rows="3" cols="50" required></textarea></p>
        <button type="submit" name="agregar_observacion">Registrar observación</button>
    </form>
    <?php endif; ?>

    <p><a href="proyectos.php">Volver a proyectos</a></p>
</body>
</html>
