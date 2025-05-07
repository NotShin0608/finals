<?php
require_once '../config/functions.php';

header('Content-Type: application/json');

try {
    $reference = generateReferenceNumber('JE');
    echo json_encode(['success' => true, 'reference' => $reference]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}