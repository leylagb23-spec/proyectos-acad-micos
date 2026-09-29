<?php
require "conexion.php";

$estados = ["Propuesta", "En revisión", "Aprobado", "En desarrollo", "Suspendido", "Finalizado"];

$mensaje = "";

// ---------- CREAR PROYECTO ----------
if (isset($_POST["guardar"])) {
    $nombre      = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $problema    = trim($_POST["problema_necesidad"]);
    $objetivos   = trim($_POST["objetivos"]);
    $id_area     = (int)($_POST["id_area"] ?? 0);
    $id_periodo  = (int)($_POST["id_periodo"] ?? 0);
    $id_docente  = (int)($_POST["id_docente"] ?? 0);
    $inicio      = $_POST["fecha_inicio"];
    $fin         = $_POST["fecha_fin_prevista"];
    $estudiantes = $_POST["estudiantes"] ?? [];  // array de id_estudiante
    $roles       = $_POST["roles"] ?? [];        // array de rol, mismo índice que estudiantes

    if ($nombre === "" || $descripcion === "" || $problema === "" || $objetivos === "" ||
        $id_area === 0 || $id_periodo === 0 || $id_docente === 0 || $inicio === "" || $fin === "") {
        $mensaje = "Todos los campos del proyecto son obligatorios.";
    } elseif ($fin < $inicio) {
        $mensaje = "La fecha de fin no puede ser anterior a la de inicio.";
    } elseif (empty($estudiantes)) {
        $mensaje = "El proyecto debe tener al menos un estudiante responsable.";
    } else {
        // Armar pares estudiante-rol válidos (ambos campos llenos)
        $pares = [];
        foreach ($estudiantes as $i => $idEst) {
            $idEst = (int)$idEst;
            $rol = trim($roles[$i] ?? "");
            if ($idEst > 0 && $rol !== "") {
                $pares[] = [$idEst, $rol];
            }
        }

        if (empty($pares)) {
            $mensaje = "Debe indicar al menos un estudiante con su rol.";
        } else {
            try {
                $pdo->beginTransaction();

                $sql = "INSERT INTO proyectos
                        (nombre, descripcion, problema_necesidad, objetivos, id_area, id_periodo,
                         id_docente, fecha_inicio, fecha_fin_prevista)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre, $descripcion, $problema, $objetivos, $id_area, $id_periodo,
                                 $id_docente, $inicio, $fin]);
                $idProyecto = $pdo->lastInsertId();

                $stmtPe = $pdo->prepare("INSERT INTO proyecto_estudiantes (id_proyecto, id_estudiante, rol)
                                         VALUES (?, ?, ?)");
                foreach ($pares as [$idEst, $rol]) {
                    $stmtPe->execute([$idProyecto, $idEst, $rol]);
                }

                $pdo->commit();
                header("Location: proyecto.php?id=" . $idProyecto);
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                if ($e->errorInfo[1] == 1062) {
                    $mensaje = "Ya existe un proyecto con ese nombre en el período seleccionado, o el estudiante tiene ese rol repetido.";
                } else {
                    $mensaje = "Error: " . $e->getMessage();
                }
            }
        }
    }
}

