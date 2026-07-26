<?php
require_once 'session.php';
header('Content-Type: application/json');

$_SESSION = [];
session_destroy();

echo json_encode(['success' => true, 'message' => 'Logged out.']);
?>
