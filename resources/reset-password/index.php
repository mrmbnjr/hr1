<?php
$pageTitle = 'Reset your password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> | Ram Yum HRMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Luckiest+Guy&family=Baloo+2:wght@600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{color-scheme:light;--maroon:#701824;--maroon-dark:#50101a;--gold:#d9a441;--gold-light:#f5e5be;--cream:#fbf7ef;--ink:#2b1a1d;--ink-soft:#715e60;--card-bg:#fffefa;--border:#e8ddcc;--field-bg:#fff;--radius:16px;--shadow:0 22px 60px rgba(68,20,25,.12);--err:#b3261e;--ok:#28774b}
[data-theme="dark"]{color-scheme:dark;--cream:#191416;--ink:#f6ece7;--ink-soft:#c6b4b5;--card-bg:#261d20;--border:#4a393d;--field-bg:#20191b;--gold-light:#48391f;--shadow:0 22px 60px rgba(0,0,0,.42)}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;font-family:'Nunito',sans-serif;background:var(--cream);color:var(--ink);display:grid;grid-template-columns:minmax(310px, .78fr) minmax(0, 1.22fr);transition:background .2s,color .2s}
.brand{position:relative;overflow:hidden;background:var(--maroon);color:#fff;padding:clamp(28px,5vw,68px);display:flex;flex-direction:column;justify-content:space-between;border-right:6px solid var(--gold)}
.brand:after{content:"";position:absolute;width:360px;height:360px;right:-150px;bottom:10%;border:1px solid rgba(245,229,190,.22);border-radius:50%;box-shadow:0 0 0 28px rgba(245,229,190,.04),0 0 0 58px rgba(245,229,190,.035),0 0 0 92px rgba(245,229,190,.025);pointer-events:none}
.brand h1{font-family:'Baloo 2',sans-serif;font-size:clamp(2rem,3vw,3rem);line-height:1.08;margin:0 0 16px;max-width:12ch}
.brand p{color:var(--ink-soft); max-width:34ch;line-height:1.7;margin:0;font-size:1.02rem}
.brand-lockup{display:flex;align-items:center;gap:14px}
.brand-lockup img{display:block;width:72px;height:72px;object-fit:contain;flex-shrink:0}
.sidebar-header-text{display:flex;flex-direction:column}
.brand-copy{flex:1;display:flex;flex-direction:column;justify-content:center}
.brand-title{margin:0;font-family:'Luckiest Guy',cursive;font-size:20px;line-height:1.05;color:#e0362c;text-shadow:-1.2px -1.2px 0 #241311,1.2px -1.2px 0 #241311,-1.2px 1.2px 0 #241311,1.2px 1.2px 0 #241311,0 2px 0 #241311;-webkit-text-stroke:1.3px #241311;paint-order:stroke fill;letter-spacing:.5px}
.brand-title span{display:inline}
.sidebar-header-text p{margin:4px 0 0;font-family:'Baloo 2',sans-serif;font-size:12px;font-weight:600;color:#fff}
.main{display:flex;align-items:center;justify-content:center;padding:52px 34px}
.card{width:100%;max-width:570px;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);padding:clamp(24px,3vw,38px);animation:card-in .45s ease-out both}
@keyframes card-in{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.card h2{font-family:'Baloo 2',sans-serif;margin:0 0 2px;font-size:1.8rem;line-height:1.2}
.sub{color:var(--ink-soft);margin:0 0 24px;font-size:.98rem}
.tabs{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px;margin-bottom:24px}
.tab{min-height:82px;border:1.5px solid var(--border);background:var(--field-bg);color:var(--ink);border-radius:11px;padding:11px 6px;cursor:pointer;font:700 .84rem 'Nunito',sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px;transition:border-color .16s,background .16s,transform .16s}
.tab:hover{border-color:var(--gold);transform:translateY(-1px)}
.tab i{font-size:1.15rem;color:var(--maroon)}
[data-theme="dark"] .tab i{color:var(--gold)}
.tab[aria-selected="true"]{border-color:var(--maroon);background:var(--gold-light);color:var(--ink)}
[data-theme="dark"] .tab[aria-selected="true"]{border-color:var(--gold)}
.tab:focus-visible,.btn:focus-visible,input:focus-visible,textarea:focus-visible,a:focus-visible,.theme-toggle:focus-visible{outline:3px solid var(--gold);outline-offset:3px}
.panel{display:none}.panel.active{display:block}
label{display:block;font-weight:800;font-size:.9rem;margin:15px 0 7px}
input,textarea{width:100%;min-height:46px;padding:11px 13px;border:1.5px solid var(--border);border-radius:9px;background:var(--field-bg);color:var(--ink);font:inherit;transition:border-color .16s,box-shadow .16s}
input::placeholder,textarea::placeholder{color:var(--ink-soft);opacity:.8}
input:focus,textarea:focus{outline:none;border-color:var(--maroon);box-shadow:0 0 0 3px rgba(112,24,36,.12)}
[data-theme="dark"] input:focus,[data-theme="dark"] textarea:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(217,164,65,.15)}
textarea{min-height:96px;resize:vertical}
.pw{position:relative}.pw input{padding-right:48px}.pw button{position:absolute;right:7px;top:50%;transform:translateY(-50%);width:36px;height:36px;background:none;border:0;border-radius:7px;color:var(--ink-soft);cursor:pointer}
.btn{width:100%;min-height:48px;margin-top:20px;padding:12px;border:0;border-radius:9px;background:var(--maroon);color:#fff;font:800 1rem 'Nunito',sans-serif;cursor:pointer;transition:background .16s,transform .16s}
.btn:hover{background:var(--maroon-dark);transform:translateY(-1px)}.btn:disabled{opacity:.6;cursor:wait;transform:none}
.btn.ghost{background:transparent;color:var(--maroon);border:1.5px solid var(--maroon);margin-top:10px}
[data-theme="dark"] .btn.ghost{color:var(--gold);border-color:var(--gold)}
.hint{font-size:.84rem;color:var(--ink-soft);margin:8px 0 0;line-height:1.45}
.msg{display:none;margin-top:16px;padding:12px 14px;border-radius:9px;font-size:.92rem;line-height:1.45}
.msg.show{display:block}.msg.err{background:rgba(179,38,30,.1);color:var(--err)}.msg.ok{background:rgba(40,119,75,.12);color:var(--ok)}
.code{letter-spacing:.5em;text-align:center;font:700 1.4rem 'Baloo 2',sans-serif}
.back{display:block;text-align:center;margin-top:22px;color:var(--maroon);font-weight:800;text-decoration:none}
[data-theme="dark"] .back{color:var(--gold)}
.theme-toggle{position:fixed;top:18px;right:18px;z-index:20;width:44px;height:44px;border:0;border-radius:50%;background:var(--card-bg);color:var(--maroon);box-shadow:0 5px 18px rgba(0,0,0,.16);display:grid;place-items:center;cursor:pointer;transition:transform .18s,background .18s,color .18s}
.theme-toggle:hover{transform:translateY(-2px) rotate(8deg)}.theme-toggle:active{transform:translateY(0) scale(.94)}.theme-toggle .icon{width:20px;height:20px}.theme-toggle .icon-moon{display:none}
[data-theme="dark"] .theme-toggle{color:var(--gold)}[data-theme="dark"] .theme-toggle .icon-sun{display:none}[data-theme="dark"] .theme-toggle .icon-moon{display:block}
@media(max-width:820px){body{grid-template-columns:1fr}.brand{min-height:190px;padding:25px 28px;border-right:0;border-bottom:5px solid var(--gold)}.brand h1{font-size:1.8rem;max-width:22ch;margin:20px 0 8px}.brand p{display:none}.brand small{font-size:.78rem}.main{padding:34px 18px}.card{max-width:560px}}
@media(max-width:480px){.brand{min-height:150px;padding:19px 20px}.brand h1{font-size:1.55rem;margin:12px 0 4px}.brand-lockup{gap:14px}.brand-lockup img{width:64px;height:64px}.brand-title{font-size:18px}.sidebar-header-text p{font-size:11px}.main{padding:22px 12px}.card{padding:23px 18px}.card h2{font-size:1.55rem}.tabs{gap:6px}.tab{min-height:76px;font-size:.76rem}.theme-toggle{top:12px;right:12px;width:40px;height:40px}}
</style>
</head>
<body>
<button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle light and dark theme" aria-pressed="false">
  <svg class="icon icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.2"></circle><path d="M12 2.5v2.4M12 19.1v2.4M4.6 4.6l1.7 1.7M17.7 17.7l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.6 19.4l1.7-1.7M17.7 6.3l1.7-1.7"></path></svg>
  <svg class="icon icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 14.3A8.5 8.5 0 1 1 9.7 3.2a6.8 6.8 0 0 0 11.1 11.1z"></path></svg>
</button>
<aside class="brand">
  <div class="brand-lockup">
    <img src="<?= asset('images/logo.png') ?>" alt="RAM-YUM Logo">
    <div class="sidebar-header-text">
      <h2 class="brand-title">RAM-YUM <span>Store</span></h2>
      <p>Korean &amp; Japanese Store</p>
    </div>
  </div>
  <div class="brand-copy">
    <h1>Locked out? Pick the way back in.</h1>
    <p>Choose the option that works for you. If none of them do, HR can reset your account.</p>
  </div>
</aside>

<main class="main">
<div class="card">
  <h2>Reset your password</h2>
  <p class="sub">Choose how you want to reset it.</p>

  <div class="tabs" role="tablist">
    <button class="tab" role="tab" aria-selected="true" data-panel="p-email"><i class="fa-solid fa-envelope"></i>Email code</button>
    <button class="tab" role="tab" aria-selected="false" data-panel="p-user"><i class="fa-solid fa-user-lock"></i>Username</button>
    <button class="tab" role="tab" aria-selected="false" data-panel="p-hr"><i class="fa-solid fa-headset"></i>Contact HR</button>
  </div>

  <!-- 1. Gmail verification code -->
  <section class="panel active" id="p-email">
    <form id="f-send" data-url="reset-send-code">
      <label for="em">Gmail address</label>
      <input id="em" name="email" type="email" placeholder="you@gmail.com" required autocomplete="email">
      <p class="hint">We'll send a 6-digit code to this address. It expires in 5 minutes.</p>
      <button class="btn" type="submit">Send code</button>
    </form>
    <form id="f-verify" data-url="reset-verify-code" hidden>
      <label for="vc">Verification code</label>
      <input id="vc" name="code" class="code" inputmode="numeric" maxlength="6" pattern="\d{6}" required autocomplete="one-time-code">
      <label for="np1">New password</label>
      <div class="pw"><input id="np1" name="password" type="password" minlength="8" required autocomplete="new-password"><button type="button" aria-label="Show password"><i class="fa-solid fa-eye"></i></button></div>
      <label for="np1c">Confirm new password</label>
      <input id="np1c" name="password_confirm" type="password" minlength="8" required autocomplete="new-password">
      <button class="btn" type="submit">Reset password</button>
      <button class="btn ghost" type="button" id="resend">Send a new code</button>
    </form>
    <div class="msg"></div>
  </section>

  <!-- 2. Username + new password -->
  <section class="panel" id="p-user">
    <form data-url="reset-by-username">
      <label for="un">Username</label>
      <input id="un" name="username" value="<?= htmlspecialchars((string) ($_GET['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required autocomplete="username">
      <label for="cp">Current password</label>
      <div class="pw"><input id="cp" name="current_password" type="password" required autocomplete="current-password"><button type="button" aria-label="Show password"><i class="fa-solid fa-eye"></i></button></div>
      <label for="np2">New password</label>
      <div class="pw"><input id="np2" name="password" type="password" minlength="8" required autocomplete="new-password"><button type="button" aria-label="Show password"><i class="fa-solid fa-eye"></i></button></div>
      <label for="np2c">Confirm new password</label>
      <input id="np2c" name="password_confirm" type="password" minlength="8" required autocomplete="new-password">
      <p class="hint">Use at least 8 characters.</p>
      <button class="btn" type="submit">Create new password</button>
    </form>
    <div class="msg"></div>
  </section>

  <!-- 3. HR request -->
  <section class="panel" id="p-hr">
    <form data-url="reset-hr-request">
      <label for="hn">Full name</label>
      <input id="hn" name="full_name" required>
      <label for="hu">Username or employee ID</label>
      <input id="hu" name="identifier" required>
      <label for="hc">Contact email or phone</label>
      <input id="hc" name="contact" required>
      <label for="hm">Message to HR</label>
      <textarea id="hm" name="message" placeholder="Tell HR what happened (optional)"></textarea>
      <button class="btn" type="submit">Send request to HR</button>
      <p class="hint">HR will verify your identity and reset your account.</p>
    </form>
    <div class="msg"></div>
  </section>

  <a class="back" href="index.php?page=login"><i class="fa-solid fa-arrow-left"></i> Back to login</a>
</div>
</main>

<script>
// Keep the theme preference consistent with the login page.
const themeToggle=document.getElementById('themeToggle');
const themeRoot=document.documentElement;
const themeKey='ramyum-theme';
let savedTheme=null;
try{savedTheme=localStorage.getItem(themeKey)}catch(e){}
const initialTheme=savedTheme||(window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');
function applyTheme(theme){
  if(theme==='dark'){themeRoot.setAttribute('data-theme','dark');themeToggle.setAttribute('aria-pressed','true')}
  else{themeRoot.removeAttribute('data-theme');themeToggle.setAttribute('aria-pressed','false')}
}
applyTheme(initialTheme);
themeToggle.addEventListener('click',()=>{
  const next=themeRoot.getAttribute('data-theme')==='dark'?'light':'dark';
  applyTheme(next);
  try{localStorage.setItem(themeKey,next)}catch(e){}
});
// Tabs
const tabs=document.querySelectorAll('.tab');
tabs.forEach(t=>t.addEventListener('click',()=>{
  tabs.forEach(x=>x.setAttribute('aria-selected',x===t));
  document.querySelectorAll('.panel').forEach(p=>p.classList.toggle('active',p.id===t.dataset.panel));
}));
// Show/hide password
document.querySelectorAll('.pw button').forEach(b=>b.addEventListener('click',()=>{
  const i=b.previousElementSibling,show=i.type==='password';
  i.type=show?'text':'password';
  b.querySelector('i').className=show?'fa-solid fa-eye-slash':'fa-solid fa-eye';
}));

function say(form,text,ok){
  const m=form.closest('.panel').querySelector('.msg');
  m.textContent=text;m.className='msg show '+(ok?'ok':'err');
}
async function post(form,extra={}){
  const fd=new FormData(form);
  Object.entries(extra).forEach(([k,v])=>fd.append(k,v));
  if(fd.get('password')!==null && fd.get('password')!==fd.get('password_confirm')){
    say(form,'Passwords do not match.',false);return null;
  }
  const btn=form.querySelector('button[type=submit]');btn.disabled=true;
  try{
    const r=await fetch('index.php?page='+form.dataset.url,{method:'POST',body:fd});
    const d=await r.json();
    say(form,d.message||(d.success?'Done.':'Something went wrong.'),d.success);
    return d;
  }catch(e){say(form,'Could not reach the server. Try again.',false);return null}
  finally{btn.disabled=false}
}

const fSend=document.getElementById('f-send'),fVerify=document.getElementById('f-verify');
let sentTo='';
fSend.addEventListener('submit',async e=>{
  e.preventDefault();
  const d=await post(fSend);
  if(d&&d.success){sentTo=fSend.email.value;fSend.hidden=true;fVerify.hidden=false;fVerify.code.focus()}
});
document.getElementById('resend').addEventListener('click',async()=>{
  const fd=new FormData();fd.append('email',sentTo);
  const r=await fetch('index.php?page=reset-send-code',{method:'POST',body:fd}).then(x=>x.json()).catch(()=>null);
  say(fVerify,r?r.message:'Could not resend the code.',!!(r&&r.success));
});
fVerify.addEventListener('submit',async e=>{
  e.preventDefault();
  const d=await post(fVerify,{email:sentTo});
  if(d&&d.success)setTimeout(()=>location.href='index.php?page=login',1800);
});
document.querySelectorAll('#p-user form,#p-hr form').forEach(f=>f.addEventListener('submit',async e=>{
  e.preventDefault();
  const d=await post(f);
  if(d&&d.success){f.reset();if(f.dataset.url==='reset-by-username')setTimeout(()=>location.href='index.php?page=login',1800)}
}));
</script>
</body>
</html>