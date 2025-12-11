<?php
// public/partials/logout_modal.php

$logoutAction = $logoutAction ?? '../logout.php';
?>
<div id="logoutModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-6 hidden bg-black bg-opacity-40">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-xl">
        <div class="p-6">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-red-600 text-3xl">!</span>
            </div>
            <h2 class="text-gray-800 text-center mb-2 text-base font-semibold">Konfirmasi Keluar</h2>
            <p class="text-sm text-gray-600 text-center mb-6">
                Apakah Anda yakin ingin keluar dari aplikasi?
            </p>

            <div class="flex gap-3">
                <button type="button" onclick="closeLogoutModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl hover:bg-gray-200 transition-colors text-sm">
                    Batal
                </button>
                <form method="post" action="<?php echo htmlspecialchars($logoutAction); ?>" class="flex-1">
                    <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-xl hover:bg-red-700 transition-colors text-sm">
                        Ya, Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
  if (window.__logoutModalInit) return;
  window.__logoutModalInit = true;

  const modal = document.getElementById('logoutModal');
  window.openLogoutModal = function () {
    if (modal) modal.classList.remove('hidden');
  };
  window.closeLogoutModal = function () {
    if (modal) modal.classList.add('hidden');
  };
  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeLogoutModal();
    });
  }
})();
</script>