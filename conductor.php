<?php
// tlp/conductor.php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db.php';

// 1) Validar rol
if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'conductor') {
    header('Location: index.php');
    exit;
}

$nombre  = $_SESSION['nombre'] ?? 'Conductor';
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

// 2) Redirecciones pedidas (sin tocar la vista)
if (isset($_GET['view'])) {
    if ($_GET['view'] === 'despachar') { header('Location: despacho_manual.php'); exit; }
    if ($_GET['view'] === 'inventario') { header('Location: ver_lotes.php'); exit; }
    // 'traslados' se queda aquí
}

// 3) Traer TODAS las placas (estados que NO son bodegas)
$placas = [];
$resPl = $conn->query("
    SELECT DISTINCT estado 
      FROM lotes 
     WHERE estado NOT IN ('empaque','almacen','CEDI','inventario')
       AND estado IS NOT NULL AND estado <> ''
     ORDER BY estado
");
while ($r = $resPl->fetch_assoc()) { $placas[] = $r['estado']; }

// 4) Si envían la placa desde el selector, validarla y guardar en sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_placa') {
    $placa_sel = trim($_POST['placa'] ?? '');
    if ($placa_sel === '' || !in_array($placa_sel, $placas, true)) {
        $_SESSION['mensaje'] = '❌ Selecciona una placa válida.';
        header('Location: conductor.php'); exit;
    }
    $_SESSION['placa'] = $placa_sel;
    $_SESSION['mensaje'] = '✅ Placa asignada: ' . $placa_sel;
    header('Location: conductor.php'); exit;
}

// 5) Si ya hay placa, cargar traslados pendientes de esa placa
$placa = $_SESSION['placa'] ?? '';
$traslados = [];
$cnt = 0;

if ($placa !== '') {
    $stmt = $conn->prepare("
        SELECT 
            m.id_movimiento, m.id_lote, m.tipo_producto, m.cantidad, 
            m.origen, m.fecha_movimiento,
            l.numero_lote
        FROM movimientos m
        LEFT JOIN lotes l ON l.id_lote = m.id_lote
        WHERE m.estado_destino = ?
          AND m.estado_aceptacion = 'pendiente'
        ORDER BY m.id_movimiento DESC
    ");
    $stmt->bind_param('s', $placa);
    $stmt->execute();
    $traslados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $cnt = count($traslados);
}

// 6) Pasar variables a la vista
$GLOBALS['nombre']    = $nombre;
$GLOBALS['mensaje']   = $mensaje;
$GLOBALS['placa']     = $placa;
$GLOBALS['placas']    = $placas;   // << todas las placas para el selector
$GLOBALS['traslados'] = $traslados;
$GLOBALS['cnt']       = $cnt;

require_once __DIR__ . '/views/conductor_view.php';
