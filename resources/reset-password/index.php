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
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--maroon:#7a1523;--maroon-dark:#560d18;--gold:#d9a441;--gold-light:#f3deaa;--cream:#fbf5e9;--ink:#2b1a1d;--ink-soft:#6f5c5f;--card-bg:#fff;--border:#e6d9c3;--radius:14px;--shadow:0 12px 32px rgba(86,13,24,.14);--err:#b3261e;--ok:#2e7d4f}
@media (prefers-color-scheme:dark){:root{--cream:#1d1214;--ink:#f6ece0;--ink-soft:#c2aeb0;--card-bg:#2a1a1d;--border:#4a3237;--shadow:0 12px 32px rgba(0,0,0,.5);--gold-light:#4a3a1c}}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;font-family:'Nunito',sans-serif;background:var(--cream);color:var(--ink);display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr)}
.brand{background:var(--maroon);color:#fff;padding:48px;display:flex;flex-direction:column;justify-content:space-between;border-right:6px solid var(--gold)}
.brand h1{font-family:'Baloo 2',sans-serif;font-size:2.6rem;line-height:1.1;margin:0 0 12px}
.brand p{color:var(--gold-light);max-width:34ch;line-height:1.6;margin:0}
.brand .logo{font-family:'Baloo 2',sans-serif;font-size:1.4rem;color:var(--gold)}
.main{display:flex;align-items:center;justify-content:center;padding:32px 20px}
.card{width:100%;max-width:520px;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);padding:28px}
.card h2{font-family:'Baloo 2',sans-serif;margin:0 0 4px;font-size:1.5rem}
.sub{color:var(--ink-soft);margin:0 0 20px;font-size:.95rem}
.tabs{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:22px}
.tab{border:1.5px solid var(--border);background:transparent;color:var(--ink);border-radius:10px;padding:12px 6px;cursor:pointer;font:600 .85rem 'Nunito',sans-serif;display:flex;flex-direction:column;align-items:center;gap:6px}
.tab i{font-size:1.2rem;color:var(--maroon)}
.tab[aria-selected="true"]{border-color:var(--maroon);background:var(--gold-light);color:var(--ink)}
.tab:focus-visible,.btn:focus-visible,input:focus-visible,textarea:focus-visible,a:focus-visible{outline:3px solid var(--gold);outline-offset:2px}
.panel{display:none}.panel.active{display:block}
label{display:block;font-weight:700;font-size:.9rem;margin:14px 0 6px}
input,textarea{width:100%;padding:11px 13px;border:1.5px solid var(--border);border-radius:10px;background:transparent;color:var(--ink);font:inherit}
textarea{min-height:96px;resize:vertical}
.pw{position:relative}.pw button{position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:0;color:var(--ink-soft);cursor:pointer;padding:6px}
.btn{width:100%;margin-top:20px;padding:12px;border:0;border-radius:10px;background:var(--maroon);color:#fff;font:700 1rem 'Nunito',sans-serif;cursor:pointer}
.btn:hover{background:var(--maroon-dark)}.btn:disabled{opacity:.6;cursor:wait}
.btn.ghost{background:transparent;color:var(--maroon);border:1.5px solid var(--maroon);margin-top:10px}
.hint{font-size:.82rem;color:var(--ink-soft);margin-top:6px}
.msg{display:none;margin-top:16px;padding:11px 13px;border-radius:10px;font-size:.92rem}
.msg.show{display:block}.msg.err{background:rgba(179,38,30,.1);color:var(--err)}.msg.ok{background:rgba(46,125,79,.12);color:var(--ok)}
.code{letter-spacing:.5em;text-align:center;font:700 1.4rem 'Baloo 2',sans-serif}
.back{display:block;text-align:center;margin-top:22px;color:var(--maroon);font-weight:700;text-decoration:none}
@media (prefers-color-scheme:dark){.back,.tab i{color:var(--gold)}.btn.ghost{color:var(--gold);border-color:var(--gold)}}
@media (max-width:820px){body{grid-template-columns:1fr}.brand{padding:24px;border-right:0;border-bottom:6px solid var(--gold)}.brand h1{font-size:1.8rem}.brand p{display:none}}
</style>
</head>
<body>
<aside class="brand">
  <div class="logo"><i class="fa-solid fa-bowl-food"></i> Ram Yum</div>
  <div>
    <h1>Locked out? Pick the way back in.</h1>
    <p>Choose the option that works for you. If none of them do, HR can reset your account.</p>
  </div>
  <small style="color:var(--gold-light)">Ram Yum HR Management System</small>
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
      <p class="hint">We'll send a 6-digit code to this address. It expires in 10 minutes.</p>
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