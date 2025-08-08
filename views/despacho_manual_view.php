<?php
// Reemplaza cualquier session_start() directa por esto:
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'includes/db.php';

// ... el resto de tu vista ...
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Despacho Manual</title>
    <link rel="stylesheet" href="./css/global.css">
</head>
<body>
    <div class="container">
        <h2>Despacho Manual - Lotes asignados</h2>

        <form action="../includes/despacho_manual_logic.php" method="POST">
            <label for="factura">Número de Factura:</label>
            <input type="text" name="factura" id="factura" required>
            <br><br>

            <table>
                <thead>
                <tr>
                    <th>Seleccionar</th>
                    <th>Número de Lote</th>
                    <th>Tipo Producto</th>
                    <th>Fecha Empaque</th>
                    <th>Fecha Vencimiento</th>
                    <th>Cantidad Disponible</th>
                    <th>Cantidad a Despachar</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $placa = $_SESSION['placa'];
                $stmt = $conn->prepare("
                    SELECT *
                    FROM lotes
                    WHERE estado = ?
                      AND cantidad_total >= 0
                ");
                $stmt->bind_param("s", $placa);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()):
                    $nl = htmlspecialchars($row['numero_lote']);
                    $tp = htmlspecialchars($row['tipo_producto']);
                    $ct = (int)$row['cantidad_total'];
                ?>
                    <tr>
                        <td><input type="checkbox" name="seleccionados[]" value="<?= $nl ?>"></td>
                        <td><?= $nl ?></td>
                        <td><?= $tp ?></td>
                        <td><?= $row['fecha_empaque'] ?></td>
                        <td><?= $row['fecha_vencimiento'] ?></td>
                        <td><?= $ct ?></td>
                        <td>
                            <input
                                type="number"
                                name="cantidad[<?= $nl ?>]"
                                min="1"
                                max="<?= $ct ?>"
                                required
                            >
                        </td>
                    </tr>
                <?php endwhile;
                $stmt->close();
                ?>
                </tbody>
            </table>

            <br>
            <button type="submit">Despachar lote(s)</button>
        </form>

        <br>
        <a href="../conductor.php"><button>Volver</button></a>
    </div>
</body>
</html>
