(function(){
  const STAGES = ["Received","Reviewing","Resolved"];
  let selectedCategory = null;
  let selectedSeverity = null;
  let contactOn = false;
  let forgotUsernameCache = null;

  // ---------- Tabs ----------
  const tabButtons = document.querySelectorAll('nav.tabs button');
  const sections = { report: document.getElementById('tab-report'), track: document.getElementById('tab-track'), admin: document.getElementById('tab-admin') };
  const hero = document.getElementById('hero');
  tabButtons.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      tabButtons.forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      Object.keys(sections).forEach(k=> sections[k].style.display = (k===btn.dataset.tab)?'block':'none');
      hero.style.display = btn.dataset.tab==='report' ? 'block' : 'none';
      if(btn.dataset.tab==='admin' && document.getElementById('adminPanel').style.display==='block'){
        loadAdminReports();
      }
    });
  });

  // ---------- Category / severity selectors ----------
  document.querySelectorAll('#categoryChips .chip').forEach(chip=>{
    chip.addEventListener('click', ()=>{
      document.querySelectorAll('#categoryChips .chip').forEach(c=>c.classList.remove('selected'));
      chip.classList.add('selected');
      selectedCategory = chip.dataset.val;
    });
  });
  document.querySelectorAll('#severityRow .sev-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.querySelectorAll('#severityRow .sev-btn').forEach(b=>b.classList.remove('selected'));
      btn.classList.add('selected');
      selectedSeverity = btn.dataset.sev;
    });
  });

  const contactToggle = document.getElementById('contactToggle');
  contactToggle.addEventListener('click', ()=>{
    contactOn = !contactOn;
    contactToggle.classList.toggle('on', contactOn);
    document.getElementById('contactField').style.display = contactOn ? 'block' : 'none';
  });

  // ---------- Photo evidence ----------
  const cameraInput = document.getElementById('cameraInput');
  const galleryInput = document.getElementById('galleryInput');
  let selectedImageFile = null;

  document.getElementById('cameraBtn').addEventListener('click', ()=> cameraInput.click());
  document.getElementById('galleryBtn').addEventListener('click', ()=> galleryInput.click());

  function handlePhotoFile(file){
    const wrap = document.getElementById('imagePreviewWrap');
    const errEl = document.getElementById('formError');
    errEl.style.display='none';
    if(!file){ return; }
    if(file.size > 5*1024*1024){
      errEl.textContent = 'Photo is too large. Max size is 5MB.';
      errEl.style.display='block';
      cameraInput.value=''; galleryInput.value='';
      selectedImageFile = null;
      wrap.style.display='none';
      return;
    }
    selectedImageFile = file;
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('imagePreview').src = e.target.result;
      wrap.style.display='block';
    };
    reader.readAsDataURL(file);
  }

  cameraInput.addEventListener('change', ()=> handlePhotoFile(cameraInput.files[0]));
  galleryInput.addEventListener('change', ()=> handlePhotoFile(galleryInput.files[0]));

  document.getElementById('removeImageBtn').addEventListener('click', ()=>{
    cameraInput.value=''; galleryInput.value='';
    selectedImageFile = null;
    document.getElementById('imagePreviewWrap').style.display='none';
  });

  function fmtDate(iso){
    if(!iso) return '';
    const d = new Date(iso.replace(' ', 'T'));
    if(isNaN(d.getTime())) return iso;
    return d.toLocaleDateString(undefined,{month:'short',day:'numeric',year:'numeric'}) + ' · ' + d.toLocaleTimeString(undefined,{hour:'2-digit',minute:'2-digit'});
  }
  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }
  async function postJSON(url, body){
    const res = await fetch(url, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(body || {})
    });
    const data = await res.json().catch(()=>({}));
    if(!res.ok){ throw new Error(data.error || 'Request failed.'); }
    return data;
  }
  async function postFormData(url, formData){
    const res = await fetch(url, { method:'POST', body: formData });
    const data = await res.json().catch(()=>({}));
    if(!res.ok){ throw new Error(data.error || 'Request failed.'); }
    return data;
  }
  async function getJSON(url){
    const res = await fetch(url);
    const data = await res.json().catch(()=>({}));
    if(!res.ok){ throw new Error(data.error || 'Request failed.'); }
    return data;
  }

  // ---------- Submit report ----------
  document.getElementById('submitBtn').addEventListener('click', async ()=>{
    const errEl = document.getElementById('formError');
    const description = document.getElementById('description').value.trim();
    errEl.style.display='none';

    if(!selectedCategory){ errEl.textContent='Please select a category.'; errEl.style.display='block'; return; }
    if(!selectedSeverity){ errEl.textContent='Please select a severity level.'; errEl.style.display='block'; return; }
    if(!description){ errEl.textContent='Please describe what happened.'; errEl.style.display='block'; return; }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true; btn.textContent = 'Submitting…';

    try{
      const formData = new FormData();
      formData.append('category', selectedCategory);
      formData.append('severity', selectedSeverity);
      formData.append('location', document.getElementById('location').value.trim());
      formData.append('occurred_at', document.getElementById('occurredAt').value);
      formData.append('description', description);
      formData.append('contact_info', contactOn ? document.getElementById('contactInfo').value.trim() : '');
      if(selectedImageFile){
        formData.append('image', selectedImageFile);
      }

      const data = await postFormData('api/submit_report.php', formData);

      showTicket(data.report);
      document.getElementById('description').value='';
      document.getElementById('location').value='';
      document.getElementById('occurredAt').value='';
      document.getElementById('contactInfo').value='';
      cameraInput.value=''; galleryInput.value='';
      selectedImageFile = null;
      document.getElementById('imagePreviewWrap').style.display='none';
      document.querySelectorAll('#categoryChips .chip').forEach(c=>c.classList.remove('selected'));
      document.querySelectorAll('#severityRow .sev-btn').forEach(b=>b.classList.remove('selected'));
      selectedCategory=null; selectedSeverity=null;
    }catch(e){
      errEl.textContent = e.message || 'Something went wrong submitting your report. Please try again.';
      errEl.style.display='block';
    }
    btn.disabled=false; btn.textContent='Submit report';
  });

  function showTicket(report){
    const panel = document.getElementById('ticketPanel');
    panel.style.display='block';
    const photoHtml = report.image_path
      ? `<div class="evidence-thumb"><img src="${report.image_path}" alt="Attached photo"><div class="cap" style="color:#B9C0D4;">Photo attached</div></div>`
      : '';
    panel.innerHTML = `
      <div class="ticket-top">
        <div class="eyebrow">Report received</div>
        <h2>Your tracking code</h2>
        <div class="ticket-code">${report.code}</div>
        <p>Save this code — it's the only way to check your report's status later. It is not linked to your name or account.</p>
        ${photoHtml}
      </div>
      <div class="perf"></div>
      <div class="ticket-bottom">
        <div>Category<span>${escapeHtml(report.category)}</span></div>
        <div>Severity<span>${escapeHtml(report.severity)}</span></div>
        <div>Submitted<span>${fmtDate(report.created_at)}</span></div>
      </div>
    `;
    panel.scrollIntoView({behavior:'smooth', block:'center'});
  }

  // ---------- Track ----------
  document.getElementById('trackBtn').addEventListener('click', trackLookup);
  document.getElementById('trackCode').addEventListener('keydown', e=>{ if(e.key==='Enter') trackLookup(); });

  async function trackLookup(){
    const raw = document.getElementById('trackCode').value.trim().toUpperCase();
    const resultEl = document.getElementById('trackResult');
    if(!raw){ resultEl.innerHTML = '<div class="not-found">Enter a tracking code above.</div>'; return; }
    resultEl.innerHTML = '<div class="not-found">Looking up…</div>';
    try{
      const data = await getJSON('api/track_report.php?code=' + encodeURIComponent(raw));
      renderTrack(data.report, data.history, resultEl);
    }catch(e){
      resultEl.innerHTML = `<div class="not-found">${escapeHtml(e.message || 'No report found for that code.')}</div>`;
    }
  }

  function renderTrack(report, history, el){
    const currentIdx = STAGES.indexOf(report.status);
    let stepsHtml = STAGES.map((stage, i)=>{
      const entry = history.find(h=>h.status===stage);
      const cls = i < currentIdx ? 'done' : (i===currentIdx ? 'current' : '');
      return `<div class="step ${cls}"><div class="line"></div><div class="node"></div><div class="s-label">${stage}</div><div class="s-date">${entry?fmtDate(entry.changed_at):'—'}</div></div>`;
    }).join('');
    const photoHtml = report.image_path
      ? `<div class="evidence-thumb"><img src="${report.image_path}" alt="Attached photo"><div class="cap">Photo attached</div></div>`
      : '';
    el.innerHTML = `
      <div style="margin-top:24px;padding-top:22px;border-top:1.5px solid var(--line);">
        <div class="rowcard-code">${escapeHtml(report.code)}</div>
        <div class="rowcard-cat" style="margin-top:6px;">${escapeHtml(report.category)} · ${escapeHtml(report.severity)} severity</div>
        <div class="rowcard-desc">${escapeHtml(report.description)}</div>
        ${photoHtml}
      </div>
      <div class="stepper">${stepsHtml}</div>
    `;
  }

  // ---------- Admin auth ----------
  function showAdminAuthView(view){
    document.getElementById('adminViewLogin').style.display = view==='login' ? 'block':'none';
    document.getElementById('adminViewCreate').style.display = view==='create' ? 'block':'none';
    document.getElementById('adminViewForgot1').style.display = view==='forgot1' ? 'block':'none';
    document.getElementById('adminViewForgot2').style.display = view==='forgot2' ? 'block':'none';
    ['loginError','createError','forgotError1','forgotError2'].forEach(id=>{
      const e = document.getElementById(id);
      e.style.display='none'; e.textContent=''; e.style.color='var(--red)';
    });
  }

  document.getElementById('goCreate').addEventListener('click', ()=> showAdminAuthView('create'));
  document.getElementById('goForgot').addEventListener('click', ()=> showAdminAuthView('forgot1'));
  document.getElementById('backToLoginFromCreate').addEventListener('click', ()=> showAdminAuthView('login'));
  document.getElementById('backToLoginFromForgot1').addEventListener('click', ()=> showAdminAuthView('login'));
  document.getElementById('backToLoginFromForgot2').addEventListener('click', ()=> showAdminAuthView('login'));

  function enterDashboard(username){
    document.getElementById('adminAuth').style.display='none';
    document.getElementById('adminPanel').style.display='block';
    document.getElementById('signedInAs').textContent = 'Signed in as ' + username;
    loadAdminReports();
  }

  document.getElementById('loginBtn').addEventListener('click', async ()=>{
    const errEl = document.getElementById('loginError');
    errEl.style.display='none';
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value;
    if(!username || !password){ errEl.textContent='Enter your username and password.'; errEl.style.display='block'; return; }
    try{
      const data = await postJSON('api/admin_login.php', {username, password});
      document.getElementById('loginUsername').value='';
      document.getElementById('loginPassword').value='';
      enterDashboard(data.username);
    }catch(e){
      errEl.textContent = e.message || 'Incorrect username or password.'; errEl.style.display='block';
    }
  });

  document.getElementById('createBtn').addEventListener('click', async ()=>{
    const errEl = document.getElementById('createError');
    errEl.style.display='none';
    const username = document.getElementById('createUsername').value.trim();
    const password = document.getElementById('createPassword').value;
    const confirmPw = document.getElementById('createPasswordConfirm').value;
    const secQ = document.getElementById('createSecQ').value.trim();
    const secA = document.getElementById('createSecA').value.trim();

    if(!username || !password || !secQ || !secA){ errEl.textContent='Please fill in all fields.'; errEl.style.display='block'; return; }
    if(password.length < 4){ errEl.textContent='Password must be at least 4 characters.'; errEl.style.display='block'; return; }
    if(password !== confirmPw){ errEl.textContent='Passwords do not match.'; errEl.style.display='block'; return; }

    try{
      const data = await postJSON('api/admin_register.php', {
        username, password,
        security_question: secQ,
        security_answer: secA
      });
      document.getElementById('createUsername').value='';
      document.getElementById('createPassword').value='';
      document.getElementById('createPasswordConfirm').value='';
      document.getElementById('createSecQ').value='';
      document.getElementById('createSecA').value='';
      enterDashboard(data.username);
    }catch(e){
      errEl.textContent = e.message || 'Something went wrong creating your account.'; errEl.style.display='block';
    }
  });

  document.getElementById('forgotFindBtn').addEventListener('click', async ()=>{
    const errEl = document.getElementById('forgotError1');
    errEl.style.display='none';
    const username = document.getElementById('forgotUsername').value.trim();
    if(!username){ errEl.textContent='Enter your username.'; errEl.style.display='block'; return; }
    try{
      const data = await postJSON('api/admin_forgot_find.php', {username});
      forgotUsernameCache = username;
      document.getElementById('forgotQuestionText').textContent = data.question;
      document.getElementById('forgotUsername').value='';
      showAdminAuthView('forgot2');
    }catch(e){
      errEl.textContent = e.message || 'No account found with that username.'; errEl.style.display='block';
    }
  });

  document.getElementById('forgotResetBtn').addEventListener('click', async ()=>{
    const errEl = document.getElementById('forgotError2');
    errEl.style.display='none';
    const answer = document.getElementById('forgotAnswer').value.trim();
    const newPass = document.getElementById('forgotNewPassword').value;
    const confirmPass = document.getElementById('forgotNewPasswordConfirm').value;
    if(newPass !== confirmPass){ errEl.textContent='Passwords do not match.'; errEl.style.display='block'; return; }
    try{
      await postJSON('api/admin_forgot_reset.php', {
        username: forgotUsernameCache,
        answer,
        new_password: newPass
      });
      document.getElementById('forgotAnswer').value='';
      document.getElementById('forgotNewPassword').value='';
      document.getElementById('forgotNewPasswordConfirm').value='';
      showAdminAuthView('login');
      const loginErr = document.getElementById('loginError');
      loginErr.textContent = 'Password reset — you can log in now.';
      loginErr.style.color = 'var(--teal)';
      loginErr.style.display='block';
    }catch(e){
      errEl.textContent = e.message || 'Something went wrong. Please try again.'; errEl.style.display='block';
    }
  });

  document.getElementById('logoutBtn').addEventListener('click', async ()=>{
    try{ await postJSON('api/admin_logout.php', {}); }catch(e){}
    document.getElementById('adminPanel').style.display='none';
    document.getElementById('adminAuth').style.display='block';
    showAdminAuthView('login');
  });

  ['filterCategory','filterSeverity','filterStatus'].forEach(id=>{
    document.getElementById(id).addEventListener('change', loadAdminReports);
  });

  async function loadAdminReports(){
    const listEl = document.getElementById('reportList');
    listEl.innerHTML = '<div class="not-found">Loading reports…</div>';

    const fc = document.getElementById('filterCategory').value;
    const fs = document.getElementById('filterSeverity').value;
    const fst = document.getElementById('filterStatus').value;
    const params = new URLSearchParams({category: fc, severity: fs, status: fst});

    let reports;
    try{
      const data = await getJSON('api/admin_reports.php?' + params.toString());
      reports = data.reports;
    }catch(e){
      // session expired or not authenticated — send back to login
      document.getElementById('adminPanel').style.display='none';
      document.getElementById('adminAuth').style.display='block';
      showAdminAuthView('login');
      return;
    }

    if(reports.length===0){
      listEl.innerHTML = '<div class="empty-state"><h3>No reports yet</h3>Submitted reports will appear here.</div>';
      return;
    }

    listEl.innerHTML = reports.map(r=>`
      <div class="rowcard" data-sev="${r.severity}">
        <div class="rowcard-top">
          <div>
            <div class="rowcard-code">${escapeHtml(r.code)}</div>
            <div class="rowcard-cat">${escapeHtml(r.category)} · ${escapeHtml(r.severity)}</div>
          </div>
          <div style="display:flex;align-items:center;gap:10px;">
            <select data-code="${escapeHtml(r.code)}" class="statusSelect">
              ${STAGES.map(s=>`<option ${s===r.status?'selected':''}>${s}</option>`).join('')}
            </select>
            <button type="button" class="text-link deleteBtn" data-code="${escapeHtml(r.code)}" style="color:var(--red);">Delete</button>
          </div>
        </div>
        <div class="rowcard-desc">${escapeHtml(r.description)}</div>
        ${r.image_path ? `<a href="${r.image_path}" target="_blank" rel="noopener" class="evidence-thumb"><img src="${r.image_path}" alt="Attached photo"><div class="cap">View full photo</div></a>` : ''}
        <div class="rowcard-meta">${r.location ? escapeHtml(r.location) + ' · ' : ''}Submitted ${fmtDate(r.created_at)}${r.contact_info ? ' · Contact provided' : ''}</div>
      </div>
    `).join('');

    document.querySelectorAll('.statusSelect').forEach(sel=>{
      sel.addEventListener('change', async ()=>{
        try{
          await postJSON('api/admin_update_status.php', {code: sel.dataset.code, status: sel.value});
          loadAdminReports();
        }catch(e){}
      });
    });

    document.querySelectorAll('.deleteBtn').forEach(btn=>{
      btn.addEventListener('click', async ()=>{
        const code = btn.dataset.code;
        const confirmed = window.confirm('Delete report ' + code + '? This cannot be undone.');
        if(!confirmed) return;
        btn.textContent = 'Deleting…';
        btn.disabled = true;
        try{
          await postJSON('api/admin_delete_report.php', {code});
          loadAdminReports();
        }catch(e){
          btn.textContent = 'Delete';
          btn.disabled = false;
          alert(e.message || 'Could not delete this report.');
        }
      });
    });
  }

  // ---------- Initial state (from PHP session) ----------
  if(INITIAL_ADMIN.loggedIn){
    enterDashboard(INITIAL_ADMIN.username);
  }

  // ============ AI ASSISTANT CHATBOT (real LLM via api/chatbot.php, Gemini free tier) ============
  const SUGGESTIONS = ['How do I submit a report?', 'Is it really anonymous?', 'How do I track my report?', 'What categories are there?'];

  let chatbotLanguage = "en";
  let chatSending = false;

  // Exposed globally because index.php calls it via
  // <select onchange="setChatbotLanguage(this.value)">
  window.setChatbotLanguage = function(language){
    chatbotLanguage = language;
    // Start a fresh conversation server-side so context doesn't mix languages
    fetch('api/chatbot_reset.php', { method: 'POST' }).catch(()=>{});
    chatMessages.innerHTML = '';
    chatOpened = false;
    addChatMessage(greetingFor(chatbotLanguage), 'bot');
    addSuggestions();
    chatOpened = true;
    speakReply(greetingFor(chatbotLanguage));
  };

  function greetingFor(lang){
    if (lang === 'ta') return "வணக்கம்! நான் SafeLine Assistant. இந்த site பற்றி எந்த கேள்வியும் கேளுங்கள்.";
    if (lang === 'tl') return "Vanakkam! Naan SafeLine Assistant. Intha site pathi edhachum kelvi kelunga.";
    return "Hi! I'm the SafeLine assistant. Ask me anything about this site.";
  }

  const chatToggleBtn = document.getElementById('chatToggleBtn');
  const chatPanel = document.getElementById('chatPanel');
  const chatMessages = document.getElementById('chatMessages');
  const chatInput = document.getElementById('chatInput');
  let chatOpened = false;

  function addChatMessage(text, sender){
    const div = document.createElement('div');
    div.className = 'chat-msg ' + sender;
    div.textContent = text;
    chatMessages.appendChild(div);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return div;
  }

  function addSuggestions(){
    const wrap = document.createElement('div');
    wrap.className = 'chat-suggestions';
    SUGGESTIONS.forEach(s=>{
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = s;
      btn.addEventListener('click', ()=> sendChatMessage(s));
      wrap.appendChild(btn);
    });
    chatMessages.appendChild(wrap);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  async function sendChatMessage(text){
    const message = (text !== undefined ? text : chatInput.value).trim();
    if(!message || chatSending) return;
    addChatMessage(message, 'user');
    chatInput.value = '';
    chatSending = true;
    const typing = addChatMessage('…', 'bot');

    try {
      const res = await fetch('api/chatbot.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message, language: chatbotLanguage })
      });
      const data = await res.json();
      typing.remove();
      if (!res.ok || data.error) {
        const msg = (data.error || 'Something went wrong. Please try again.') + (data.detail ? '\n\n[Debug detail: ' + data.detail + ']' : '');
        addChatMessage(msg, 'bot');
      } else {
        addChatMessage(data.reply, 'bot');
        speakReply(data.reply);
      }
    } catch (err) {
      typing.remove();
      addChatMessage('Could not reach the assistant. Check your connection and try again.', 'bot');
    } finally {
      chatSending = false;
    }
  }

  // ============ VOICE INPUT (recording -> speech-to-text) + VOICE OUTPUT (text-to-speech) ============
  // Browser Web Speech API — no server changes needed. Works in Chrome/Edge; if a
  // browser doesn't support it, the mic button explains that instead of failing silently.
  const SpeechRecognitionCtor = window.SpeechRecognition || window.webkitSpeechRecognition;
  const chatMicBtn = document.getElementById('chatMicBtn');
  const chatMicStatus = document.getElementById('chatMicStatus');
  const chatVoiceToggleBtn = document.getElementById('chatVoiceToggleBtn');

  let voiceReplyEnabled = true;
  let recognition = null;
  let isRecording = false;

  // en/tl (Tanglish, spoken with English letters) both use the en-IN recognizer,
  // which is comfortable with Tamil-accented English and code-switching; ta uses ta-IN.
  function recognitionLangFor(lang){
    if (lang === 'ta') return 'ta-IN';
    return 'en-IN';
  }

  function micStatusText(lang){
    if (lang === 'ta') return 'கேட்கிறேன்… பேசுங்கள்';
    if (lang === 'tl') return 'Kekkuren… pesunga';
    return 'Listening… speak now';
  }

  if (SpeechRecognitionCtor) {
    recognition = new SpeechRecognitionCtor();
    recognition.continuous = false;
    recognition.interimResults = true;

    recognition.addEventListener('result', (e) => {
      let interim = '', final = '';
      for (let i = 0; i < e.results.length; i++) {
        const chunk = e.results[i][0].transcript;
        if (e.results[i].isFinal) final += chunk; else interim += chunk;
      }
      chatInput.value = final || interim;
      if (final.trim()) {
        sendChatMessage(final.trim());
      }
    });

    recognition.addEventListener('end', () => {
      isRecording = false;
      chatMicBtn.classList.remove('recording');
      chatMicStatus.style.display = 'none';
    });

    recognition.addEventListener('error', (e) => {
      isRecording = false;
      chatMicBtn.classList.remove('recording');
      chatMicStatus.style.display = 'none';
      if (e.error === 'not-allowed' || e.error === 'service-not-allowed') {
        addChatMessage('Mic access was blocked. Allow microphone permission in the browser to use voice input.', 'bot');
      } else if (e.error !== 'no-speech' && e.error !== 'aborted') {
        addChatMessage('Could not hear that clearly. Please try recording again.', 'bot');
      }
    });

    chatMicBtn.addEventListener('click', () => {
      if (isRecording) {
        recognition.stop();
        return;
      }
      recognition.lang = recognitionLangFor(chatbotLanguage);
      try {
        recognition.start();
        isRecording = true;
        chatMicBtn.classList.add('recording');
        chatMicStatus.textContent = micStatusText(chatbotLanguage);
        chatMicStatus.innerHTML = '<span class="dot"></span>' + micStatusText(chatbotLanguage);
        chatMicStatus.style.display = 'flex';
      } catch (err) {
        isRecording = false;
      }
    });
  } else {
    chatMicBtn.disabled = true;
    chatMicBtn.title = 'Voice recording is not supported in this browser — try Chrome or Edge.';
    chatMicBtn.style.opacity = '0.4';
  }

  // Pick the closest available system voice for the reply language, so Tamil
  // replies are spoken with a Tamil voice when the device/browser has one installed.
  function pickVoice(lang){
    const voices = window.speechSynthesis ? window.speechSynthesis.getVoices() : [];
    if (!voices.length) return null;
    const want = lang === 'ta' ? 'ta' : 'en';
    return voices.find(v => v.lang && v.lang.toLowerCase().startsWith(want)) || null;
  }

  function speakReply(text){
    if (!voiceReplyEnabled || !window.speechSynthesis || !text) return;
    window.speechSynthesis.cancel(); // don't overlap with a previous reply
    const utter = new SpeechSynthesisUtterance(text);
    utter.lang = chatbotLanguage === 'ta' ? 'ta-IN' : 'en-IN';
    const voice = pickVoice(chatbotLanguage);
    if (voice) utter.voice = voice;
    utter.rate = 0.98;
    window.speechSynthesis.speak(utter);
  }

  if (window.speechSynthesis) {
    // Voice list loads asynchronously in some browsers.
    window.speechSynthesis.onvoiceschanged = () => {};
  }

  chatVoiceToggleBtn.addEventListener('click', () => {
    voiceReplyEnabled = !voiceReplyEnabled;
    chatVoiceToggleBtn.textContent = voiceReplyEnabled ? '🔊' : '🔇';
    chatVoiceToggleBtn.title = voiceReplyEnabled ? 'Spoken replies: on' : 'Spoken replies: off';
    chatVoiceToggleBtn.classList.toggle('muted', !voiceReplyEnabled);
    if (!voiceReplyEnabled && window.speechSynthesis) window.speechSynthesis.cancel();
  });

  chatToggleBtn.addEventListener('click', ()=>{
    const isOpen = chatPanel.style.display === 'flex';
    chatPanel.style.display = isOpen ? 'none' : 'flex';
    if(!isOpen && !chatOpened){
      chatOpened = true;
      addChatMessage(greetingFor(chatbotLanguage), 'bot');
      addSuggestions();
      speakReply(greetingFor(chatbotLanguage));
    }
  });
  document.getElementById('chatCloseBtn').addEventListener('click', ()=>{
    chatPanel.style.display = 'none';
  });
  document.getElementById('chatSendBtn').addEventListener('click', ()=> sendChatMessage());
  chatInput.addEventListener('keydown', e=>{ if(e.key==='Enter') sendChatMessage(); });
})();
