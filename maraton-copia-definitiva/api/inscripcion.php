<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Solo se acepta POST']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos: se esperaba un objeto JSON']);
    exit;
}

// Incluir configuración
include '../includes/config.php';

$campos_obligatorios = ['nombre', 'dni', 'email', 'carrera', 'fecha_nacimiento', 'talle_remera'];
foreach ($campos_obligatorios as $campo) {
    if (empty($input[$campo])) {
        echo json_encode(['success' => false, 'message' => "$campo es obligatorio"]);
        exit;
    }
}

try {
    $conexion = new PDO("mysql:host=localhost;dbname=maraton_db;charset=utf8", "root", "");
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar DNI duplicado
    $stmt = $conexion->prepare("SELECT id FROM inscripciones WHERE dni = ?");
    $stmt->execute([$input['dni']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'DNI ya registrado']);
        exit;
    }

    // Validar edad
    $fecha_nac = new DateTime($input['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nac)->y;

    if ($input['carrera'] === 'Kids' && $edad > 12) {
        echo json_encode(['success' => false, 'message' => 'Kids es solo para menores de 13 años']);
        exit;
    }
    if ($input['carrera'] !== 'Kids' && $edad < 13) {
        echo json_encode(['success' => false, 'message' => 'Carreras adultas son para mayores de 12 años']);
        exit;
    }

    // Validar carrera contra configuración
    $carreras_validas = array_keys(MARATON_2026_CONFIG['limites_inscripciones']);
    if (!in_array($input['carrera'], $carreras_validas)) {
        echo json_encode(['success' => false, 'message' => 'Carrera no válida']);
        exit;
    }

    // Campos opcionales
    $cobertura_medica = $input['cobertura_medica'] ?? '';
    $numero_afiliado = $input['numero_afiliado'] ?? '';
    $telefono_emergencia = $input['telefono_emergencia'] ?? '';

    // Insertar
    $sql = "INSERT INTO inscripciones (
        nombre, dni, email, carrera, fecha_nacimiento, talle_remera,
        cobertura_medica, numero_afiliado, telefono_emergencia, fecha_inscripcion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        $input['nombre'],
        $input['dni'],
        $input['email'],
        $input['carrera'],
        $input['fecha_nacimiento'],
        $input['talle_remera'],
        $cobertura_medica,
        $numero_afiliado,
        $telefono_emergencia
    ]);

    $id = $conexion->lastInsertId();
    $qr = "MUNICIPIO ITUZAINGO\nMARATON 2026\nID: $id\nDNI: {$input['dni']}\nCARRERA: {$input['carrera']}";
    echo json_encode([
        'success' => true,
        'qr_data' => $qr,
        'numero_corredor' => $id
    ]);

} catch (Exception $e) {
    error_log("Error en inscripcion.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>