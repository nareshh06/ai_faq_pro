# AI FAQ Generator (College Mini Project)

A simple AI-style FAQ system: users type a question in natural language, and a
PHP matching engine scores it against a MySQL-stored knowledge base (using
keyword tokenizing + similarity scoring) to return the best answer with a
confidence percentage. Includes an admin panel to add/delete FAQs.

## Tech Stack
- **Frontend:** HTML, CSS, JavaScript (Fetch API / AJAX)
- **Backend:** PHP (mysqli)
- **Database:** MySQL

## Folder Structure
```
ai_faq_generator/
├── index.html              → Main page (chat UI + admin panel)
├── css/
│   └── style.css           → Styling
├── js/
│   └── script.js           → Frontend logic (AJAX calls)
├── php/
│   ├── config.php          → Database connection
│   ├── get_answer.php      → AI matching engine (core logic)
│   ├── add_faq.php         → Add new FAQ (admin)
│   ├── get_all_faqs.php    → List all FAQs (admin)
│   └── delete_faq.php      → Delete FAQ (admin)
└── database.sql            → Database schema + sample data
```

## Setup Instructions (using XAMPP)

1. **Install XAMPP** (includes Apache + MySQL + PHP) if you don't have it.
2. Copy the `ai_faq_generator` folder into your `htdocs` directory:
   - Windows: `C:\xampp\htdocs\ai_faq_generator`
   - Mac: `/Applications/XAMPP/htdocs/ai_faq_generator`
3. Start **Apache** and **MySQL** from the XAMPP Control Panel.
4. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
5. Click **Import**, choose `database.sql`, and click **Go**.
   This creates the `ai_faq_db` database with sample FAQs.
6. If your MySQL username/password is different from the defaults
   (`root` / no password), update them in `php/config.php`:
   ```php
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
7. Open your browser and go to:
   ```
   http://localhost/ai_faq_generator/index.html
   ```
8. Try asking questions like:
   - "How do I reset my password?"
   - "What payment methods do you accept?"
   - "Is my data secure?"

## How the "AI" Matching Works
1. The user's question is cleaned and split into individual words (tokens),
   removing common stopwords (is, the, a, how, etc.).
2. Each FAQ's question + keywords are tokenized the same way.
3. The engine counts overlapping/partial-matching words between the user
   query and every FAQ entry (a simplified Jaccard-similarity approach).
4. The FAQ with the highest similarity score is returned as the answer,
   along with a confidence percentage.
5. If no FAQ scores above 25% confidence, the system tells the user it
   couldn't find a good match.
6. Every query is logged in the `query_logs` table — useful to show in
   your project demo/report as "the system learns from usage patterns".

## Possible Extensions (for viva / extra marks)
- Replace the keyword scorer with a real NLP library (e.g. PHP-ML) or call
  an external AI API (OpenAI/Gemini) for smarter matching.
- Add user authentication for the admin panel (currently open access).
- Add categories/filter dropdown on the frontend.
- Show analytics dashboard from the `query_logs` table (most asked
  questions, unanswered questions, etc.).

## Notes
- This project uses **prepared statements** (`bind_param`) to prevent SQL
  injection.
- `escapeHtml()` in JS prevents basic HTML/script injection when rendering
  FAQ content in the admin list.
- For a real production app you'd add authentication before exposing
  `add_faq.php` / `delete_faq.php`.

