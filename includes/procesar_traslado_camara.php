<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['empacador', 'almacen', 'cedi'])) {
    http_response_code(403);
    exit('Acceso denegado');
}

$id_usuario = $_SESSION['id_usuario'] ?? null;
$datos_json = $_POST['datos_traslado'] ?? '{}';
$datos = json_decode($datos_json, true);

if (!$id_usuario || !is_array($datos)) {
    exit("Error en los datos enviados.");
}

$resumen = [];

$conn->begin_transaction();

try {
    foreach ($datos as $qr_codigo => $cantidad) {
        // Buscar el lote por qr_codigo
        $stmt = $conn->prepare("SELECT id_lote, cantidad_total, estado FROM lotes WHERE qr_codigo = ?");
        $stmt->bind_param("s", $qr_codigo);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            $resumen[] = "❌ Código $qr_codigo no encontrado.";
            continue;
        }

        $lote = $res->fetch_assoc();

        $estado_usuario = $_SESSION['rol'];
$estado_permitido = match ($estado_usuario) {
    'empacador' => 'empaque',
    'almacen'   => 'almacen',
    'cedi'      => 'CEDI',
    default     => null
};

if ($lote['estado'] !== $estado_permitido) {
    $resumen[] = "⚠️ Lote $qr_codigo no está en tu bodega autorizada ($estado_permitido).";
    continue;
}


        if ($cantidad > $lote['cantidad_total']) {
            $resumen[] = "❌ Lote $qr_codigo no tiene suficientes unidades.";
            continue;
        }

        $nuevo_estado = 'almacen';

        if ($cantidad == $lote['cantidad_total']) {
            // Traslado completo
            $update = $conn->prepare("UPDATE lotes SET estado = ? WHERE id_lote = ?");
            $update->bind_param("si", $nuevo_estado, $lote['id_lote']);
            $update->execute();
        } else {
            // Parcial: dividir lote
            $restante = $lote['cantidad_total'] - $cantidad;

            $update = $conn->prepare("UPDATE lotes SET cantidad_total = ? WHERE id_lote = ?");
            $update->bind_param("ii", $restante, $lote['id_lote']);
            $update->execute();

            $insert = $conn->prepare("
                INSERT INTO lotes (numero_lote, tipo_producto, fecha_empaque, fecha_vencimiento, cantidad_total, qr_codigo, planta_origen, id_empacador, estado, codigo_vitad, embalaje)
                SELECT CONCAT(numero_lote, '-Q', UNIX_TIMESTAMP()), tipo_producto, fecha_empaque, fecha_vencimiento, ?, CONCAT('QR_', UNIX_TIMESTAMP()), planta_origen, id_empacador, ?, codigo_vitad, embalaje
                FROM lotes WHERE id_lote = ?
            ");
            $insert->bind_param("isi", $cantidad, $nuevo_estado, $lote['id_lote']);
            $insert->execute();
        }

        // Registrar movimiento
        $mov = $conn->prepare("INSERT INTO movimientos (id_lote, origen, destino, cantidad, fecha_movimiento, id_responsable, estado_origen, estado_destino)
                               VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
        $mov->bind_param("issiiss", $lote['id_lote'], $lote['estado'], $nuevo_estado, $cantidad, $id_usuario, $lote['estado'], $nuevo_estado);
        $mov->execute();

        $resumen[] = "✅ Lote $qr_codigo: $cantidad unidad(es) trasladadas.";
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $resumen[] = "❌ Error en traslado: " . $e->getMessage();
}

foreach ($resumen as $linea) {
    echo $linea . "<br>";
}
?>
