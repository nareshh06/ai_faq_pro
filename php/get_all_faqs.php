<?php
require_once 'config.php';
require_once 'session.php';
requireAdmin();
header('Content-Type: application/json');

$result = $conn->query("SELECT id, question, answer, keywords, category, source, created_at FROM faqs ORDER BY id DESC");

$faqs = [];
while ($row = $result->fetch_assoc()) {
    $faqs[] = $row;
}

echo json_encode(['success' => true, 'faqs' => $faqs]);
$conn->close();
?>
