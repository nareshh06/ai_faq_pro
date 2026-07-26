// ================================================
// FAQ.ai — FRONTEND LOGIC
// ================================================

const API_BASE = "php";

// ================= AUTH STATE =================
let isLoggedIn = false;
let currentAdminUsername = "";

async function checkSession() {
  try {
    const res = await fetch(`${API_BASE}/check_session.php`);
    const data = await res.json();
    isLoggedIn = !!data.loggedIn;
    currentAdminUsername = data.username || "";
  } catch (err) {
    isLoggedIn = false;
    console.error(err);
  }
  updateAuthUI();
}

function updateAuthUI() {
  const statusEl = document.getElementById("adminStatus");
  const usernameLabel = document.getElementById("adminUsernameLabel");

  if (isLoggedIn) {
    statusEl.style.display = "flex";
    usernameLabel.textContent = `👤 ${currentAdminUsername}`;
  } else {
    statusEl.style.display = "none";
  }

  ["admin", "analytics"].forEach(tabId => {
    const gate = document.getElementById(`adminLoginGate-${tabId}`);
    const content = document.getElementById(`adminContent-${tabId}`);
    if (!gate || !content) return;
    gate.style.display = isLoggedIn ? "none" : "flex";
    content.style.display = isLoggedIn ? (tabId === "admin" ? "grid" : "block") : "none";
  });
}

