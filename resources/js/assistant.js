/** Halaman chat "Tanya Kyra". Riwayat disimpan di sessionStorage agar bertahan saat berpindah halaman. */

const STORAGE_KEY = 'kyra-chat';
const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
const escapeHtml = (s) =>
    String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

export function initAssistant() {
    const root = document.getElementById('assistant');
    if (!root) return;

    const log = root.querySelector('[data-log]');
    const form = root.querySelector('form');
    const input = form.querySelector('textarea');
    const sendBtn = form.querySelector('[type=submit]');

    /** @type {{role: 'user'|'assistant', content: string, products?: any[]}[]} */
    let messages = [];
    try {
        messages = JSON.parse(sessionStorage.getItem(STORAGE_KEY) ?? '[]');
    } catch {
        messages = [];
    }

    const save = () => {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(messages.slice(-30)));
        } catch {
            /* penyimpanan tidak tersedia */
        }
    };

    const productCards = (products = []) =>
        products.length
            ? `<div class="d-flex gap-2 overflow-auto pb-1 mt-2">${products
                  .map(
                      (p) => `<a class="card book-card text-decoration-none text-body flex-shrink-0" style="width: 140px" href="/books/${encodeURIComponent(p.slug)}">
                        <div class="cover"><img src="${escapeHtml(p.image_url)}" alt="" loading="lazy"></div>
                        <div class="card-body p-2"><div class="title small">${escapeHtml(p.title)}</div>
                        <div class="price small">${rupiah(p.price)}</div></div></a>`,
                  )
                  .join('')}</div>`
            : '';

    const bubble = (m) =>
        m.role === 'user'
            ? `<div class="d-flex justify-content-end mb-3"><div class="chat-bubble chat-user">${escapeHtml(m.content)}</div></div>`
            : `<div class="d-flex gap-2 mb-3"><div class="chat-avatar"><i class="bi bi-stars"></i></div>
                <div class="flex-grow-1 min-w-0"><div class="chat-bubble chat-bot">${escapeHtml(m.content)}</div>${productCards(m.products)}</div></div>`;

    const render = (pending = false) => {
        log.querySelectorAll('[data-msg]').forEach((el) => el.remove());
        log.insertAdjacentHTML(
            'beforeend',
            messages.map((m) => `<div data-msg>${bubble(m)}</div>`).join('') +
                (pending
                    ? '<div data-msg class="d-flex gap-2 mb-3"><div class="chat-avatar"><i class="bi bi-stars"></i></div><div class="chat-bubble chat-bot text-secondary"><span class="spinner-grow spinner-grow-sm me-1"></span>Kyra sedang mengetik…</div></div>'
                    : ''),
        );
        root.querySelector('[data-intro]')?.classList.toggle('d-none', messages.length > 0);
        log.scrollTop = log.scrollHeight;
    };

    const send = async (text) => {
        const content = text.trim();
        if (!content) return;
        messages.push({ role: 'user', content });
        input.value = '';
        sendBtn.disabled = true;
        render(true);

        try {
            const res = await fetch(root.dataset.url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({ messages: messages.map(({ role, content }) => ({ role, content })) }),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message ?? 'Kyra gagal menjawab.');
            messages.push({ role: 'assistant', content: json.reply, products: json.products });
        } catch (err) {
            messages.pop(); // biarkan pengguna mengirim ulang
            input.value = content;
            alert(err.message);
        } finally {
            sendBtn.disabled = false;
            save();
            render();
            input.focus();
        }
    };

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        send(input.value);
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.requestSubmit();
        }
    });
    root.querySelectorAll('[data-prompt]').forEach((btn) => btn.addEventListener('click', () => send(btn.textContent)));
    root.querySelector('[data-reset]')?.addEventListener('click', () => {
        messages = [];
        save();
        render();
    });

    render();
}
