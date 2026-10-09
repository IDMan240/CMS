<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMS | Administrator Login</title>
<style>
:root{--navy:#062447;--navy2:#0b315c;--red:#f01425;--blue:#1765bf;--line:#cbd7e6;--text:#0a2f5c}
*{box-sizing:border-box}
html,body{margin:0;width:100%;min-height:100%;font-family:Arial,Helvetica,sans-serif}
body{min-height:100vh;background:linear-gradient(135deg,#f7f9fc 0%,#fff 48%,#f4f7fb 100%);color:var(--text);overflow-x:hidden}

/* SINGLE LOGIN PAGE — NO BRANDING/PICTURE PANEL */
.login-page{min-height:100vh;width:100%;display:flex;align-items:center;justify-content:center;padding:30px 18px;position:relative;overflow:hidden}
.login-page::before{content:"";position:absolute;top:-190px;right:-190px;width:520px;height:520px;border-radius:50%;border:80px solid rgba(13,57,105,.035);pointer-events:none}
.login-page::after{content:"";position:absolute;left:-120px;bottom:-170px;width:470px;height:260px;border-top:8px solid #123f70;border-right:8px solid #123f70;transform:skewY(-25deg);box-shadow:0 14px 0 -6px var(--red);pointer-events:none}
.login-side{position:relative;z-index:2;width:100%;display:flex;align-items:center;justify-content:center}
.login-content{position:relative;width:100%;max-width:560px;background:#fff;border:1px solid #e0e7f0;border-radius:24px;padding:38px 48px 34px;text-align:center;box-shadow:0 25px 70px rgba(9,43,85,.12)}
.cms-logo{width:128px;height:105px;object-fit:contain;display:block;margin:0 auto 1px;background:transparent}
.cms-title{margin:0;font-size:35px;line-height:1.02;font-weight:900;letter-spacing:-1.5px;color:#092b55;text-transform:uppercase}
.cms-subtitle{margin:3px 0 0;font-size:20px;line-height:1.08;font-weight:900;letter-spacing:-.5px;color:#092b55;text-transform:uppercase}
.red-rule{width:76px;height:3px;background:var(--red);margin:22px auto 29px}
.login-heading{margin:0;font-size:23px;letter-spacing:4.5px;font-weight:900;color:#0a2f5c;text-transform:uppercase}
.login-help{margin:10px 0 31px;color:#637693;font-size:16px}
.login-form{text-align:left}
.field{position:relative;margin-bottom:20px}
.field input{display:block;width:100%;height:62px;border:1px solid var(--line);border-radius:11px;background:#fff;outline:none;padding:0 60px 0 68px;color:#14385f;font-size:17px;box-shadow:0 1px 3px rgba(15,45,80,.03);transition:border-color .2s,box-shadow .2s}
.field input::placeholder{color:#73849d}
.field input:focus{border-color:#2d6fc0;box-shadow:0 0 0 4px rgba(45,111,192,.09)}
.field-icon{position:absolute;left:22px;top:50%;transform:translateY(-50%);width:25px;height:25px;color:#12385f;display:flex;align-items:center;justify-content:center;pointer-events:none}
.field-icon svg,.password-toggle svg{width:24px;height:24px;stroke:currentColor;fill:none;stroke-width:2}
.password-toggle{position:absolute;right:11px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:#113b67;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;border-radius:8px}
.password-toggle:hover{background:#f1f5fa}
.options{display:flex;justify-content:space-between;align-items:center;margin:1px 0 26px;font-size:15px;color:#365274}
.remember{display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none}
.remember input{width:20px;height:20px;margin:0;accent-color:#1765bf;cursor:pointer}
.forgot{color:#075dcc;text-decoration:none;font-weight:600}.forgot:hover{text-decoration:underline}
.login-button{display:flex;align-items:center;justify-content:center;gap:12px;width:100%;height:62px;border:0;border-radius:11px;background:linear-gradient(90deg,#ec111f,#f20e20);color:#fff;font-size:19px;font-weight:900;letter-spacing:.2px;cursor:pointer;box-shadow:0 10px 22px rgba(237,20,34,.18);transition:.2s}
.login-button:hover{transform:translateY(-1px);box-shadow:0 14px 27px rgba(237,20,34,.24)}
.login-button:disabled{opacity:.75;cursor:not-allowed;transform:none}.login-button svg{width:24px;height:24px;stroke:currentColor;fill:none;stroke-width:2}
.or{display:flex;align-items:center;gap:24px;margin:29px 0 24px;color:#6f829c;font-size:15px;font-weight:700}.or::before,.or::after{content:"";height:1px;background:#d8e0ea;flex:1}
.access-note{display:flex;align-items:flex-start;gap:18px;text-align:left;border:1px solid #d3dce8;background:#f5f8fc;border-radius:11px;padding:17px 20px;color:#12385f}
.access-icon{width:38px;height:38px;flex:0 0 38px;display:flex;align-items:center;justify-content:center}.access-icon svg{width:36px;height:36px;stroke:#0b315c;fill:none;stroke-width:2}
.access-note strong{display:block;font-size:15px;margin:1px 0 6px}.access-note span{display:block;font-size:13px;line-height:1.5;color:#60748e}
.error-message{display:none;text-align:left;margin:0 0 16px;padding:12px 14px;border-radius:9px;border:1px solid #f0b8bd;background:#fff1f2;color:#b40e18;font-size:13px;font-weight:700}
.footer{margin-top:18px;color:#8391a4;font-size:10px;letter-spacing:.5px}
.login-overlay{position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:9999;background:rgba(2,18,39,.68);backdrop-filter:blur(5px);padding:20px}
.login-dialog{width:min(380px,100%);background:#fff;border-radius:18px;padding:30px 25px;text-align:center;border-top:6px solid var(--red);box-shadow:0 25px 70px rgba(0,0,0,.3)}
.spinner{width:48px;height:48px;margin:0 auto 16px;border-radius:50%;border:4px solid #e8edf3;border-top-color:var(--red);animation:spin .75s linear infinite}
.login-dialog h3{margin:0;color:#0a2f5c;font-size:20px}.login-dialog p{margin:8px 0 0;color:#697b91;font-size:13px;line-height:1.5}@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:600px){.login-page{padding:18px 14px}.login-content{max-width:520px;padding:30px 22px 26px;border-radius:19px}.cms-logo{width:112px;height:90px}.cms-title{font-size:28px}.cms-subtitle{font-size:17px}.red-rule{margin:18px auto 25px}.login-heading{font-size:18px;letter-spacing:3px}.login-help{font-size:14px;margin-bottom:25px}.field input{height:57px;font-size:16px;padding-left:61px}.login-button{height:57px;font-size:17px}.options{font-size:13px}.access-note{padding:14px;gap:13px}.access-note span{font-size:12px}.or{margin:25px 0 21px}.footer{font-size:9px}}
</style>
</head>
<body>
<div class="login-page">
    <section class="login-side" aria-label="Administrator login">
        <div class="login-content">
            <img class="cms-logo" src="assets/cms-login-logo.png" alt="CMS logo">
            <h1 class="cms-title">Calibration</h1>
            <div class="cms-subtitle">Management System</div>
            <div class="red-rule"></div>

            <h2 class="login-heading">Administrator Login</h2>
            <p class="login-help">Sign in to your account to continue</p>

            <div id="errorMessage" class="error-message"></div>

            <form id="loginForm" method="POST" action="../auth/login.php">
                <div class="field">
                    <span class="field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </span>
                    <input type="text" id="username" name="username" autocomplete="username" placeholder="Username" required>
                </div>

                <div class="field">
                    <span class="field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                    </span>
                    <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Password" required>
                    <button type="button" class="password-toggle" id="togglePassword" aria-label="Show password">
                        <svg id="eyeIcon" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                    </button>
                </div>

                <div class="options">
                    <label class="remember"><input type="checkbox" id="rememberUsername"> <span>Remember me</span></label>
                    <a href="#" class="forgot" id="forgotPassword">Forgot password?</a>
                </div>

                <button type="submit" class="login-button" id="loginButton">
                    <svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5"></path><path d="M15 12H3"></path><path d="M21 19V5a2 2 0 0 0-2-2h-6"></path></svg>
                    <span>Login</span>
                </button>
            </form>

            <div class="or"><span>OR</span></div>

            <div class="access-note">
                <div class="access-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 3 20 6v5c0 5.2-3.4 8.7-8 10-4.6-1.3-8-4.8-8-10V6l8-3Z"></path><path d="m8.5 12 2.2 2.2 4.8-5"></path></svg>
                </div>
                <div>
                    <strong>Authorized Access Only</strong>
                    <span>This system is for authorized personnel only.<br>Unauthorized access is prohibited.</span>
                </div>
            </div>

            <div class="footer">Calibration • Testing • Inspection • Certification</div>
        </div>
    </section>
</div>

<div class="login-overlay" id="loginOverlay" aria-hidden="true">
    <div class="login-dialog" role="status" aria-live="polite">
        <div class="spinner"></div>
        <h3>Authenticating...</h3>
        <p>Please wait while the system verifies your login details.</p>
    </div>
</div>

<script>
(function(){
    const params = new URLSearchParams(window.location.search);
    const error = params.get('error');
    const errorBox = document.getElementById('errorMessage');
    const messages = {
        invalid:'Invalid username or password.',
        disabled:'This user account has been disabled.',
        required:'Please enter your username and password.',
        database:'Unable to connect to the database.'
    };
    if(error){ errorBox.textContent = messages[error] || 'Login failed. Please try again.'; errorBox.style.display='block'; }

    const username = document.getElementById('username');
    const remember = document.getElementById('rememberUsername');
    const saved = localStorage.getItem('cms_remembered_username');
    if(saved){ username.value=saved; remember.checked=true; }

    const password = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');
    toggle.addEventListener('click', function(){
        const show = password.type === 'password';
        password.type = show ? 'text' : 'password';
        this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        document.getElementById('eyeIcon').innerHTML = show
            ? '<path d="M3 3l18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.1 3.9"></path><path d="M6.1 6.1C3.6 8 2 12 2 12s3.5 7 10 7c1 0 2-.2 2.9-.5"></path>'
            : '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle>';
    });

    document.getElementById('forgotPassword').addEventListener('click', function(e){
        e.preventDefault();
        alert('Please contact the CMS administrator to reset your password.');
    });

    document.getElementById('loginForm').addEventListener('submit', function(){
        if(remember.checked) localStorage.setItem('cms_remembered_username', username.value.trim());
        else localStorage.removeItem('cms_remembered_username');
        document.getElementById('loginOverlay').style.display='flex';
        document.getElementById('loginOverlay').setAttribute('aria-hidden','false');
        const button=document.getElementById('loginButton');
        button.disabled=true;
        button.querySelector('span').textContent='Authenticating...';
    });
})();
</script>
</body>
</html>
