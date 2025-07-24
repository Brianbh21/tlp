<?php
session_start();

// Permitir 'almacen' y 'cedi'
if (!isset($_SESSION['nombre']) || ($_SESSION['rol'] !== 'almacen' && $_SESSION['rol'] !== 'cedi')) {
    header("Location: index.php");
    exit();
}

$nombre = $_SESSION['nombre'];
$rol = $_SESSION['rol'];
$cedula = $_SESSION['cedula'];
$id_usuario = $_SESSION['id_usuario'] ?? null;

// Función para definir la ruta de regreso según el rol
function redireccionarSegunRol($rol) {
    switch ($rol) {
        case 'almacen': return 'almacen.php';
        case 'cedi': return 'cedi.php';
        case 'empacador': return 'empacador.php';
        default: return 'index.php';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Traslado por Escáner</title>
  <link rel="stylesheet" href="./css/global.css?v=<?php echo time(); ?>">
  <script src="https://unpkg.com/html5-qrcode" defer></script>
</head>
<body>
  <div class="contenedor">
    <h2 class="titulo-panel">📦 Traslado por Escáner (Cámara)</h2>

    <div class="info-usuario">
      <strong>Usuario:</strong> <?= htmlspecialchars($nombre) ?><br>
      <strong>Rol:</strong> <?= htmlspecialchars($rol) ?> |
      <strong>Cédula:</strong> <?= htmlspecialchars($cedula) ?>
    </div>

    <div class="formulario-destino">
      <label for="destino">Selecciona destino:</label>
      <select id="destino" required>
        <option value="">-- Selecciona bodega destino --</option>
        <option value="inventario">Inventario</option>
        <option value="empaque">Empaque</option>
        <option value="CEDI">CEDI</option>
        <option value="almacen">Almacén</option>
      </select>
    </div>

    <div id="lector" style="width:100%; max-width:400px; margin:auto;"></div>

    <div id="estado-scan">
      <p>📍 Esperando escaneo...</p>
      <div id="resultado-qr" class="resultado"></div>
      <div id="listado-codigos"></div>
    </div>

    <form id="formulario-camara" method="POST" action="includes/registrar_traslado_camara.php">
      <input type="hidden" name="datos_codigos" id="datos_codigos">
      <input type="hidden" name="destino" id="destino_hidden">
      <input type="hidden" name="id_responsable" value="<?= $id_usuario ?>">
      <button type="submit" class="boton-enviar">Trasladar códigos escaneados</button>
    </form>

    <!-- Botón de regreso -->
    <div class="text-center" style="margin-top: 30px;">
      <a href="<?= redireccionarSegunRol($rol); ?>" class="btn-gris">⬅ Regresar</a>
    </div>
  </div>

  <script src="js/traslado_camara.js"></script>
</body>
</html>
