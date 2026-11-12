/** Saran kata per huruf & cari dengan gambar di kolom pencarian header. */

const csrf = () => document.querySelector('meta[name=csrf-token]')?.content ?? '';
const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
const escapeHtml = (s) =>
    String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

const ICONS = { title: 'bi-book', author: 'bi-person', category: 'bi-tag' };

export function initSuggestions() {
    const form = document.querySelector('form[data-suggest-url]');
    if (!form) return;

    const input = form.querySelector('input[name=q]');
    const list = form.querySelector('.search-suggestions');
    let timer;
    let controller;
    let active = -1;

    const close = () => {
        list.classList.remove('show');
        input.setAttribute('aria-expanded', 'false');
        active = -1;
    };

    const highlight = (label, q) => {
        const i = label.toLowerCase().indexOf(q.toLowerCase());
        if (i < 0) return escapeHtml(label);
        return escapeHtml(label.slice(0, i)) + '<mark class="p-0 bg-transparent fw-bold">' +
            escapeHtml(label.slice(i, i + q.length)) + '</mark>' + escapeHtml(label.slice(i + q.length));
    };

    const urlFor = (s) => {
        if (s.type === 'title' && s.slug) return `/books/${encodeURIComponent(s.slug)}`;
        if (s.type === 'category' && s.slug) return `/books?category=${encodeURIComponent(s.slug)}`;
        return `/books?q=${encodeURIComponent(s.value)}`;
    };

    const render = (items, q) => {
        if (!items.length) return close();
        list.innerHTML = items
            .map(
                (s, i) => `<li><a class="dropdown-item d-flex align-items-center gap-2 py-2" role="option" id="sugg-${i}" href="${urlFor(s)}">
                    <i class="bi ${ICONS[s.type] ?? 'bi-search'} text-secondary"></i>
                    <span class="text-truncate">${highlight(s.label, q)}</span>
                    <small class="ms-auto text-secondary">${s.type === 'author' ? 'Penulis' : s.type === 'category' ? 'Kategori' : ''}</small>
                </a></li>`,
            )
            .join('');
        list.classList.add('show');
        input.setAttribute('aria-expanded', 'true');
        active = -1;
    };

    // Setiap huruf yang diketik memicu permintaan baru (debounce 150 ms, permintaan lama dibatalkan).
    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (!q) return close();
        timer = setTimeout(async () => {
            controller?.abort();
            controller = new AbortController();
            try {
                const res = await fetch(`${form.dataset.suggestUrl}?q=${encodeURIComponent(q)}`, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (res.ok && input.value.trim() === q) render((await res.json()).data, q);
            } catch {
                /* dibatalkan atau offline: abaikan */
            }
        }, 150);
    });

    input.addEventListener('keydown', (e) => {
        const items = [...list.querySelectorAll('a')];
        if (!list.classList.contains('show') || !items.length) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            active = (active + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
            items.forEach((a, i) => a.classList.toggle('active', i === active));
            input.setAttribute('aria-activedescendant', items[active].id);
        } else if (e.key === 'Enter' && active >= 0) {
            e.preventDefault();
            window.location.href = items[active].href;
        } else if (e.key === 'Escape') {
            close();
        }
    });

    document.addEventListener('click', (e) => !form.contains(e.target) && close());
}

export function initImageSearch() {
    const input = document.getElementById('imageSearchInput');
    const modalEl = document.getElementById('imageSearchModal');
    if (!input || !modalEl) return;

    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    const status = modalEl.querySelector('[data-status]');
    const results = modalEl.querySelector('[data-results]');
    const preview = modalEl.querySelector('[data-preview]');
    const allLink = modalEl.querySelector('[data-all]');

    document.querySelectorAll('[data-image-search]').forEach((btn) => btn.addEventListener('click', () => input.click()));
    modalEl.querySelector('[data-retake]').addEventListener('click', () => input.click());

    input.addEventListener('change', async () => {
        const [file] = input.files;
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran foto maksimal 5 MB.');
            return;
        }

        preview.src = URL.createObjectURL(file);
        results.innerHTML = '';
        allLink.classList.add('d-none');
        status.innerHTML = '<div class="d-flex align-items-center gap-2 text-secondary"><span class="spinner-border spinner-border-sm"></span> Mengenali buku dari foto…</div>';
        modal.show();

        const body = new FormData();
        body.append('image', file);
        input.value = '';

        try {
            const res = await fetch(input.dataset.url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                body,
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.errors?.image?.[0] ?? json.message ?? 'Gagal memproses foto.');

            const id = json.identified;
            status.innerHTML = `
                <div class="small text-secondary">Terdeteksi</div>
                <div class="fw-bold">${escapeHtml(id.title ?? 'Judul tidak terbaca')}</div>
                ${id.author ? `<div class="text-secondary">${escapeHtml(id.author)}</div>` : ''}
                <div class="small mt-1">${escapeHtml(id.description)}</div>
                <div class="mt-2">${id.keywords.map((k) => `<span class="badge text-bg-light border me-1">${escapeHtml(k)}</span>`).join('')}</div>`;

            results.innerHTML = json.data.length
                ? json.data
                      .map(
                          (p) => `<div class="col"><a class="card book-card text-decoration-none text-body h-100" href="/books/${encodeURIComponent(p.slug)}">
                            <div class="cover"><img src="${escapeHtml(p.image_url)}" alt=""></div>
                            <div class="card-body p-2"><div class="title small">${escapeHtml(p.title)}</div>
                            <div class="price small">${rupiah(p.price)}</div></div></a></div>`,
                      )
                      .join('')
                : '<p class="text-secondary mb-0">Belum ada buku yang cocok di katalog. Coba foto lain atau cari dengan kata kunci di atas.</p>';

            if (json.query) {
                allLink.href = `/books?q=${encodeURIComponent(json.query)}`;
                allLink.classList.remove('d-none');
            }
        } catch (err) {
            status.innerHTML = `<div class="alert alert-danger mb-0 py-2">${escapeHtml(err.message)}</div>`;
        }
    });
}
