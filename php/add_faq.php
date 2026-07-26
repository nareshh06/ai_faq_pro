<?php
require_once 'config.php';
require_once 'session.php';
requireAdmin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$question = isset($data['question']) ? trim($data['question']) : '';
$answer   = isset($data['answer']) ? trim($data['answer']) : '';
$keywords = isset($data['keywords']) ? trim($data['keywords']) : '';
$category = isset($data['category']) ? trim($data['category']) : 'General';

if (empty($question) || empty($answer) || empty($keywords)) {
    echo json_encode(['success' => false, 'message' => 'Question, answer and keywords are all required.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO faqs (question, answer, keywords, category, source) VALUES (?, ?, ?, ?, 'manual')");
$stmt->bind_param('ssss', $question, $answer, $keywords, $category);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'FAQ added successfully!', 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error adding FAQ: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
