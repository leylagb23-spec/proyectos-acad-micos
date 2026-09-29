<?php
$actual = basename($_SERVER["PHP_SELF"]);

$enlaces = [
    "index.php"       => "Inicio",
    "catalogos.php"    => "Áreas y períodos",
    "docentes.php"     => "Docentes",
    "estudiantes.php"  => "Estudiantes",
    "proyectos.php"    => "Proyectos",
];
?>
<nav class="menu">
    <?php foreach ($enlaces as $archivo => $texto): ?>
        <a href="<?= $archivo ?>" class="<?= $actual === $archivo ? "activo" : "" ?>"><?= $texto ?></a>
    <?php endforeach; ?>
</nav>
