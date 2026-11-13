/** Perilaku yang dipakai storefront dan panel penjual. */
import { t } from './i18n';
import { setLottiePlaying } from './lottie';
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
                b.title = wishlisted ? t('Hapus dari wishlist') : t('Simpan ke wishlist');
                const icon = b.querySelector('i');
                icon.className = `bi ${wishlisted ? 'bi-heart-fill' : 'bi-heart'}`;
                const label = b.querySelector('span');
                if (label) label.textContent = wishlisted ? t('Tersimpan') : t('Wishlist');
            });
        } catch {
            form.submit(); // fallback: kirim biasa
        } finally {
            btn.disabled = false;
        }
    });
}

/** Efek riak pada setiap tombol .btn. */
export function initRipple() {
    document.addEventListener('pointerdown', (e) => {
        const btn = e.target.closest('.btn');
        if (!btn || btn.disabled || btn.classList.contains('disabled')) return;

        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const ripple = document.createElement('span');
        ripple.className = 'ripple';
        ripple.style.width = ripple.style.height = `${size}px`;
        ripple.style.left = `${e.clientX - rect.left - size / 2}px`;
        ripple.style.top = `${e.clientY - rect.top - size / 2}px`;
        btn.appendChild(ripple);
        ripple.addEventListener('animationend', () => ripple.remove());
    });
}

/**
 * Loading screen halaman: disembunyikan setelah halaman siap, ditampilkan lagi saat
 * pengguna berpindah halaman (klik tautan / kirim form). Tombol submit diberi spinner.
 */
export function initPageLoader() {
    const html = document.documentElement;
    const loader = document.getElementById('page-loader');
    const done = () => {
        html.classList.add('is-loaded');
        setLottiePlaying(loader, false); // jangan memutar animasi yang tidak terlihat
    };
    document.readyState === 'complete' ? done() : window.addEventListener('load', done);
    setTimeout(done, 2500); // jangan menunggu gambar yang lambat terlalu lama

    // Kembali lewat tombol Back (bfcache): pastikan loader & tombol kembali normal.
    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        html.classList.remove('is-navigating');
        document.querySelectorAll('.btn.is-loading').forEach(resetButton);
    });

    const navigating = () => {
        html.classList.add('is-navigating');
        setLottiePlaying(loader, true);
    };

    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href]');
        if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (a.target && a.target !== '_self') return;
        if (a.hasAttribute('download') || a.dataset.bsToggle || a.dataset.noLoader !== undefined) return;

        const url = new URL(a.href, location.href);
        if (url.origin !== location.origin) return;
        if (url.pathname === location.pathname && url.search === location.search && url.hash) return; // anchor di halaman yang sama
        navigating();
    });

    // Didaftarkan setelah handler data-confirm, jadi form yang dibatalkan tidak memicu loader.
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (e.defaultPrevented || form.target === '_blank' || form.dataset.noLoader !== undefined) return;

        const btn = e.submitter ?? form.querySelector('[type=submit]');
        if (btn && btn.classList.contains('btn') && !btn.classList.contains('is-loading')) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.style.width = `${btn.offsetWidth}px`;
            btn.classList.add('is-loading');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';
        }
        navigating();
    });
}

function resetButton(btn) {
    btn.classList.remove('is-loading');
    btn.style.width = '';
    if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
}
