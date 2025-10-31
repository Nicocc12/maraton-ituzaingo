<?php
header('Content-Type: application/json');

// Incluir configuración global
include '../includes/config.php';

try {
    $conexion = new PDO("mysql:host=localhost;dbname=maraton_db;charset=utf8", "root", "");
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = "SELECT carrera, COUNT(*) as total FROM inscripciones GROUP BY carrera";
    $inscripciones = $conexion->query($query)->fetchAll(PDO::FETCH_KEY_PAIR);

    // Usar límites desde config.php
    $limites = MARATON_2026_CONFIG['limites_inscripciones'];

    $stock = [
        '10km' => ($limites['10km'] ?? 0) - ($inscripciones['10km'] ?? 0),
        '3km' => ($limites['3km'] ?? 0) - ($inscripciones['3km'] ?? 0),
        'kids' => ($limites['Kids'] ?? 0) - ($inscripciones['Kids'] ?? 0),
        'total' => array_sum($inscripciones)
    ];

    echo json_encode(['success' => true, 'stock' => $stock]);

} catch (Exception $e) {
    error_log("Error en stock.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al obtener el stock']);
}
?>