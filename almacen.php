<?php
// /var/www/html/tlp/almacen.php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

// Solo ALMACÉN
if (empty($_SESSION['nombre']) || ($_SESSION['rol'] ?? '') !== 'almacen') {
    echo "Acceso denegado. Debes iniciar sesión como ALMACÉN.";
    exit();
}

$nombre = $_SESSION['nombre'];
$rol    = $_SESSION['rol'];
$cedula = $_SESSION['cedula'] ?? '';

// 1) Traer traslados pendientes para ALMACÉN
$sql = <<<SQL
 SELECT m.*, l.tipo_producto, l.numero_lote
   FROM movimientos m
   LEFT JOIN lotes l ON l.id_lote = m.id_lote
  WHERE UPPER(m.destino) = 'ALMACEN'
    AND m.estado_aceptacion = 'pendiente'
  ORDER BY m.fecha_movimiento ASC
SQL;

$res = $conn->query($sql);
$traslados_pendientes = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// 2) Mensaje flash
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

// 3) Renderizar vista
include __DIR__ . '/views/almacen_view.php';
