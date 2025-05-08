<?php
require_once 'config/functions.php';

$type = $_GET['type'] ?? '';
$accounts = getAccountNamesByType($type);

header('Content-Type: application/json');
echo json_encode($accounts);