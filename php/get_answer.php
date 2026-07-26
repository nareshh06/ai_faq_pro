<?php
/*
================================================
 HYBRID FAQ ANSWER ENGINE
 1. Tokenize the user's question
 2. Score it against every FAQ in the database
 3. If a confident match is found -> return it instantly (fast, free)
 4. If NOT confident -> ask the connected AI model to generate an answer,
    using the top related FAQs as grounding context
 5. Save the AI's answer back into the database as a "learned" FAQ,
    so next time the same/similar question is asked, it's answered
    instantly from the database instead of calling the AI again
================================================
*/

require_once 'config.php';
require_once 'ai_helper.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$userQuery = isset($data['query']) ? trim($data['query']) : '';

if (empty($userQuery)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a question.']);
    exit;
}

// ---------- Helper: clean & tokenize text ----------
function tokenize($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $words = preg_split('/\s+/', trim($text));

    $stopwords = ['is','am','are','the','a','an','to','of','in','on','for',
                  'and','how','do','i','can','my','what','does','it','this',
                  'you','your','please','me','with','be'];

    $words = array_filter($words, fn($w) => $w !== '' && !in_array($w, $stopwords));
    return array_values($words);
}

$queryWords = tokenize($userQuery);

if (empty($queryWords)) {
    echo json_encode(['success' => false, 'message' => 'Could not understand the question. Try rephrasing it.']);
    exit;
}

// ---------- Step 1: score against every FAQ ----------
$result = $conn->query("SELECT id, question, answer, keywords, category FROM faqs");

$scored = []; // will hold every faq with its score, so we can also use top-3 as AI context

if ($result && $result->num_rows > 0) {
    while ($faq = $result->fetch_assoc()) {
        $faqWords = tokenize($faq['question'] . ' ' . $faq['keywords']);
        if (empty($faqWords)) continue;

        $matchCount = 0;
        foreach ($queryWords as $qw) {
            foreach ($faqWords as $fw) {
                if ($qw === $fw || (strlen($qw) > 3 && strpos($fw, $qw) !== false) ||
                    (strlen($fw) > 3 && strpos($qw, $fw) !== false)) {
                    $matchCount++;
                    break;
                }
            }
        }

        $score = count($queryWords) > 0 ? ($matchCount / count($queryWords)) : 0;
        $faq['score'] = round($score * 100, 1);
        $scored[] = $faq;
    }
}

// Sort by score descending
usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

$bestMatch = $scored[0] ?? null;
$confidence = $bestMatch['score'] ?? 0;

// ---------- Step 2: confident DB match found ----------
if ($bestMatch && $confidence >= CONFIDENCE_THRESHOLD) {

    logQuery($conn, $userQuery, $bestMatch['id'], $confidence, 'database');

    echo json_encode([
        'success' => true,
        'answer' => $bestMatch['answer'],
        'category' => $bestMatch['category'],
        'confidence' => $confidence,
        'source' => 'database'
    ]);
    exit;
}

// ---------- Step 3: not confident -> ask the AI ----------
// Use the top 3 related FAQs (even if low score) as grounding context
$contextFaqs = array_slice($scored, 0, 3);

$aiResult = generateAIAnswer($userQuery, $contextFaqs);

if ($aiResult['success']) {

    // ---------- Step 4: self-learn - save AI answer into the FAQ table ----------
    $autoKeywords = implode(', ', array_slice($queryWords, 0, 8));
    $stmt = $conn->prepare("INSERT INTO faqs (question, answer, keywords, category, source) VALUES (?, ?, ?, 'General', 'ai')");
    $stmt->bind_param('sss', $userQuery, $aiResult['answer'], $autoKeywords);
    $stmt->execute();
    $newFaqId = $stmt->insert_id;
    $stmt->close();

    logQuery($conn, $userQuery, $newFaqId, $confidence, 'ai');

    echo json_encode([
        'success' => true,
        'answer' => $aiResult['answer'],
        'category' => 'AI Generated',
        'confidence' => 100,
        'source' => 'ai'
    ]);

} else {
    logQuery($conn, $userQuery, null, $confidence, 'none');

    echo json_encode([
        'success' => false,
        'message' => "I couldn't find an answer in the knowledge base, and the AI service is unavailable right now (" . $aiResult['error'] . ").",
        'confidence' => $confidence
    ]);
}

$conn->close();

// ---------- Utility: log every query for the Analytics tab ----------
function logQuery($conn, $query, $faqId, $confidence, $answeredBy) {
    $stmt = $conn->prepare("INSERT INTO query_logs (user_query, matched_faq_id, confidence, answered_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('sids', $query, $faqId, $confidence, $answeredBy);
    $stmt->execute();
    $stmt->close();
}
?>
