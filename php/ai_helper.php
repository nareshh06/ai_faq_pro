<?php
/*
================================================
 AI HELPER
 Handles the actual HTTP request to the free AI
 provider (Groq, OpenAI-compatible endpoint).
================================================
*/

require_once 'ai_config.php';

/**
 * Calls the AI model to generate an answer.
 *
 * @param string $userQuestion  The question typed by the user
 * @param array  $contextFaqs   A few related FAQs pulled from our DB, used
 *                               as grounding context so the AI stays relevant
 *                               to this website (a simple form of RAG).
 * @return array ['success' => bool, 'answer' => string|null, 'error' => string|null]
 */
function generateAIAnswer($userQuestion, $contextFaqs = []) {

    if (empty(AI_API_KEY) || AI_API_KEY === 'PASTE_YOUR_FREE_GROQ_API_KEY_HERE') {
        return [
            'success' => false,
            'answer' => null,
            'error' => 'AI API key not configured yet. Add your free Groq key in php/ai_config.php'
        ];
    }

    // Build context text from related FAQs (grounding / mini-RAG)
    $contextText = '';
    if (!empty($contextFaqs)) {
        $contextText = "Here are some related existing FAQ entries from this website's knowledge base:\n";
        foreach ($contextFaqs as $faq) {
            $contextText .= "- Q: {$faq['question']}\n  A: {$faq['answer']}\n";
        }
    }

    $systemPrompt = "You are a helpful FAQ assistant embedded on a website. "
        . "Answer the user's question clearly and concisely (2-4 sentences max). "
        . "If related FAQ context is provided below, prefer to stay consistent with it. "
        . "If the question is unrelated to the website or you are unsure, still give your best "
        . "helpful general answer, but keep it brief and polite.\n\n" . $contextText;

    $payload = [
        'model' => AI_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userQuestion]
        ],
        'temperature' => 0.4,
        'max_tokens' => 300
    ];

    $ch = curl_init(AI_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . AI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'answer' => null, 'error' => 'Connection error: ' . $curlError];
    }

    $decoded = json_decode($response, true);

    if ($httpCode !== 200 || !isset($decoded['choices'][0]['message']['content'])) {
        $errMsg = $decoded['error']['message'] ?? 'Unknown AI API error (HTTP ' . $httpCode . ')';
        return ['success' => false, 'answer' => null, 'error' => $errMsg];
    }

    $answer = trim($decoded['choices'][0]['message']['content']);
    return ['success' => true, 'answer' => $answer, 'error' => null];
}
?>