// Handle both login forms (admin tab + analytics tab) the same way
document.querySelectorAll(".login-form").forEach(form => {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const username = form.querySelector(".login-username").value.trim();
    const password = form.querySelector(".login-password").value.trim();
    const msgEl = form.parentElement.querySelector(".login-msg");
    const target = form.dataset.target;

    try {
      const res = await fetch(`${API_BASE}/login.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, password })
      });
      const data = await res.json();

      if (data.success) {
        isLoggedIn = true;
        currentAdminUsername = data.username;
        updateAuthUI();
        if (target === "admin") loadAllFaqs();
        if (target === "analytics") loadAnalytics();
      } else {
        msgEl.textContent = data.message;
        msgEl.className = "login-msg form-msg error";
      }
    } catch (err) {
      msgEl.textContent = "⚠️ Could not connect to the server.";
      msgEl.className = "login-msg form-msg error";
      console.error(err);
    }
  });
});

document.getElementById("logoutBtn").addEventListener("click", async () => {
  try {
    await fetch(`${API_BASE}/logout.php`);
  } catch (err) {
    console.error(err);
  }
  isLoggedIn = false;
  currentAdminUsername = "";
  updateAuthUI();
  // Send user back to the chat tab after logging out
  document.querySelector('.side-btn[data-tab="chat"]').click();
});

// Check session on page load
checkSession();

// ================= TAB SWITCHING =================
const sideButtons = document.querySelectorAll(".side-btn");
const tabSections = document.querySelectorAll(".tab-section");

sideButtons.forEach(btn => {
  btn.addEventListener("click", async () => {
    sideButtons.forEach(b => b.classList.remove("active"));
    tabSections.forEach(t => t.classList.remove("active"));

    btn.classList.add("active");
    document.getElementById(btn.dataset.tab).classList.add("active");

    if (btn.dataset.tab === "admin" || btn.dataset.tab === "analytics") {
      await checkSession(); // re-verify in case the session expired
      if (isLoggedIn) {
        if (btn.dataset.tab === "admin") loadAllFaqs();
        if (btn.dataset.tab === "analytics") loadAnalytics();
      }
    }
  });
});

// ================= DARK MODE =================
const themeToggle = document.getElementById("themeToggle");
function applyTheme(isDark) {
  document.body.classList.toggle("dark", isDark);
  themeToggle.textContent = isDark ? "☀️ Light Mode" : "🌙 Dark Mode";
}
// default to light; toggle in-session (kept simple, no localStorage per artifact rules N/A here since this is a real deployed site — but we keep it session-only for simplicity)
let isDark = false;
applyTheme(isDark);
themeToggle.addEventListener("click", () => {
  isDark = !isDark;
  applyTheme(isDark);
});

// ================= CHAT =================
const chatWindow = document.getElementById("chatWindow");
const questionForm = document.getElementById("questionForm");
const userQuestionInput = document.getElementById("userQuestion");
const clearChatBtn = document.getElementById("clearChatBtn");

function timeNow() {
  return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function appendUserMessage(text) {
  const msg = document.createElement("div");
  msg.classList.add("message", "user");
  msg.innerHTML = `
    <div class="avatar user-avatar">You</div>
    <div class="bubble-wrap">
      <div class="bubble"></div>
      <div class="meta-row"><span>${timeNow()}</span></div>
    </div>
  `;
  msg.querySelector(".bubble").textContent = text;
  chatWindow.appendChild(msg);
  chatWindow.scrollTop = chatWindow.scrollHeight;
}

function appendBotMessage(text, { source, confidence, isError } = {}) {
  const msg = document.createElement("div");
  msg.classList.add("message", "bot");
  if (isError) msg.classList.add("error");

  let badge = "";
  if (source === "database") {
    badge = `<span class="source-badge db">📚 Knowledge Base · ${confidence}%</span>`;
  } else if (source === "ai") {
    badge = `<span class="source-badge ai">✦ AI Generated</span>`;
  }

  msg.innerHTML = `
    <div class="avatar bot-avatar">✦</div>
    <div class="bubble-wrap">
      <div class="bubble"></div>
      <div class="meta-row">${badge}<span>${timeNow()}</span></div>
    </div>
  `;
  msg.querySelector(".bubble").textContent = text;
  chatWindow.appendChild(msg);
  chatWindow.scrollTop = chatWindow.scrollHeight;
}

function showTyping() {
  const msg = document.createElement("div");
  msg.classList.add("message", "bot");
  msg.id = "typingIndicator";
  msg.innerHTML = `
    <div class="avatar bot-avatar">✦</div>
    <div class="bubble-wrap">
      <div class="bubble typing-dots"><span></span><span></span><span></span></div>
    </div>
  `;
  chatWindow.appendChild(msg);
  chatWindow.scrollTop = chatWindow.scrollHeight;
}

function removeTyping() {
  const el = document.getElementById("typingIndicator");
  if (el) el.remove();
}

questionForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  const question = userQuestionInput.value.trim();
  if (!question) return;

  appendUserMessage(question);
  userQuestionInput.value = "";
  showTyping();

  try {
    const res = await fetch(`${API_BASE}/get_answer.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ query: question })
    });

    const data = await res.json();
    removeTyping();

    if (data.success) {
      appendBotMessage(data.answer, { source: data.source, confidence: data.confidence });
    } else {
      appendBotMessage(data.message || "Sorry, I couldn't find an answer.", { isError: true });
    }
  } catch (err) {
    removeTyping();
    appendBotMessage("⚠️ Could not connect to the server. Make sure PHP/MySQL is running.", { isError: true });
    console.error(err);
  }
});

clearChatBtn.addEventListener("click", () => {
  chatWindow.innerHTML = `
    <div class="message bot">
      <div class="avatar bot-avatar">✦</div>
      <div class="bubble-wrap">
        <div class="bubble">👋 Hi! I'm your AI-powered FAQ assistant. Ask me anything.</div>
      </div>
    </div>
  `;
});

// ================= ADMIN: ADD FAQ =================
const addFaqForm = document.getElementById("addFaqForm");
const addFaqMsg = document.getElementById("addFaqMsg");

