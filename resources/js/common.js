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

/** Tombol wishlist (<form data-wishlist>) dikirim lewat fetch agar halaman tidak reload. */
export function initWishlist() {
    const token = document.querySelector('meta[name=csrf-token]')?.content;

    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('form[data-wishlist]');
        if (!form) return;
        e.preventDefault();

        const btn = form.querySelector('button');
        btn.disabled = true;
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
            });
            if (!res.ok) throw new Error(res.status);
            const { wishlisted } = await res.json();

            // Samakan semua tombol untuk buku yang sama di halaman ini.
            document.querySelectorAll(`form[data-wishlist][action="${form.action}"] button`).forEach((b) => {
                b.classList.toggle('active', wishlisted);
                b.setAttribute('aria-pressed', String(wishlisted));
                b.title = wishlisted ? 'Hapus dari wishlist' : 'Simpan ke wishlist';
                const icon = b.querySelector('i');
                icon.className = `bi ${wishlisted ? 'bi-heart-fill' : 'bi-heart'}`;
                const label = b.querySelector('span');
                if (label) label.textContent = wishlisted ? 'Tersimpan' : 'Wishlist';
            });
        } catch {
            form.submit(); // fallback: kirim biasa
        } finally {
            btn.disabled = false;
        }
    });
}
