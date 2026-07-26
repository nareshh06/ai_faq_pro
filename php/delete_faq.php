<?php
require_once 'config.php';
require_once 'session.php';
requireAdmin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid FAQ id.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM faqs WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'FAQ deleted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting FAQ: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