addFaqForm.addEventListener("submit", async (e) => {
  e.preventDefault();

  const payload = {
    question: document.getElementById("faqQuestion").value.trim(),
    answer: document.getElementById("faqAnswer").value.trim(),
    keywords: document.getElementById("faqKeywords").value.trim(),
    category: document.getElementById("faqCategory").value.trim() || "General"
  };

  try {
    const res = await fetch(`${API_BASE}/add_faq.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    const data = await res.json();
    addFaqMsg.textContent = data.message;
    addFaqMsg.className = "form-msg " + (data.success ? "success" : "error");

    if (data.success) {
      addFaqForm.reset();
      document.getElementById("faqCategory").value = "General";
      loadAllFaqs();
    }
  } catch (err) {
    addFaqMsg.textContent = "⚠️ Could not connect to the server.";
    addFaqMsg.className = "form-msg error";
    console.error(err);
  }
});

// ================= ADMIN: LOAD FAQs =================
const faqListEl = document.getElementById("faqList");
const faqCountEl = document.getElementById("faqCount");

async function loadAllFaqs() {
  faqListEl.innerHTML = `<p class="loading-text">Loading FAQs...</p>`;

  try {
    const res = await fetch(`${API_BASE}/get_all_faqs.php`);
    const data = await res.json();

    if (!data.success || data.faqs.length === 0) {
      faqListEl.innerHTML = `<p class="loading-text">No FAQs found. Add one!</p>`;
      faqCountEl.textContent = "0";
      return;
    }

    faqCountEl.textContent = data.faqs.length;
    faqListEl.innerHTML = "";

    data.faqs.forEach(faq => {
      const item = document.createElement("div");
      item.classList.add("faq-item");
      item.innerHTML = `
        <button class="delete-btn" data-id="${faq.id}">Delete</button>
        <h4></h4>
        <p></p>
        <div class="tags">
          <span class="tag category"></span>
          <span class="tag ${faq.source === 'ai' ? 'ai' : 'manual'}">${faq.source === 'ai' ? '✦ AI Learned' : '✓ Manual'}</span>
        </div>
      `;
      item.querySelector("h4").textContent = faq.question;
      item.querySelector("p").textContent = faq.answer;
      item.querySelector(".tag.category").textContent = faq.category;
      faqListEl.appendChild(item);
    });

    document.querySelectorAll(".delete-btn").forEach(btn => {
      btn.addEventListener("click", () => deleteFaq(btn.dataset.id));
    });

  } catch (err) {
    faqListEl.innerHTML = `<p class="loading-text">⚠️ Could not load FAQs.</p>`;
    console.error(err);
  }
}

async function deleteFaq(id) {
  if (!confirm("Delete this FAQ?")) return;
  try {
    const res = await fetch(`${API_BASE}/delete_faq.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id })
    });
    const data = await res.json();
    if (data.success) loadAllFaqs();
    else alert(data.message);
  } catch (err) {
    alert("Could not connect to the server.");
    console.error(err);
  }
}

// ================= ANALYTICS =================
async function loadAnalytics() {
  try {
    const res = await fetch(`${API_BASE}/analytics.php`);
    const data = await res.json();
    if (!data.success) return;

    const s = data.stats;
    document.getElementById("statTotalFaqs").textContent = s.total_faqs;
    document.getElementById("statTotalQueries").textContent = s.total_queries;
    document.getElementById("statDbAnswered").textContent = s.queries_by_source.database;
    document.getElementById("statAiAnswered").textContent = s.queries_by_source.ai;

    const recentEl = document.getElementById("recentQueries");
    if (s.recent_queries.length === 0) {
      recentEl.innerHTML = `<p class="loading-text">No questions asked yet.</p>`;
      return;
    }

    recentEl.innerHTML = "";
    s.recent_queries.forEach(q => {
      const row = document.createElement("div");
      row.classList.add("recent-item");
      const badgeClass = q.answered_by === 'ai' ? 'ai' : (q.answered_by === 'database' ? 'db' : '');
      const badgeText = q.answered_by === 'ai' ? '✦ AI' : (q.answered_by === 'database' ? '📚 DB' : '❌ None');
      row.innerHTML = `
        <span class="q-text"></span>
        <span class="source-badge ${badgeClass}">${badgeText}</span>
        <span class="time"></span>
      `;
      row.querySelector(".q-text").textContent = q.user_query;
      row.querySelector(".time").textContent = new Date(q.created_at).toLocaleString();
      recentEl.appendChild(row);
    });

  } catch (err) {
    console.error(err);
  }
}
