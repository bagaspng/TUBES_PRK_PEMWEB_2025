<?php
// public/admin/modals/modal_poli.php
?>

<div id="poliModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40">
  <div class="bg-white rounded-2xl w-full max-w-2xl p-6 mx-4">
    <div class="flex items-center justify-between mb-4">
      <h2 id="poliModalTitle" class="text-base font-semibold text-gray-800">Tambah Poli</h2>
      <button id="btnClosePoliModal" type="button" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
    </div>

    <form id="poliForm" method="post" class="space-y-4">
      <input type="hidden" name="action" id="poliFormAction" value="create">
      <input type="hidden" name="id_poli" id="poliFormId" value="">

      <div>
        <label class="block text-sm text-gray-700 mb-2">
            Nama Poli <span class="text-red-500">*</span>
        </label>
        <input
            type="text"
            name="nama_poli"
            id="field_poli_nama"
            value=""
            placeholder="Contoh: Poli Umum"
            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
            required
        >
      </div>

      <div>
        <label class="block text-sm text-gray-700 mb-2">
            Deskripsi
        </label>
        <textarea
            name="deskripsi"
            id="field_poli_deskripsi"
            rows="3"
            placeholder="Deskripsi singkat tentang poli"
            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 text-sm resize-none"
        ></textarea>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <button type="button" id="btnCancelPoli" class="px-4 py-3 bg-gray-100 text-gray-700 rounded-xl text-sm">Batal</button>
        <button type="submit" class="px-6 py-3 bg-green-500 text-white rounded-xl text-sm">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  const modal = document.getElementById('poliModal');
  const btnAdd = document.getElementById('btnAddPoli');
  const btnClose = document.getElementById('btnClosePoliModal');
  const btnCancel = document.getElementById('btnCancelPoli');
  const form = document.getElementById('poliForm');
  const formAction = document.getElementById('poliFormAction');
  const formId = document.getElementById('poliFormId');
  const title = document.getElementById('poliModalTitle');
  const fieldNama = document.getElementById('field_poli_nama');
  const fieldDeskripsi = document.getElementById('field_poli_deskripsi');

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
    title.textContent = 'Tambah Poli';
  }

  if (btnAdd) {
    btnAdd.addEventListener('click', function () {
      clearForm();
      openModal();
    });
  }
  if (btnClose) btnClose.addEventListener('click', closeModal);
  if (btnCancel) btnCancel.addEventListener('click', closeModal);

  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('modal') === 'create') {
    clearForm();
    openModal();
  }

  document.querySelectorAll('.edit-poli-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id = btn.getAttribute('data-id');
      const nama = btn.getAttribute('data-nama') || '';
      const deskripsi = btn.getAttribute('data-deskripsi') || '';
      fieldNama.value = nama;
      fieldDeskripsi.value = deskripsi;
      formAction.value = 'update';
      formId.value = id;
      title.textContent = 'Edit Poli';
      openModal();
    });
  });

  modal.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });
})();
</script>