// ---------- DATOS PARA EL FORMULARIO ----------
$areas = $pdo->query("SELECT * FROM areas_academicas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$periodos = $pdo->query("SELECT * FROM periodos_academicos WHERE activo = 1 ORDER BY fecha_inicio DESC")
                ->fetchAll(PDO::FETCH_ASSOC);
$docentes = $pdo->query("SELECT * FROM docentes WHERE activo = 1 ORDER BY apellidos, nombres")
                ->fetchAll(PDO::FETCH_ASSOC);
$listaEstudiantes = $pdo->query("SELECT * FROM estudiantes WHERE activo = 1 ORDER BY apellidos, nombres")
                        ->fetchAll(PDO::FETCH_ASSOC);

// ---------- FILTROS DE CONSULTA ----------
$where  = ["p.activo = 1"];
$params = [];

if (!empty($_GET["area"])) {
    $where[] = "p.id_area = ?";
    $params[] = (int)$_GET["area"];
}
if (!empty($_GET["docente"])) {
    $where[] = "p.id_docente = ?";
    $params[] = (int)$_GET["docente"];
}
if (!empty($_GET["estudiante"])) {
    $where[] = "p.id_proyecto IN (SELECT id_proyecto FROM proyecto_estudiantes WHERE id_estudiante = ?)";
    $params[] = (int)$_GET["estudiante"];
}
if (!empty($_GET["estado"]) && in_array($_GET["estado"], $estados)) {
    $where[] = "p.estado = ?";
    $params[] = $_GET["estado"];
}
if (($_GET["grupo"] ?? "") === "activos") {
    $where[] = "p.estado != 'Finalizado'";
} elseif (($_GET["grupo"] ?? "") === "finalizados") {
    $where[] = "p.estado = 'Finalizado'";
}

$sql = "SELECT p.*, a.nombre AS area, per.nombre AS periodo,
               d.nombres AS doc_nombres, d.apellidos AS doc_apellidos
        FROM proyectos p
        JOIN areas_academicas a ON a.id_area = p.id_area
        JOIN periodos_academicos per ON per.id_periodo = p.id_periodo
        JOIN docentes d ON d.id_docente = p.id_docente
        WHERE " . implode(" AND ", $where) . "
        ORDER BY p.fecha_registro DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proyectos académicos</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include "menu.php"; ?>

    <h1>Registrar proyecto académico</h1>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje"><strong><?= htmlspecialchars($mensaje) ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <p>Nombre del proyecto: <input type="text" name="nombre" required style="width:400px"></p>
        <p>Descripción:<br><textarea name="descripcion" rows="3" cols="60" required></textarea></p>
        <p>Problema o necesidad:<br><textarea name="problema_necesidad" rows="3" cols="60" required></textarea></p>
        <p>Objetivos:<br><textarea name="objetivos" rows="3" cols="60" required></textarea></p>

        <p>Área académica:
            <select name="id_area" required>
                <option value="">-- Seleccione --</option>
                <?php foreach ($areas as $a): ?>
                    <option value="<?= $a["id_area"] ?>"><?= htmlspecialchars($a["nombre"]) ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>Período académico:
            <select name="id_periodo" required>
                <option value="">-- Seleccione --</option>
                <?php foreach ($periodos as $p): ?>
                    <option value="<?= $p["id_periodo"] ?>"><?= htmlspecialchars($p["nombre"]) ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>Docente asesor:
            <select name="id_docente" required>
                <option value="">-- Seleccione --</option>
                <?php foreach ($docentes as $d): ?>
                    <option value="<?= $d["id_docente"] ?>">
                        <?= htmlspecialchars($d["nombres"] . " " . $d["apellidos"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>Fecha de inicio: <input type="date" name="fecha_inicio" required></p>
        <p>Fecha de finalización prevista: <input type="date" name="fecha_fin_prevista" required></p>

        <h3>Estudiantes responsables y su rol</h3>
        <p><em>Complete al menos una fila con estudiante y rol. Deje en blanco las filas que no use.</em></p>
        <?php for ($i = 0; $i < 4; $i++): ?>
            <p>
                Estudiante:
                <select name="estudiantes[]">
                    <option value="">-- Ninguno --</option>
                    <?php foreach ($listaEstudiantes as $e): ?>
                        <option value="<?= $e["id_estudiante"] ?>">
                            <?= htmlspecialchars($e["nombres"] . " " . $e["apellidos"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                Rol:
                <select name="roles[]">
                    <option value="">-- Ninguno --</option>
                    <option value="Líder">Líder</option>
                    <option value="Investigador">Investigador</option>
                    <option value="Diseñador">Diseñador</option>
                    <option value="Programador">Programador</option>
                    <option value="Documentador">Documentador</option>
                </select>
            </p>
        <?php endfor; ?>

        <button type="submit" name="guardar">Crear proyecto</button>
    </form>

    <h2>Consultar proyectos</h2>
    <form method="get">
        Área:
        <select name="area">
            <option value="">Todas</option>
            <?php foreach ($areas as $a): ?>
                <option value="<?= $a["id_area"] ?>" <?= ($_GET["area"] ?? "") == $a["id_area"] ? "selected" : "" ?>>
                    <?= htmlspecialchars($a["nombre"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        Docente:
        <select name="docente">
            <option value="">Todos</option>
            <?php foreach ($docentes as $d): ?>
                <option value="<?= $d["id_docente"] ?>" <?= ($_GET["docente"] ?? "") == $d["id_docente"] ? "selected" : "" ?>>
                    <?= htmlspecialchars($d["nombres"] . " " . $d["apellidos"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        Estudiante:
        <select name="estudiante">
            <option value="">Todos</option>
            <?php foreach ($listaEstudiantes as $e): ?>
                <option value="<?= $e["id_estudiante"] ?>" <?= ($_GET["estudiante"] ?? "") == $e["id_estudiante"] ? "selected" : "" ?>>
                    <?= htmlspecialchars($e["nombres"] . " " . $e["apellidos"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        Estado:
        <select name="estado">
            <option value="">Todos</option>
            <?php foreach ($estados as $est): ?>
                <option value="<?= $est ?>" <?= ($_GET["estado"] ?? "") === $est ? "selected" : "" ?>><?= $est ?></option>
            <?php endforeach; ?>
        </select>

        Grupo:
        <select name="grupo">
            <option value="">Todos</option>
            <option value="activos" <?= ($_GET["grupo"] ?? "") === "activos" ? "selected" : "" ?>>Activos</option>
            <option value="finalizados" <?= ($_GET["grupo"] ?? "") === "finalizados" ? "selected" : "" ?>>Finalizados</option>
        </select>

        <button type="submit">Filtrar</button>
        <a href="proyectos.php">Limpiar</a>
    </form>

    <table border="1" cellpadding="5">
        <tr>
            <th>N.º</th><th>Nombre</th><th>Área</th><th>Período</th>
            <th>Docente</th><th>Estado</th><th></th>
        </tr>
        <?php foreach ($proyectos as $p): ?>
        <tr>
            <td><?= $p["id_proyecto"] ?></td>
            <td><?= htmlspecialchars($p["nombre"]) ?></td>
            <td><?= htmlspecialchars($p["area"]) ?></td>
            <td><?= htmlspecialchars($p["periodo"]) ?></td>
            <td><?= htmlspecialchars($p["doc_nombres"] . " " . $p["doc_apellidos"]) ?></td>
            <td><?= $p["estado"] ?></td>
            <td><a href="proyecto.php?id=<?= $p["id_proyecto"] ?>">Ver / Gestionar</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
