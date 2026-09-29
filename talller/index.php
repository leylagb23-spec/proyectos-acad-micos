<?php
require "conexion.php";

$activos = $pdo->query("SELECT COUNT(*) FROM proyectos WHERE activo = 1 AND estado != 'Finalizado'")
               ->fetchColumn();

$finalizados = $pdo->query("SELECT COUNT(*) FROM proyectos WHERE activo = 1 AND estado = 'Finalizado'")
                   ->fetchColumn();

$propuestas = $pdo->query("SELECT COUNT(*) FROM proyectos WHERE activo = 1 AND estado = 'Propuesta'")
                  ->fetchColumn();

$total = $pdo->query("SELECT COUNT(*) FROM proyectos WHERE activo = 1")->fetchColumn();
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

    <h1>Sistema de registro y seguimiento de proyectos académicos</h1>

    <ul>
        <li>Proyectos activos (no finalizados): <strong><?= $activos ?></strong>
            (<a href="proyectos.php?grupo=activos">ver</a>)</li>
        <li>Proyectos finalizados: <strong><?= $finalizados ?></strong>
            (<a href="proyectos.php?grupo=finalizados">ver</a>)</li>
        <li>Proyectos en estado Propuesta: <strong><?= $propuestas ?></strong>
            (<a href="proyectos.php?estado=Propuesta">ver</a>)</li>
        <li>Total de proyectos registrados: <strong><?= $total ?></strong></li>
    </ul>
</body>
</html>
