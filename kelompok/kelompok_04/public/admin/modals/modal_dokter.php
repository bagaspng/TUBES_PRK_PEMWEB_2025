<?php
// public/admin/modals/modal_dokter.php
?>

<div id="dokterModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40">
  <div class="bg-white rounded-2xl w-full max-w-3xl p-6 mx-4 max-h-screen overflow-y-auto">
    <div class="flex items-center justify-between mb-4">
      <h2 id="modalTitle" class="text-base font-semibold text-gray-800">Tambah Dokter</h2>
      <button id="btnCloseModal" type="button" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
    </div>

    <form id="dokterForm" method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="action" id="formAction" value="create">
      <input type="hidden" name="id_dokter" id="formId" value="">

      <div>
        <label class="block text-sm text-gray-700 mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
        <input type="text" name="nama_dokter" id="field_nama" required
               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
      </div>

      <div>
        <label class="block text-sm text-gray-700 mb-2">NIP / Kode Dokter <span class="text-red-500">*</span></label>
        <input type="text" name="kode_dokter" id="field_kode" required
               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
      </div>

      <div>
        <label class="block text-sm text-gray-700 mb-2">Spesialisasi <span class="text-red-500">*</span></label>
        <input type="text" name="spesialis" id="field_spesialis" required
               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
      </div>

      <div>
        <label class="block text-sm text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
        <input type="email" name="email" id="field_email" required
               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
      </div>

      <div>
        <label class="block text-sm text-gray-700 mb-2">Nomor Telepon <span class="text-red-500">*</span></label>
        <input type="tel" name="no_hp" id="field_nohp" required
               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
      </div>

      <div>
        <label class="block text-sm text-gray-700 mb-2">Poli <span class="text-red-500">*</span></label>
        <select name="id_poli" id="field_poli" required
                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
          <option value="">-- Pilih Poli --</option>
          <?php foreach ($poliklinik as $p): ?>
            <option value="<?php echo (int)$p['id_poli']; ?>"><?php echo htmlspecialchars($p['nama_poli']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="md:col-span-2 border-t border-gray-200 my-2"></div>
      <div class="md:col-span-2">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Data Login Dokter</h3>
      </div>

      <div>
        <label class="block text-sm text-gray-700 mb-2">Username <span class="text-red-500">*</span></label>
        <input type="text" name="username" id="field_username" required
               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
        <p class="mt-1 text-xs text-gray-400">Username untuk login dokter</p>
      </div>

      <div>
        <label class="block text-sm text-gray-700 mb-2">Password <span class="text-red-500" id="passwordRequired">*</span></label>
        <input type="password" name="password" id="field_password"
               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
        <p class="mt-1 text-xs text-gray-400" id="passwordHint">Password untuk login dokter</p>
      </div>

      <div class="md:col-span-2 flex justify-end gap-3 pt-2">
        <button type="button" id="btnCancelForm" class="px-4 py-3 bg-gray-100 text-gray-700 rounded-xl">Batal</button>
        <button type="submit" class="px-6 py-3 bg-green-500 text-white rounded-xl">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  const modal = document.getElementById('dokterModal');
  const btnAdd = document.getElementById('btnAddDokter');
  const btnClose = document.getElementById('btnCloseModal');
  const btnCancel = document.getElementById('btnCancelForm');
  const form = document.getElementById('dokterForm');
  const formAction = document.getElementById('formAction');
  const formId = document.getElementById('formId');
  const modalTitle = document.getElementById('modalTitle');
  const passwordField = document.getElementById('field_password');
  const passwordRequired = document.getElementById('passwordRequired');
  const passwordHint = document.getElementById('passwordHint');

  function openModal() {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    window.scrollTo(0,0);
  }
  function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  function clearForm() {
    form.reset();
    formAction.value = 'create';
    formId.value = '';
    modalTitle.textContent = 'Tambah Dokter';
    passwordField.required = true;
    passwordRequired.style.display = 'inline';
    passwordHint.textContent = 'Password untuk login dokter';
  }

  if (btnAdd) {
    btnAdd.addEventListener('click', function () {
      clearForm();
      openModal();
    });
  }
  if (btnClose) btnClose.addEventListener('click', closeModal);
  if (btnCancel) btnCancel.addEventListener('click', closeModal);

  // Auto-open modal jika dari dashboard atau dengan parameter ?modal=create
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('modal') === 'create') {
    clearForm();
    openModal();
  }

  // Edit buttons
  document.querySelectorAll('.edit-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id = btn.getAttribute('data-id');
      document.getElementById('field_nama').value = btn.getAttribute('data-nama') || '';
      document.getElementById('field_kode').value = btn.getAttribute('data-kode') || '';
      document.getElementById('field_spesialis').value = btn.getAttribute('data-spesialis') || '';
      document.getElementById('field_email').value = btn.getAttribute('data-email') || '';
      document.getElementById('field_nohp').value = btn.getAttribute('data-nohp') || '';
      document.getElementById('field_username').value = btn.getAttribute('data-username') || '';
      document.getElementById('field_poli').value = btn.getAttribute('data-id-poli') || '';
      document.getElementById('field_password').value = '';
      formAction.value = 'update';
      formId.value = id;
      modalTitle.textContent = 'Edit Dokter';
      passwordField.required = false;
      passwordRequired.style.display = 'none';
      passwordHint.textContent = 'Kosongkan jika tidak ingin mengubah password';
      openModal();
    });
  });

  // Close modal on clicking outside
  modal.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });
})();
</script>