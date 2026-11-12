/** Perilaku yang dipakai storefront dan panel penjual. */
export function initCommon() {
    // <form data-confirm="Yakin?"> meminta konfirmasi sebelum dikirim.
    document.addEventListener('submit', (e) => {
        const msg = e.target.dataset.confirm;
        if (msg && !window.confirm(msg)) e.preventDefault();
    });

    // <select data-autosubmit> / <input data-autosubmit> mengirim form saat nilainya berubah.
    document.addEventListener('change', (e) => {
        if (e.target.matches('[data-autosubmit]')) e.target.form?.requestSubmit();
    });

    // <input type="file" data-preview="#img"> menampilkan pratinjau gambar sebelum diunggah.
    document.querySelectorAll('input[type=file][data-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const [file] = input.files;
            const target = document.querySelector(input.dataset.preview);
            if (file && target) target.src = URL.createObjectURL(file);
        });
    });

    // Alert sukses hilang sendiri setelah 5 detik.
    document.querySelectorAll('.alert-success.alert-dismissible').forEach((el) => {
        setTimeout(() => window.bootstrap?.Alert.getOrCreateInstance(el).close(), 5000);
    });
}
