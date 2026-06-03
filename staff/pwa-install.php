<!-- PWA Install Banner -->
<div id="pwa-install-banner" style="
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 99999;
  padding: 0 16px 16px;
  animation: slideUp 0.4s ease-out;
">
  <div style="
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    border-radius: 16px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 -4px 24px rgba(0,0,0,0.25);
    border: 1px solid rgba(255,255,255,0.08);
  ">
    <img src="assets/img/logo/lwx.png" alt="Loewix" style="width:48px; height:48px; border-radius:12px; flex-shrink:0;">
    <div style="flex:1; min-width:0;">
      <div style="font-family:'Roboto',sans-serif; font-size:15px; font-weight:600; color:#fff; margin-bottom:2px;">
        Install Loewix
      </div>
      <div style="font-family:'Roboto',sans-serif; font-size:12px; color:rgba(255,255,255,0.6);">
        Akses lebih cepat dari Home Screen
      </div>
    </div>
    <button id="pwa-install-btn" style="
      background: linear-gradient(135deg, #2563eb, #3b82f6);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 10px 20px;
      font-family: 'Roboto', sans-serif;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      white-space: nowrap;
      flex-shrink: 0;
    ">Install</button>
    <button id="pwa-dismiss-btn" style="
      background: none;
      border: none;
      color: rgba(255,255,255,0.4);
      font-size: 20px;
      cursor: pointer;
      padding: 4px 8px;
      line-height: 1;
      flex-shrink: 0;
    ">&times;</button>
  </div>
</div>
<style>
  @keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }
</style>
<script>
(function() {
  let deferredPrompt = null;
  const banner = document.getElementById('pwa-install-banner');
  const installBtn = document.getElementById('pwa-install-btn');
  const dismissBtn = document.getElementById('pwa-dismiss-btn');

  // Check if already dismissed today
  const dismissedAt = localStorage.getItem('pwa-dismissed');
  const oneDayMs = 24 * 60 * 60 * 1000;
  const wasDismissedRecently = dismissedAt && (Date.now() - parseInt(dismissedAt)) < oneDayMs;

  // Check if already installed (standalone mode)
  const isInstalled = window.matchMedia('(display-mode: standalone)').matches
                   || window.navigator.standalone === true;

  window.addEventListener('beforeinstallprompt', function(e) {
    e.preventDefault();
    deferredPrompt = e;

    if (!wasDismissedRecently && !isInstalled && banner) {
      // Show banner after 2 seconds
      setTimeout(function() {
        banner.style.display = 'block';
      }, 2000);
    }
  });

  if (installBtn) {
    installBtn.addEventListener('click', function() {
      if (!deferredPrompt) return;
      banner.style.display = 'none';
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then(function(result) {
        console.log('[PWA] Install result:', result.outcome);
        deferredPrompt = null;
      });
    });
  }

  if (dismissBtn) {
    dismissBtn.addEventListener('click', function() {
      banner.style.display = 'none';
      localStorage.setItem('pwa-dismissed', Date.now().toString());
    });
  }

  // Hide if app gets installed
  window.addEventListener('appinstalled', function() {
    banner.style.display = 'none';
    deferredPrompt = null;
    console.log('[PWA] App installed!');
  });
})();
</script>
