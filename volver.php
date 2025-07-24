<?php
session_start();

if (!isset($_SESSION['rol'])) {
    header("Location: index.php");
    exit();
}

switch ($_SESSION['rol']) {
    case 'empacador':
        header("Location: empacador.php");
        break;
    case 'almacen':
        header("Location: almacen.php");
        break;
    case 'cedi':
        header("Location: cedi.php");
        break;
    case 'inventario':
        header("Location: inventario.php");
        break;
    case 'admin':
        header("Location: admin.php");
        break;
    default:
        header("Location: index.php");
        break;
}
exit();
