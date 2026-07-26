<?php
/*
================================================
 SESSION BOOTSTRAP
 Included at the top of every protected admin
 endpoint. Starts (or resumes) the PHP session.
================================================
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Call this at the top of any endpoint that must only
 * be accessible to a logged-in admin. If nobody is
 * logged in, it immediately stops execution and returns
 * a 401 JSON response.
 */
function requireAdmin() {
    if (empty($_SESSION['admin_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Not authenticated. Please log in as admin first.'
        ]);
        exit;
    }
}
?>
