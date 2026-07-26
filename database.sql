-- ============================================
-- AI FAQ GENERATOR PRO - DATABASE SCHEMA
-- ============================================
-- Import this file in phpMyAdmin OR run:
-- mysql -u root -p < database.sql
-- ============================================

CREATE DATABASE IF NOT EXISTS ai_faq_pro_db;
USE ai_faq_pro_db;

-- Table to store FAQ question-answer pairs
CREATE TABLE IF NOT EXISTS faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    keywords VARCHAR(500) NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
    source ENUM('manual', 'ai') DEFAULT 'manual',   -- was this added by admin or auto-learned from AI?
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table to log every user query (used for the Analytics tab)
CREATE TABLE IF NOT EXISTS query_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_query VARCHAR(500) NOT NULL,
    matched_faq_id INT DEFAULT NULL,
    confidence FLOAT DEFAULT 0,
    answered_by ENUM('database', 'ai', 'none') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table to store admin login accounts (passwords are bcrypt-hashed, never plain text)
-- This table starts EMPTY on purpose — visit php/create_admin.php once after import
-- to create your first admin account. See README for details.
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- SAMPLE DATA
-- ============================================
INSERT INTO faqs (question, answer, keywords, category, source) VALUES
('What is this website about?', 'This is an AI powered FAQ system. It first checks its own knowledge base for an answer, and if it cannot find a confident match, it asks an AI model to generate one and remembers it for next time.', 'website, about, purpose, site', 'General', 'manual'),
('How do I create an account?', 'Click on the Sign Up button on the top right corner, fill in your details, and verify your email to create an account.', 'account, register, signup, create, join', 'Account', 'manual'),
('How can I reset my password?', 'Go to the login page and click Forgot Password. Enter your registered email to receive a password reset link.', 'password, reset, forgot, change, recover', 'Account', 'manual'),
('What payment methods are accepted?', 'We accept credit cards, debit cards, UPI, net banking, and popular digital wallets.', 'payment, pay, method, card, upi, wallet', 'Billing', 'manual'),
('How do I contact customer support?', 'You can reach our support team via the Contact Us page, or email us at support@example.com. We usually respond within 24 hours.', 'contact, support, help, customer, email', 'Support', 'manual'),
('Is my data safe and secure?', 'Yes, we use industry standard encryption and follow strict data protection policies to keep your information safe.', 'data, safe, secure, privacy, security', 'Security', 'manual'),
('Can I cancel my subscription anytime?', 'Yes, you can cancel your subscription anytime from your account settings without any cancellation fee.', 'cancel, subscription, unsubscribe, stop, plan', 'Billing', 'manual'),
('Do you offer a free trial?', 'Yes, we offer a 7 day free trial for all new users with full access to premium features.', 'free, trial, demo, test, try', 'Billing', 'manual'),
('How does the AI matching work?', 'The system first tokenizes your question and scores it against the FAQ database using keyword similarity. If no confident match is found, it sends your question to a connected AI model to generate a fresh answer.', 'ai, matching, work, algorithm, how', 'General', 'manual'),
('What should I do if I face a technical issue?', 'Please try refreshing the page first. If the issue persists, report it to our support team with a screenshot for faster resolution.', 'technical, issue, problem, bug, error', 'Support', 'manual');
