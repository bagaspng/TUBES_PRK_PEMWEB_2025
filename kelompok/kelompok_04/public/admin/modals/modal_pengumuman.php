<?php
// public/admin/modals/modal_pengumuman.php
?>

<div class="modal-overlay" id="modalOverlay"></div>
<div class="modal-content" id="modalContent">
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800" id="modalTitle">Tambah Pengumuman</h2>
            <button type="button" onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" id="announcementForm" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id_pengumuman" id="field_id_pengumuman">
            <input type="hidden" name="gambar_existing" id="field_gambar_existing">

            <div class="space-y-4">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Judul Pengumuman <span class="text-red-500">*</span></label>
                    <input type="text" name="judul" id="field_judul" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal" id="field_tanggal" required value="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Isi Pengumuman <span class="text-red-500">*</span></label>
                    <textarea name="isi" id="field_isi" required rows="5"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"></textarea>
                </div>

                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gambar</label>
                    <input type="file" name="gambar" id="field_gambar" accept="image/jpeg,image/jpg,image/png,image/gif" 
                           onchange="previewImage(event)"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, GIF. Maksimal 2MB</p>
                    <div id="imagePreview" class="mt-3 hidden">
                        <img id="previewImg" src="" alt="Preview" class="image-preview">
                        <button type="button" onclick="removeImage()" class="mt-2 text-red-600 text-sm hover:underline">Hapus gambar</button>
                    </div>
                </div>

                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" id="field_status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="draft">Draft</option>
                        <option value="publish">Publish</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="button" onclick="closeModal()" 
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-medium">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const modalOverlay = document.getElementById('modalOverlay');
const modalContent = document.getElementById('modalContent');
const announcementForm = document.getElementById('announcementForm');

function openModal(action) {
    document.getElementById('formAction').value = action;
    document.getElementById('modalTitle').textContent = action === 'create' ? 'Tambah Pengumuman' : 'Edit Pengumuman';
    
    // Reset form
    announcementForm.reset();
    document.getElementById('field_id_pengumuman').value = '';
    document.getElementById('field_gambar_existing').value = '';
    document.getElementById('field_tanggal').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('imagePreview').classList.add('hidden');
    
    modalOverlay.classList.add('active');
    modalContent.classList.add('active');
}

function closeModal() {
    modalOverlay.classList.remove('active');
    modalContent.classList.remove('active');
}

function editAnnouncement(announcement) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('modalTitle').textContent = 'Edit Pengumuman';
    
    document.getElementById('field_id_pengumuman').value = announcement.id_pengumuman;
    document.getElementById('field_judul').value = announcement.judul;
    document.getElementById('field_isi').value = announcement.isi;
    document.getElementById('field_tanggal').value = announcement.tanggal;
    document.getElementById('field_status').value = announcement.status;
    document.getElementById('field_gambar_existing').value = announcement.gambar || '';
    if (announcement.gambar) {
        document.getElementById('previewImg').src = '../../' + announcement.gambar;
        document.getElementById('imagePreview').classList.remove('hidden');
    } else {
        document.getElementById('imagePreview').classList.add('hidden');
    }
    
    modalOverlay.classList.add('active');
    modalContent.classList.add('active');
}

function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    document.getElementById('field_gambar').value = '';
    document.getElementById('field_gambar_existing').value = '';
    document.getElementById('imagePreview').classList.add('hidden');
}

modalOverlay.addEventListener('click', function(e) {
    if (e.target === modalOverlay) {
        closeModal();
    }
});

announcementForm.addEventListener('submit', function(e) {
    const judul = document.getElementById('field_judul').value.trim();
    const isi = document.getElementById('field_isi').value.trim();
    const tanggal = document.getElementById('field_tanggal').value;
    
    if (!judul || !isi || !tanggal) {
        e.preventDefault();
        alert('Semua field wajib diisi');
        return false;
    }
});

const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('modal') === 'create') {
    openModal('create');
}
</script>