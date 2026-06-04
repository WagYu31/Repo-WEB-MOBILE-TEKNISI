<?php
session_start();
if (isset($_GET['login']) && $_GET['login'] === 'failed') {
  echo "<script>alert('Cek kembali Username dan Password Anda.');</script>";
}
$loginError = isset($_GET['error']) ? htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="assets/img/logo/lwx.png">
  <link rel="icon" type="image/png" href="assets/img/logo/lwx.png">
  <!-- PWA -->
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#0f0f1a">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Loewix">
  <meta name="mobile-web-app-capable" content="yes">
  <title>LOEWIX | Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons&display=swap" rel="stylesheet">
  <style>
    .material-icons, .material-icons-round {
      font-family: 'Material Icons' !important;
      font-weight: normal;
      font-style: normal;
      font-size: 24px;
      display: inline-block;
      line-height: 1;
      text-transform: none;
      letter-spacing: normal;
      word-wrap: normal;
      white-space: nowrap;
      direction: ltr;
      -webkit-font-smoothing: antialiased;
      text-rendering: optimizeLegibility;
    }
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0f0f1a;
      overflow: hidden;
      position: relative;
    }

    /* ═══ Animated gradient background ═══ */
    .bg-animated {
      position: fixed;
      inset: 0;
      z-index: 0;
      background: 
        radial-gradient(ellipse 80% 60% at 20% 40%, rgba(236, 72, 153, 0.15) 0%, transparent 60%),
        radial-gradient(ellipse 60% 80% at 80% 20%, rgba(99, 102, 241, 0.12) 0%, transparent 60%),
        radial-gradient(ellipse 70% 50% at 50% 90%, rgba(14, 165, 233, 0.1) 0%, transparent 60%),
        #0f0f1a;
    }

    .bg-animated::before {
      content: '';
      position: absolute;
      inset: 0;
      background: 
        radial-gradient(circle 400px at 30% 30%, rgba(236, 72, 153, 0.08), transparent),
        radial-gradient(circle 300px at 70% 70%, rgba(99, 102, 241, 0.06), transparent);
      animation: bgPulse 8s ease-in-out infinite alternate;
    }

    @keyframes bgPulse {
      0% { opacity: 0.6; transform: scale(1); }
      100% { opacity: 1; transform: scale(1.05); }
    }

    /* ═══ Floating particles ═══ */
    .particles {
      position: fixed;
      inset: 0;
      z-index: 1;
      overflow: hidden;
      pointer-events: none;
    }

    .particle {
      position: absolute;
      width: 3px;
      height: 3px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 50%;
      animation: particleFloat linear infinite;
    }

    @keyframes particleFloat {
      0% { transform: translateY(100vh) scale(0); opacity: 0; }
      10% { opacity: 1; }
      90% { opacity: 1; }
      100% { transform: translateY(-10vh) scale(1); opacity: 0; }
    }

    /* ═══ Login container ═══ */
    .login-wrapper {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 420px;
      padding: 20px;
    }

    /* ═══ Glass card ═══ */
    .login-card {
      background: rgba(255, 255, 255, 0.04);
      backdrop-filter: blur(40px);
      -webkit-backdrop-filter: blur(40px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 24px;
      padding: 48px 36px 40px;
      box-shadow: 
        0 0 0 1px rgba(255, 255, 255, 0.03),
        0 32px 64px -16px rgba(0, 0, 0, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.06);
      animation: cardAppear 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes cardAppear {
      0% { opacity: 0; transform: translateY(30px) scale(0.96); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* ═══ Logo ═══ */
    .logo-section {
      text-align: center;
      margin-bottom: 36px;
    }

    .logo-section img {
      height: 44px;
      filter: brightness(0) invert(1);
      opacity: 0.95;
      transition: opacity 0.3s;
    }

    .logo-section img:hover { opacity: 1; }

    .logo-divider {
      width: 40px;
      height: 3px;
      background: linear-gradient(90deg, #ec4899, #8b5cf6);
      border-radius: 3px;
      margin: 16px auto 0;
    }

    .welcome-text {
      color: rgba(255, 255, 255, 0.4);
      font-size: 12px;
      font-weight: 500;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      margin-top: 14px;
    }

    /* ═══ Form inputs ═══ */
    .form-group {
      position: relative;
      margin-bottom: 20px;
    }

    .form-group .input-icon {
      position: absolute;
      left: 16px;
      top: 50%;
      font-family: 'Material Icons' !important;
      transform: translateY(-50%);
      color: rgba(255, 255, 255, 0.25);
      font-size: 20px;
      transition: color 0.3s;
      pointer-events: none;
      z-index: 2;
    }

    .form-input {
      width: 100%;
      padding: 16px 16px 16px 50px;
      background: rgba(255, 255, 255, 0.05);
      border: 1.5px solid rgba(255, 255, 255, 0.08);
      border-radius: 14px;
      color: #fff;
      font-size: 14px;
      font-family: inherit;
      font-weight: 400;
      outline: none;
      transition: all 0.3s ease;
    }

    .form-input::placeholder {
      color: rgba(255, 255, 255, 0.25);
      font-weight: 400;
    }

    .form-input:focus {
      border-color: rgba(236, 72, 153, 0.5);
      background: rgba(255, 255, 255, 0.07);
      box-shadow: 0 0 0 4px rgba(236, 72, 153, 0.08);
    }

    .form-input:focus ~ .input-icon {
      color: #ec4899;
    }

    /* Password toggle */
    .toggle-pw {
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.25);
      cursor: pointer;
      font-size: 20px;
      padding: 0;
      transition: color 0.3s;
      z-index: 2;
    }
    .toggle-pw:hover { color: rgba(255, 255, 255, 0.5); }

    /* ═══ Forgot password ═══ */
    .forgot-link {
      display: inline-block;
      color: rgba(255, 255, 255, 0.3);
      font-size: 12px;
      font-weight: 500;
      text-decoration: none;
      margin-bottom: 24px;
      transition: color 0.3s;
    }
    .forgot-link:hover { color: #ec4899; }

    /* ═══ Submit button ═══ */
    .btn-login {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, #ec4899, #8b5cf6);
      border: none;
      border-radius: 14px;
      color: #fff;
      font-size: 14px;
      font-weight: 700;
      font-family: inherit;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      transition: all 0.3s ease;
      box-shadow: 0 8px 24px -4px rgba(236, 72, 153, 0.3);
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 32px -4px rgba(236, 72, 153, 0.4);
    }

    .btn-login:active {
      transform: translateY(0);
      box-shadow: 0 4px 16px -4px rgba(236, 72, 153, 0.3);
    }

    .btn-login::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
      opacity: 0;
      transition: opacity 0.3s;
    }
    .btn-login:hover::after { opacity: 1; }

    /* ═══ Sign up link ═══ */
    .signup-text {
      text-align: center;
      margin-top: 28px;
      color: rgba(255, 255, 255, 0.3);
      font-size: 13px;
    }

    .signup-text a {
      color: #ec4899;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }
    .signup-text a:hover { color: #f472b6; }

    /* ═══ Error toast ═══ */
    .error-toast {
      position: fixed;
      top: 24px;
      left: 50%;
      transform: translateX(-50%) translateY(-100px);
      background: rgba(239, 68, 68, 0.95);
      backdrop-filter: blur(20px);
      color: #fff;
      padding: 14px 24px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
      z-index: 1000;
      box-shadow: 0 8px 32px rgba(239, 68, 68, 0.3);
      transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .error-toast.show { transform: translateX(-50%) translateY(0); }

    /* ═══ Footer ═══ */
    .login-footer {
      text-align: center;
      margin-top: 24px;
      color: rgba(255, 255, 255, 0.15);
      font-size: 11px;
      letter-spacing: 0.05em;
    }

    /* ═══ Responsive ═══ */
    @media (max-width: 480px) {
      .login-card { padding: 36px 24px 32px; border-radius: 20px; }
      .logo-section img { height: 36px; }
      .form-input { padding: 14px 14px 14px 46px; font-size: 14px; }
    }

    @media (max-height: 600px) {
      .login-card { padding: 28px 28px 24px; }
      .logo-section { margin-bottom: 20px; }
      .form-group { margin-bottom: 14px; }
    }
  </style>
</head>

<body>
  <!-- Animated Background -->
  <div class="bg-animated"></div>

  <!-- Floating Particles -->
  <div class="particles" id="particles"></div>

  <!-- Error Toast -->
  <?php if ($loginError): ?>
  <div class="error-toast" id="errorToast">
    <i class="material-icons" style="font-size:18px;">error_outline</i>
    <?= $loginError ?>
  </div>
  <?php endif; ?>

  <!-- Login Card -->
  <div class="login-wrapper">
    <div class="login-card">
      <!-- Logo -->
      <div class="logo-section">
        <img src="assets/img/logo/lwx-logo.png" alt="Loewix">
        <div class="logo-divider"></div>
        <p class="welcome-text">Portal Admin · Teknisi · Finance</p>
      </div>

      <!-- Form -->
      <form role="form" method="POST" action="proses_login.php" autocomplete="on">
        <div class="form-group">
          <input type="text" class="form-input" name="email" placeholder="Username" required autocomplete="username">
          <i class="material-icons input-icon">person_outline</i>
        </div>

        <div class="form-group">
          <input type="password" class="form-input" name="password" id="passwordInput" placeholder="Password" required autocomplete="current-password">
          <i class="material-icons input-icon">lock_outline</i>
          <button type="button" class="toggle-pw" onclick="togglePassword()">
            <i class="material-icons" id="pwIcon">visibility_off</i>
          </button>
        </div>

        <a href="javascript:;" class="forgot-link">Lupa Password?</a>

        <button type="submit" class="btn-login">
          Sign In
        </button>

        <p class="signup-text">
          Belum punya akun? <a href="sign-up.php">Daftar</a>
        </p>
      </form>
    </div>

    <div class="login-footer">
      © <?= date('Y') ?> Loewix CCTV — All rights reserved
    </div>
  </div>

  <!-- PWA Install Banner -->
  <?php include 'pwa-install.php'; ?>
  <!-- PWA Service Worker Registration -->
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function() {
        navigator.serviceWorker.register('/staff/sw.js', { scope: '/staff/' })
          .then(function(reg) { console.log('[PWA] SW registered:', reg.scope); })
          .catch(function(err) { console.log('[PWA] SW failed:', err); });
      });
    }
  </script>

  <script>
    // Toggle password visibility
    function togglePassword() {
      const inp = document.getElementById('passwordInput');
      const icon = document.getElementById('pwIcon');
      if (inp.type === 'password') {
        inp.type = 'text';
        icon.textContent = 'visibility';
      } else {
        inp.type = 'password';
        icon.textContent = 'visibility_off';
      }
    }

    // Generate floating particles
    (function() {
      const container = document.getElementById('particles');
      for (let i = 0; i < 30; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.width = p.style.height = (Math.random() * 3 + 1) + 'px';
        p.style.animationDuration = (Math.random() * 15 + 10) + 's';
        p.style.animationDelay = (Math.random() * 10) + 's';
        container.appendChild(p);
      }
    })();

    // Show error toast
    <?php if ($loginError): ?>
    setTimeout(function() {
      document.getElementById('errorToast').classList.add('show');
      setTimeout(function() {
        document.getElementById('errorToast').classList.remove('show');
      }, 4000);
    }, 300);
    <?php endif; ?>

    // Input focus animation
    document.querySelectorAll('.form-input').forEach(function(input) {
      input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
        this.parentElement.style.transition = 'transform 0.2s ease';
      });
      input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
      });
    });
  </script>
</body>

</html>