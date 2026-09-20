<?php
session_start();
$adminLoggedIn = !empty($_SESSION['admin_id']);
$adminUsername = $_SESSION['admin_username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SafeLine — Anonymous Campus Feedback &amp; Safety Reporting</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topbar">
  <div class="topbar-inner">
    <div class="brand">
      <div class="brand-mark"></div>
      <div>
        <div class="brand-word">SAFELINE</div>
        <div class="brand-sub">Campus feedback &amp; safety reporting</div>
      </div>
    </div>
    <nav class="tabs">
      <button data-tab="report" class="active">Report</button>
      <button data-tab="track">Track</button>
      <button data-tab="admin">Admin</button>
    </nav>
  </div>
</div>

<div class="hero" id="hero">
  <div class="hero-inner">
    <div class="eyebrow">No login · No name · No trace</div>
    <h1>Say something.<br>Stay anonymous.</h1>
    <p>Report a safety concern, harassment, or a campus issue in a few minutes. No account, no name attached — just a tracking code so you can follow up.</p>
    <div class="hero-badges">
      <div class="badge"><span class="dot"></span>Identity never stored</div>
      <div class="badge"><span class="dot"></span>Trackable by code only</div>
    </div>
  </div>
</div>

<main>

  <!-- ================= REPORT TAB ================= -->
  <section id="tab-report">
    <div class="panel">
      <h2>New report</h2>
      <div class="sub">Fill in what you can. Every field except the description is optional.</div>

      <label>Category</label>
      <div class="chip-row" id="categoryChips">
        <button type="button" class="chip" data-val="Safety Incident">Safety Incident</button>
        <button type="button" class="chip" data-val="Harassment / Bullying">Harassment / Bullying</button>
        <button type="button" class="chip" data-val="Drug / Substance Use">Drug / Substance Use</button>
        <button type="button" class="chip" data-val="Facility / Maintenance">Facility / Maintenance</button>
        <button type="button" class="chip" data-val="Academic Integrity">Academic Integrity</button>
        <button type="button" class="chip" data-val="Other">Other</button>
      </div>

      <label>Severity</label>
      <div class="severity-row" id="severityRow">
        <button type="button" class="sev-btn" data-sev="Low"><div class="bar"></div>Low</button>
        <button type="button" class="sev-btn" data-sev="Medium"><div class="bar"></div>Medium</button>
        <button type="button" class="sev-btn" data-sev="High"><div class="bar"></div>High</button>
        <button type="button" class="sev-btn" data-sev="Critical"><div class="bar"></div>Critical</button>
      </div>

      <label>Location on campus</label>
      <input type="text" id="location" placeholder="e.g. Block C, Hostel 2, Parking lot">

      <label>When did this happen? <span style="text-transform:none;font-weight:400;">(optional)</span></label>
      <input type="datetime-local" id="occurredAt">

      <label>What happened?</label>
      <textarea id="description" placeholder="Describe the incident or issue. Include as much detail as feels safe to share."></textarea>

      <label>Photo evidence <span style="text-transform:none;font-weight:400;">(optional)</span></label>
      <div class="chip-row">
        <button type="button" class="chip photo-btn" id="cameraBtn">📷 Take Photo</button>
        <button type="button" class="chip photo-btn" id="galleryBtn">🖼️ Choose from Gallery</button>
      </div>
      <input type="file" id="cameraInput" accept="image/*" capture="environment" style="display:none;">
      <input type="file" id="galleryInput" accept="image/png, image/jpeg, image/webp, image/gif" style="display:none;">
      <div class="hint">Take a photo now or pick one from your gallery. Max 5MB. This is optional — you can submit without one.</div>
      <div id="imagePreviewWrap" style="display:none;margin-top:12px;">
        <img id="imagePreview" alt="Selected photo preview" style="max-width:100%;border-radius:9px;border:1.5px solid var(--line);">
        <button type="button" class="text-link" id="removeImageBtn" style="display:block;margin-top:8px;">Remove photo</button>
      </div>

      <div class="toggle-row">
        <div>
          <div class="t-label">Include contact info</div>
          <div class="t-sub">Off by default — report stays fully anonymous</div>
        </div>
        <button type="button" class="switch" id="contactToggle"></button>
      </div>
      <div id="contactField" style="display:none;">
        <label>Email or phone (only visible to reviewers)</label>
        <input type="text" id="contactInfo" placeholder="you@campus.edu">
      </div>

      <div id="formError" class="error-msg" style="display:none;"></div>
      <button type="button" class="submit-btn" id="submitBtn">Submit report</button>
    </div>

    <div class="ticket" id="ticketPanel" style="display:none;"></div>

    <div class="footer-note">If you or someone else is in immediate danger, contact campus security or local emergency services directly — this form is not monitored in real time.</div>
  </section>

  <!-- ================= TRACK TAB ================= -->
  <section id="tab-track" style="display:none;">
    <div class="panel">
      <h2>Track your report</h2>
      <div class="sub">Enter the tracking code you received when you submitted your report.</div>
      <div class="lookup-row">
        <input type="text" id="trackCode" placeholder="e.g. SL-7F2K9" class="mono">
        <button type="button" id="trackBtn">Look up</button>
      </div>
      <div id="trackResult"></div>
    </div>
  </section>

  <!-- ================= ADMIN TAB ================= -->
  <section id="tab-admin" style="display:none;">
    <div id="adminAuth" class="gate">

      <!-- LOGIN -->
      <div id="adminViewLogin">
        <div class="gate-header">
          <div class="eyebrow">Reviewer access</div>
          <h2>Admin login</h2>
          <div class="sub">Sign in to review and update submitted reports.</div>
        </div>
        <label>Username</label>
        <input type="text" id="loginUsername" placeholder="Username">
        <label>Password</label>
        <input type="password" id="loginPassword" placeholder="Password">
        <div id="loginError" class="error-msg" style="display:none;"></div>
        <button type="button" class="submit-btn" id="loginBtn" style="margin-top:14px;">Log in</button>
        <div class="link-row">
          <button type="button" class="text-link" id="goCreate">Create an ID</button>
          <span class="sep">·</span>
          <button type="button" class="text-link" id="goForgot">Forgot password?</button>
        </div>
      </div>

      <!-- CREATE ID -->
      <div id="adminViewCreate" style="display:none;">
        <div class="gate-header">
          <div class="eyebrow">Reviewer access</div>
          <h2>Create an ID</h2>
          <div class="sub">Set up an admin account to review reports.</div>
        </div>
        <label>Username</label>
        <input type="text" id="createUsername" placeholder="Choose a username">
        <label>Password</label>
        <input type="password" id="createPassword" placeholder="Choose a password (min. 4 characters)">
        <label>Confirm password</label>
        <input type="password" id="createPasswordConfirm" placeholder="Re-enter password">
        <label>Security question</label>
        <input type="text" id="createSecQ" placeholder="e.g. What was your first pet's name?">
        <label>Answer</label>
        <input type="text" id="createSecA" placeholder="Answer — used to reset your password">
        <div id="createError" class="error-msg" style="display:none;"></div>
        <button type="button" class="submit-btn" id="createBtn" style="margin-top:14px;">Create account</button>
        <div class="link-row">
          <button type="button" class="text-link" id="backToLoginFromCreate">Back to login</button>
        </div>
      </div>

      <!-- FORGOT PASSWORD — STEP 1 -->
      <div id="adminViewForgot1" style="display:none;">
        <div class="gate-header">
          <div class="eyebrow">Reviewer access</div>
          <h2>Forgot password</h2>
          <div class="sub">Enter your username to find your security question.</div>
        </div>
        <label>Username</label>
        <input type="text" id="forgotUsername" placeholder="Username">
        <div id="forgotError1" class="error-msg" style="display:none;"></div>
        <button type="button" class="submit-btn" id="forgotFindBtn" style="margin-top:14px;">Continue</button>
        <div class="link-row">
          <button type="button" class="text-link" id="backToLoginFromForgot1">Back to login</button>
        </div>
      </div>

      <!-- FORGOT PASSWORD — STEP 2 -->
      <div id="adminViewForgot2" style="display:none;">
        <div class="gate-header">
          <div class="eyebrow">Reviewer access</div>
          <h2>Answer security question</h2>
          <div class="sub" id="forgotQuestionText"></div>
        </div>
        <label>Answer</label>
        <input type="text" id="forgotAnswer" placeholder="Your answer">
        <label>New password</label>
        <input type="password" id="forgotNewPassword" placeholder="New password (min. 4 characters)">
        <label>Confirm new password</label>
        <input type="password" id="forgotNewPasswordConfirm" placeholder="Confirm new password">
        <div id="forgotError2" class="error-msg" style="display:none;"></div>
        <button type="button" class="submit-btn" id="forgotResetBtn" style="margin-top:14px;">Reset password</button>
        <div class="link-row">
          <button type="button" class="text-link" id="backToLoginFromForgot2">Back to login</button>
        </div>
      </div>

    </div>

    <div id="adminPanel" style="display:none;">
      <div class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
          <h2 style="margin-bottom:0;">All reports</h2>
          <div style="display:flex;align-items:center;gap:12px;">
            <span id="signedInAs" style="font-size:12.5px;color:var(--ink-soft);"></span>
            <button type="button" class="text-link" id="logoutBtn">Log out</button>
          </div>
        </div>
        <div class="sub" style="margin-top:6px;">Update status as reports move through review.</div>
        <div class="filters">
          <select id="filterCategory">
            <option value="">All categories</option>
            <option>Safety Incident</option>
            <option>Harassment / Bullying</option>
            <option>Drug / Substance Use</option>
            <option>Facility / Maintenance</option>
            <option>Academic Integrity</option>
            <option>Other</option>
          </select>
          <select id="filterSeverity">
            <option value="">All severities</option>
            <option>Low</option><option>Medium</option><option>High</option><option>Critical</option>
          </select>
          <select id="filterStatus">
            <option value="">All statuses</option>
            <option>Received</option><option>Reviewing</option><option>Resolved</option>
          </select>
        </div>
        <div id="reportList"></div>
      </div>
    </div>
  </section>

</main>

<!-- ================= AI ASSISTANT CHATBOT ================= -->
<button type="button" id="chatToggleBtn" aria-label="Open SafeLine Assistant">💬</button>

<div id="chatPanel" style="display:none;">
  <div id="chatHeader">
    <div>
      <select id="chatbotLanguage" onchange="setChatbotLanguage(this.value)">
    <option value="en">English</option>
    <option value="ta">தமிழ்</option>
    <option value="tl">Tanglish</option>
</select>
      <div id="chatTitle">SafeLine Assistant</div>
      <div id="chatSubtitle">Ask me anything about this site</div>
    </div>
    <button type="button" id="chatVoiceToggleBtn" aria-label="Toggle spoken replies" title="Spoken replies: on">🔊</button>
    <button type="button" id="chatCloseBtn" aria-label="Close chat">✕</button>
  </div>
  <div id="chatMessages"></div>
  <div id="chatMicStatus" style="display:none;"></div>
  <div id="chatInputRow">
    <input type="text" id="chatInput" placeholder="Ask a question…" autocomplete="off">
    <button type="button" id="chatMicBtn" aria-label="Record voice message" title="Record voice">🎤</button>
    <button type="button" id="chatSendBtn">Send</button>
  </div>
</div>

<script>
  const INITIAL_ADMIN = {
    loggedIn: <?php echo $adminLoggedIn ? 'true' : 'false'; ?>,
    username: <?php echo json_encode($adminUsername); ?>
  };
</script>
<script src="main.js"></script>
</body>
</html>
