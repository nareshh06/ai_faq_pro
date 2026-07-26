<?php
require_once 'config.php';
require_once 'session.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    exit;
}

$admin = $result->fetch_assoc();

if (!password_verify($password, $admin['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    exit;
}

// Credentials correct -> start session
$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_username'] = $admin['username'];

echo json_encode([
    'success' => true,
    'message' => 'Logged in successfully.',
    'username' => $admin['username']
]);

$stmt->close();
$conn->close();
?>
