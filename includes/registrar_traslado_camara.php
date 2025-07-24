<?php
session_start();
require_once 'db.php';

// Validar rol permitido
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['almacen', 'empacador', 'cedi'])) {
    exit("❌ No autorizado");
}

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("❌ Método no permitido");
}

// Recolectar y validar datos del formulario
$datos = json_decode($_POST['datos_codigos'] ?? '', true);
$destino = $_POST['destino'] ?? '';
$cedula_responsable = $_SESSION['cedula'] ?? null;

if (!$datos || !$destino || !$cedula_responsable) {
    exit("❌ Faltan datos necesarios para procesar el traslado.");
}

$conn->begin_transaction();
try {
    foreach ($datos as $numero_lote => $cantidad) {
        // Buscar el lote actual
        $stmt = $conn->prepare("SELECT * FROM lotes WHERE numero_lote = ?");
        $stmt->bind_param("s", $numero_lote);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) continue;

        $lote = $res->fetch_assoc();
        $id_lote = $lote['id_lote'];
        $estado_origen = $lote['estado'];
        $cantidad_actual = $lote['cantidad_total'];
        $tipo_producto = $lote['tipo_producto'];
        $codigo_vitad = $lote['codigo_vitad'];

        // Ajustar cantidad si excede lo disponible
        if ($cantidad > $cantidad_actual) $cantidad = $cantidad_actual;

        // Actualizar lote original
        if ($cantidad === $cantidad_actual) {
            $conn->query("UPDATE lotes SET estado = '$destino' WHERE id_lote = $id_lote");
        } else {
            $conn->query("UPDATE lotes SET cantidad_total = cantidad_total - $cantidad WHERE id_lote = $id_lote");

            // Verificar si ya existe lote con mismo numero_lote y destino
            $stmt_destino = $conn->prepare("SELECT id_lote FROM lotes WHERE numero_lote = ? AND estado = ?");
            $stmt_destino->bind_param("ss", $numero_lote, $destino);
            $stmt_destino->execute();
            $res_destino = $stmt_destino->get_result();

            if ($res_destino->num_rows > 0) {
                $row_destino = $res_destino->fetch_assoc();
                $id_lote_destino = $row_destino['id_lote'];

                $stmt_update = $conn->prepare("UPDATE lotes SET cantidad_total = cantidad_total + ? WHERE id_lote = ?");
                $stmt_update->bind_param("ii", $cantidad, $id_lote_destino);
                $stmt_update->execute();
            } else {
                // Crear nuevo lote con datos heredados
                $fecha_empaque = $lote['fecha_empaque'];
                $fecha_vencimiento = $lote['fecha_vencimiento'];
                $planta_origen = $lote['planta_origen'];
                $id_empacador = $lote['id_empacador'];
                $embalaje = $lote['embalaje'];
                $cantidad_origen = $cantidad;

                $stmt_insert = $conn->prepare("INSERT INTO lotes (
                    numero_lote, tipo_producto, fecha_empaque, fecha_vencimiento,
                    cantidad_total, estado, codigo_vitad, planta_origen,
                    id_empacador, embalaje, cantidad_origen
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt_insert->bind_param(
                    "ssssisssiii",
                    $numero_lote,
                    $tipo_producto,
                    $fecha_empaque,
                    $fecha_vencimiento,
                    $cantidad,
                    $destino,
                    $codigo_vitad,
                    $planta_origen,
                    $id_empacador,
                    $embalaje,
                    $cantidad_origen
                );

                $stmt_insert->execute();
            }
        }

        // Registrar movimiento
        $stmt_mov = $conn->prepare("INSERT INTO movimientos (
            id_lote, origen, destino, cantidad, fecha_movimiento, id_responsable, estado_origen, estado_destino
        ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
        $stmt_mov->bind_param(
            "ississs",
            $id_lote,
            $estado_origen,
            $destino,
            $cantidad,
            $cedula_responsable,
            $estado_origen,
            $destino
        );
        $stmt_mov->execute();
    }

    $conn->commit();
    echo "✅ Traslados registrados correctamente.<br><br>";
    echo '<div class="text-center">
            <a href="javascript:history.back()" class="btn-gris">⬅ Regresar</a>
          </div>';
} catch (Exception $e) {
    $conn->rollback();
    echo "❌ Error en el proceso: " . $e->getMessage();
}
?>
