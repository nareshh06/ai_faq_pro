<?php
/*
================================================
 ANALYTICS ENDPOINT
 Returns summary stats for the Analytics tab:
 total FAQs, manual vs AI-learned count, total
 queries asked, database vs AI answered ratio,
 and the most recent queries.
================================================
*/

require_once 'config.php';
require_once 'session.php';
requireAdmin();
header('Content-Type: application/json');

$stats = [];

// Total FAQs
$r = $conn->query("SELECT COUNT(*) AS total FROM faqs");
$stats['total_faqs'] = (int) $r->fetch_assoc()['total'];

// Manual vs AI-learned FAQs
$r = $conn->query("SELECT source, COUNT(*) AS cnt FROM faqs GROUP BY source");
$stats['faqs_by_source'] = ['manual' => 0, 'ai' => 0];
while ($row = $r->fetch_assoc()) {
    $stats['faqs_by_source'][$row['source']] = (int) $row['cnt'];
}

// Total queries asked
$r = $conn->query("SELECT COUNT(*) AS total FROM query_logs");
$stats['total_queries'] = (int) $r->fetch_assoc()['total'];

// Queries answered by database vs AI vs none
$r = $conn->query("SELECT answered_by, COUNT(*) AS cnt FROM query_logs GROUP BY answered_by");
$stats['queries_by_source'] = ['database' => 0, 'ai' => 0, 'none' => 0];
while ($row = $r->fetch_assoc()) {
    $stats['queries_by_source'][$row['answered_by']] = (int) $row['cnt'];
}

// Recent queries (last 10)
$r = $conn->query("SELECT user_query, confidence, answered_by, created_at FROM query_logs ORDER BY id DESC LIMIT 10");
$stats['recent_queries'] = [];
while ($row = $r->fetch_assoc()) {
    $stats['recent_queries'][] = $row;
}

echo json_encode(['success' => true, 'stats' => $stats]);
$conn->close();
?>
