
<?php
/* api.php · P10 · mis eventos en JSON */

session_start();

// 1. Indicar que la respuesta será JSON
header('Content-Type: application/json; charset=utf-8');

// Función para responder con código HTTP y JSON
function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Verificar que exista una sesión iniciada
if (!isset($_SESSION['usuario']['id'])) {
    responder(401, [
        'error' => 'Inicia sesión para consultar tus eventos.'
    ]);
}

// Obtener los datos del usuario actual
$usuarioId = (int) $_SESSION['usuario']['id'];

// Verificar que la solicitud sea GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');

    responder(405, [
        'error' => 'Método no permitido. Usa GET.'
    ]);
}

// 3. Conectar con la base de datos
require __DIR__ . '/conexion.php';

// 4. Obtener los filtros de la URL
$q = mb_substr(trim($_GET['q'] ?? ''), 0, 60);
$categoriaId = (int) ($_GET['categoria'] ?? 0);

// Condiciones iniciales: siempre filtrar por usuario
$condiciones = ['e.usuario_id = ?'];
$tipos = 'i';
$valores = [$usuarioId];

// Filtro de búsqueda por título o descripción
if ($q !== '') {
    $patron = '%' . addcslashes($q, '%_\\') . '%';

    $condiciones[] = '(e.titulo LIKE ? OR e.descripcion LIKE ?)';

    $tipos .= 'ss';
    $valores[] = $patron;
    $valores[] = $patron;
}

// Filtro por categoría
if ($categoriaId > 0) {
    $condiciones[] = 'e.categoria_id = ?';

    $tipos .= 'i';
    $valores[] = $categoriaId;
}

// 5. Consulta de los eventos del usuario
$sql = "SELECT e.id, e.titulo, e.fecha,
               TIME_FORMAT(e.hora, '%H:%i') AS hora,
               c.nombre AS categoria, e.descripcion
          FROM eventos e
          INNER JOIN categorias c
                  ON c.id = e.categoria_id
         WHERE " . implode(' AND ', $condiciones) . "
         ORDER BY e.fecha, e.hora";

$stmt = $conexion->prepare($sql);

$stmt->bind_param($tipos, ...$valores);

$stmt->execute();

$eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conexion->close();

// 6. Responder con los eventos en JSON
responder(200, [
    'usuario' => $_SESSION['usuario']['nombre'] ?? '',
    'total' => count($eventos),
    'filtros' => [
        'q' => $q,
        'categoria' => $categoriaId
    ],
    'eventos' => $eventos
]);
