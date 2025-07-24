<?php
// resultado_lote.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['id'])) {
    die("ID de lote no especificado");
}

require_once 'db.php';

$id_lote = $_GET['id'];

$sql = "SELECT qr_codigo FROM lotes WHERE id_lote = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_lote);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $qr_filename = $row['qr_codigo'];
    $qr_path = "../qrcodes/$qr_filename";
    if (!file_exists($qr_path)) {
        die("La imagen QR no fue encontrada en: $qr_path");
    }
} else {
    die("No se encontró el lote con ID: $id_lote");
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lote Registrado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 30px;
        }
        img {
            border: 1px solid #ccc;
            margin: 20px auto;
        }
        .btn {
            display: inline-block;
            margin: 10px;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
        }
        .print {
            background-color: #007bff;
            color: white;
        }
        .print:hover {
            background-color: #0056b3;
        }
        .back {
            background-color: #6c757d;
            color: white;
        }
        .back:hover {
            background-color: #5a6268;
        }

        /* Ocultar elementos en impresión */
        @media print {
            .no-print {
                display: none !important;
            }

            a[href]:after {
                content: "";
            }

            body {
                margin: 0;
            }
        }
        .print-info {
    margin-top: 10px;
    font-size: 14px;
    color: #000;
    padding: 10px;
}

@media print {
    .print-info {
        display: block;
    }
}

    </style>
</head>
<body>
      <p class="no-print">Este es el código QR generado:</p>

      <div class="print-info">
    <p><strong>📅 Fecha de impresión:</strong> <?= date('d/m/Y') ?></p>
    <p><strong>🕒 Hora:</strong> <?= date('H:i:s') ?></p>
</div>
    <img id="qrImage" src="<?= htmlspecialchars($qr_path) ?>" alt="QR del lote" width="250">


    <div class="no-print">
        <a href="#" onclick="window.print()" class="btn print">🖨️ Imprimir QR</a>
        <a href="../crear_lote.php" class="btn back">← Volver al formulario</a>
    </div>
</body>
</html>
