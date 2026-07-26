<?php
require_once 'session.php';
header('Content-Type: application/json');

if (!empty($_SESSION['admin_id'])) {
    echo json_encode([
        'success' => true,
        'loggedIn' => true,
        'username' => $_SESSION['admin_username']
    ]);
} else {
    echo json_encode([
        'success' => true,
        'loggedIn' => false
    ]);
}
?>
