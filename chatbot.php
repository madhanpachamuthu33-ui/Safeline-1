<?php
/**
 * api/chatbot.php
 *
 * Powers the "SafeLine Assistant" widget with a real LLM so it can answer
 * any phrasing of a question about the site.
 *
 * Uses Google's Gemini API — chosen because Google AI Studio gives every
 * developer a free API key with NO credit card required (unlike most
 * providers, whose "free tier" needs billing set up first).
 *
 * Get a free key at: https://aistudio.google.com/apikey
 *
 * Chat history is kept only in the PHP session (never written to the
 * `reports` table or any other DB table) so it stays consistent with
 * SafeLine's "identity never stored" promise, and disappears when the
 * browser session ends.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// ---- Configuration --------------------------------------------------------
// Set this as a real environment variable on your server — never hardcode it
// here or commit it to git:
//   export GEMINI_API_KEY="AIza..."
$GEMINI_API_KEY = getenv('GEMINI_API_KEY') ?: '';
$GEMINI_MODEL   = 'gemini-3.6-flash'; // fast + on Gemini's free tier

if ($GEMINI_API_KEY === '') {
    json_response([
        'error' => 'Chat assistant is not configured. Set the GEMINI_API_KEY environment variable on the server (free key: https://aistudio.google.com/apikey).'
    ], 500);
}

// ---- Input ------------------------------------------------------------
$body = read_json_body();
$userMessage = trim((string)($body['message'] ?? ''));
$language    = $body['language'] ?? 'en'; // 'en' | 'ta' | 'tl' (Tanglish) — from the dropdown in index.php

if ($userMessage === '') {
    json_response(['error' => 'Message cannot be empty.'], 400);
}
if (mb_strlen($userMessage) > 1000) {
    json_response(['error' => 'Message is too long (max 1000 characters).'], 400);
}

// ---- Rolling chat history in session (not persisted to DB) ----------------
if (!isset($_SESSION['chatbot_history']) || !is_array($_SESSION['chatbot_history'])) {
    $_SESSION['chatbot_history'] = [];
}

// Gemini's "contents" format uses role: 'user' | 'model'
$_SESSION['chatbot_history'][] = ['role' => 'user', 'text' => $userMessage];

// Keep only the last 10 turns (20 messages) to bound token usage
$history = array_slice($_SESSION['chatbot_history'], -20);

// ---- System prompt ------------------------------------------------------
$languageInstruction = [
    'en' => 'Respond in clear, plain English.',
    'ta' => 'Respond in Tamil (தமிழ்).',
    'tl' => 'Respond in Tanglish (Tamil words in Latin/English script, natural spoken style, like how students actually chat).',
][$language] ?? 'Respond in clear, plain English.';

$systemPrompt = <<<SYS
You are the SafeLine Assistant, a help widget on the SafeLine anonymous campus
feedback and safety reporting website. You are NOT a crisis line and you never
ask for or store any identifying information.

What SafeLine does, in full detail:
- Report tab: students submit an anonymous report choosing a category
  (Safety Incident, Harassment / Bullying, Drug / Substance Use,
  Facility / Maintenance, Academic Integrity, Other), a severity
  (Low/Medium/High/Critical), an optional location, an optional
  date/time it happened, a required description, and an optional photo
  (max 5MB, JPG/PNG/WEBP/GIF, taken via camera or chosen from gallery).
  There is also an "include contact info" toggle (off by default) if the
  student wants reviewers to be able to reach them — everything else about
  the report stays anonymous either way. On submit they get a tracking
  code like SL-7F2K9. No login or account is ever required to submit a
  report.
- Track tab: anyone can look up a report's status timeline (Received ->
  Reviewing -> Resolved) by entering that tracking code — no login needed
  here either.
- Admin tab: campus staff create an account (username, password, security
  question + answer), log in, view all submitted reports, filter by
  category/severity/status, update a report's status, or delete a report.
  "Forgot password" is handled via the security question, not email.

Your job:
- Answer ANY question the user asks about how this specific site works —
  submitting, tracking, categories, severity levels, photos, anonymity,
  contact info, the admin flow, account creation, password reset, etc. —
  in your own words, even if it's phrased in an unusual or indirect way.
  Do not require exact keyword matches; understand intent.
- Reassure users about anonymity whenever it's relevant: no login or
  account is required to submit or track a report, contact info is
  opt-in only, and the tracking code is the only link back to their report.
- If someone describes an emergency or immediate danger, tell them clearly
  that this form is not monitored in real time and to contact campus
  security or local emergency services directly right away.
- Keep answers short — a few sentences — since this is a small chat widget.
  This may be a stressful moment for the person you're talking to, so stay
  warm and non-judgmental.
- Never ask for the user's name or any other identifying detail.
- You cannot look up or discuss the contents of any specific report — you
  only explain how the site works.
- If asked something completely unrelated to this site (e.g. general
  trivia, coding help, other topics), gently steer back: say you're only
  able to help with questions about SafeLine.

{$languageInstruction}
SYS;

// ---- Build Gemini request -------------------------------------------------
$contents = array_map(function ($turn) {
    return [
        'role'  => $turn['role'] === 'assistant' ? 'model' : 'user',
        'parts' => [['text' => $turn['text']]],
    ];
}, $history);

$payload = [
    'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
    'contents'           => $contents,
    'generationConfig'   => ['maxOutputTokens' => 500],
];

$url = "https://generativelanguage.googleapis.com/v1beta/models/{$GEMINI_MODEL}:generateContent?key=" . urlencode($GEMINI_API_KEY);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    json_response(['error' => 'Could not reach the assistant. ' . $curlErr], 502);
}

$decoded = json_decode($response, true);
$assistantText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

if ($httpCode !== 200 || $assistantText === null) {
    // Try to surface the most useful detail we can find
    $detail = $decoded['error']['message'] ?? null;
    if (!$detail && isset($decoded['promptFeedback']['blockReason'])) {
        $detail = 'Blocked by safety filter: ' . $decoded['promptFeedback']['blockReason'];
    }
    if (!$detail && isset($decoded['candidates'][0]['finishReason'])) {
        $detail = 'Model finish reason: ' . $decoded['candidates'][0]['finishReason'];
    }
    if (!$detail) {
        $detail = 'HTTP ' . $httpCode . ' — raw response: ' . substr($response, 0, 300);
    }
    error_log('SafeLine chatbot Gemini error: HTTP ' . $httpCode . ' — ' . $response);
    json_response([
        'error'  => 'Assistant service returned an error.',
        'detail' => $detail,
    ], 502);
}

// Save assistant reply into session history too
$_SESSION['chatbot_history'][] = ['role' => 'assistant', 'text' => $assistantText];
$_SESSION['chatbot_history'] = array_slice($_SESSION['chatbot_history'], -20);

json_response(['reply' => trim($assistantText)]);
