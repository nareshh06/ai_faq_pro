<?php
/*
================================================
 AI API CONFIGURATION
 ------------------------------------------------
 This project uses GROQ (https://console.groq.com)
 because it offers a genuinely FREE tier with no
 credit card required, and it is very fast.

 HOW TO GET YOUR FREE API KEY:
 1. Go to https://console.groq.com
 2. Sign up (free) with Google/GitHub/email
 3. Go to "API Keys" in the left menu
 4. Click "Create API Key" and copy it
 5. Paste it below as AI_API_KEY

 The free tier is generous enough for a college
 project demo (thousands of requests/day).

 ------------------------------------------------
 WANT TO USE A DIFFERENT FREE AI PROVIDER INSTEAD?
 This code calls an "OpenAI compatible" chat
 endpoint, so you can swap in:
   - OpenRouter (openrouter.ai) - free models available
   - Google Gemini (ai.google.dev) - needs slightly
     different request format, see note in ai_helper.php
 Just change AI_API_URL, AI_API_KEY and AI_MODEL below.
================================================
*/

define('AI_PROVIDER', 'groq');
define('AI_API_KEY', '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');
define('AI_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('AI_MODEL', 'llama-3.3-70b-versatile'); // free, fast, high quality Groq model

// Minimum confidence (%) required for a database match to be trusted.
// Below this, the AI is asked to generate a fresh answer instead.
define('CONFIDENCE_THRESHOLD', 45);
?>
