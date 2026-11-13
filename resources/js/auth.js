/**
 * Halaman Masuk/Daftar yang interaktif:
 * tab tanpa reload, maskot buku yang matanya mengikuti ketikan dan menutup mata saat
 * password diisi, cek username/email langsung, meteran kekuatan password, Caps Lock.
 */
export function initAuth() {
    const root = document.getElementById('auth');
    if (!root) return;

    const mascot = root.querySelector('[data-mascot]');
    const bubble = root.querySelector('[data-mascot-bubble]');
    const pupils = mascot?.querySelector('.mascot-pupils');
    const mouth = mascot?.querySelector('.mascot-mouth');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // --- Maskot -----------------------------------------------------------------------
    const say = (text) => {
        if (!bubble || !text || bubble.textContent === text) return;
        bubble.classList.remove('pop');
        bubble.textContent = text;
        void bubble.offsetWidth; // restart animasi
        bubble.classList.add('pop');
    };
    const mood = (name) => {
        mascot?.classList.remove('is-sad', 'is-happy', 'is-shy');
        if (name) mascot?.classList.add(`is-${name}`);
        mouth?.setAttribute('d', name === 'sad' ? mouth.dataset.sad : mouth.dataset.happy);
    };
    const look = (x, y) => pupils?.setAttribute('transform', `translate(${x.toFixed(1)} ${y.toFixed(1)})`);
    const defaultMessage = () => bubble?.dataset[root.dataset.tab === 'login' ? 'msgLogin' : 'msgRegister'];

    // Mata mengikuti kursor/jari di layar.
    let typing = false;
    window.addEventListener('pointermove', (e) => {
        if (typing || !mascot) return;
        const box = mascot.getBoundingClientRect();
        const dx = e.clientX - (box.left + box.width / 2);
        const dy = e.clientY - (box.top + box.height * 0.45);
        const dist = Math.hypot(dx, dy) || 1;
        const reach = Math.min(1, dist / 300);
        look((dx / dist) * 6 * reach, (dy / dist) * 5 * reach);
    });

    // Saat mengetik: mata membaca dari kiri ke kanan sesuai posisi kursor teks.
    const followCaret = (input) => {
        const ratio = Math.min(1, (input.selectionStart ?? input.value.length) / 28);
        const rtl = document.documentElement.dir === 'rtl';
        look((rtl ? 1 - ratio : ratio) * 12 - 6, 5);
    };

    // Setelah gagal, maskot tetap sedih sampai pengguna mulai mengetik lagi (autofocus tidak menghapusnya).
    let sad = root.classList.contains('is-error');
    root.addEventListener('input', () => (sad = false));

    root.querySelectorAll('[data-watch]').forEach((input) => {
        input.addEventListener('focus', () => {
            typing = true;
            if (!sad) mood(null);
            followCaret(input);
        });
        ['input', 'click', 'keyup'].forEach((ev) => input.addEventListener(ev, () => followCaret(input)));
        input.addEventListener('blur', () => (typing = false));
    });

    // Password: maskot menutup mata dengan tangannya (kecuali password sedang ditampilkan).
    const secrets = root.querySelectorAll('[data-secret]');
    secrets.forEach((input) => {
        input.addEventListener('focus', () => {
            mood(input.type === 'password' ? 'shy' : null);
            say(bubble?.dataset.msgPassword);
        });
        input.addEventListener('blur', () => {
            mood(null);
            say(defaultMessage());
        });
    });

    // Sapa dengan nama yang diketik.
    root.querySelector('[data-greet]')?.addEventListener('input', (e) => {
        const name = e.target.value.trim().split(/\s+/)[0];
        say(name ? bubble.dataset.msgName.replace(':name', name) : defaultMessage());
        if (name) mood('happy');
    });

    // --- Tab Masuk/Daftar tanpa reload ----------------------------------------------------
    const tabs = root.querySelectorAll('[data-auth-tab]');
    const indicator = root.querySelector('.auth-tabs-indicator');
    const moveIndicator = () => {
        const active = root.querySelector('[data-auth-tab].active');
        if (!active || !indicator) return;
        indicator.style.width = `${active.offsetWidth}px`;
        indicator.style.transform = `translateX(${active.offsetLeft}px)`;
    };
    const showTab = (name, push) => {
        if (root.dataset.tab === name) return;
        root.dataset.tab = name;
        tabs.forEach((t) => {
            const on = t.dataset.authTab === name;
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', String(on));
        });
        root.querySelectorAll('[data-auth-panel]').forEach((panel) => {
            const on = panel.dataset.authPanel === name;
            panel.hidden = !on;
            if (on && !reduceMotion) {
                panel.classList.remove('enter');
                void panel.offsetWidth;
                panel.classList.add('enter');
            }
        });
        moveIndicator();
        mood(null);
        say(defaultMessage());
        document.title = root.dataset[name === 'login' ? 'titleLogin' : 'titleRegister'];
        if (push) history.pushState({ tab: name }, '', root.querySelector(`[data-auth-tab="${name}"]`).href);
        root.querySelector(`[data-auth-panel="${name}"] input:not([type=hidden])`)?.focus({ preventScroll: true });
    };
    tabs.forEach((t) =>
        t.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation(); // jangan memicu loading screen
            showTab(t.dataset.authTab, true);
        }),
    );
    window.addEventListener('popstate', () => showTab(location.pathname.endsWith('/register') ? 'register' : 'login', false));
    window.addEventListener('resize', moveIndicator);
    requestAnimationFrame(() => {
        indicator?.classList.add('no-anim');
        moveIndicator();
        requestAnimationFrame(() => indicator?.classList.remove('no-anim'));
    });

    // --- Lihat/sembunyikan password & Caps Lock --------------------------------------------
    root.querySelectorAll('[data-pw-toggle]').forEach((btn) => {
        const input = btn.parentElement.querySelector('input');
        btn.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('i').className = `bi ${show ? 'bi-eye-slash' : 'bi-eye'}`;
            btn.setAttribute('aria-label', show ? btn.dataset.labelHide : btn.dataset.labelShow);
            mood(show ? null : 'shy'); // password terlihat: maskot boleh ikut melihat
            input.focus();
        });
    });
    root.querySelectorAll('[data-secret]').forEach((input) => {
        const hint = input.closest('form').querySelector('[data-caps]');
        const check = (e) => hint && (hint.hidden = !e.getModifierState?.('CapsLock'));
        input.addEventListener('keydown', check);
        input.addEventListener('keyup', check);
        input.addEventListener('blur', () => hint && (hint.hidden = true));
    });

    // --- Cek username/email langsung ke server -----------------------------------------------
    root.querySelectorAll('[data-check]').forEach((input) => {
        const bad = input.parentElement.querySelector('[data-feedback]');
        const ok = input.parentElement.querySelector('[data-feedback-ok]');
        let timer;
        let controller;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            controller?.abort();
            input.classList.remove('is-valid', 'is-invalid');
            const value = input.value.trim();
            if (!value) return;
            timer = setTimeout(async () => {
                controller = new AbortController();
                try {
                    const url = new URL(root.dataset.checkUrl);
                    url.searchParams.set('field', input.dataset.check);
                    url.searchParams.set('value', value);
                    const res = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
                    if (!res.ok) return;
                    const data = await res.json();
                    input.classList.toggle('is-valid', data.ok);
                    input.classList.toggle('is-invalid', !data.ok);
                    (data.ok ? ok : bad).textContent = data.message;
                    mood(data.ok ? 'happy' : 'sad');
                } catch {
                    /* dibatalkan / offline: abaikan */
                }
            }, 400);
        });
    });

    // --- Kekuatan password -----------------------------------------------------------------
    const strength = root.querySelector('[data-strength]');
    const pwInput = root.querySelector('[data-strength-input]');
    const labels = strength ? JSON.parse(strength.dataset.labels) : [];
    const updateStrength = () => {
        const v = pwInput.value;
        const rules = { length: v.length >= 8, letter: /\p{L}/u.test(v), number: /\d/.test(v) };
        let score = Object.values(rules).filter(Boolean).length;
        if (score === 3 && (v.length >= 12 || /[^\p{L}\d]/u.test(v))) score = 4; // bonus: panjang / simbol
        const level = v ? Math.max(1, Math.min(3, score - (score === 4 ? 1 : 0))) : 0;

        strength.dataset.level = String(level);
        strength.querySelector('[data-strength-label]').textContent = v ? labels[level - 1] : '';
        for (const [rule, met] of Object.entries(rules)) {
            const li = strength.querySelector(`[data-rule="${rule}"]`);
            li.classList.toggle('met', met);
            li.querySelector('i').className = `bi ${met ? 'bi-check-circle-fill' : 'bi-circle'}`;
        }
        if (Object.values(rules).every(Boolean)) mood('happy');
        updateMatch();
    };

    // --- Konfirmasi password ---------------------------------------------------------------
    const confirm = root.querySelector('[data-confirm-for]');
    const updateMatch = () => {
        if (!confirm || !confirm.value) return confirm?.classList.remove('is-valid', 'is-invalid');
        const same = confirm.value === pwInput.value;
        confirm.classList.toggle('is-valid', same);
        confirm.classList.toggle('is-invalid', !same);
    };
    pwInput?.addEventListener('input', updateStrength);
    confirm?.addEventListener('input', updateMatch);

    // --- Status error dari server: kartu bergetar & maskot sedih ----------------------------
    if (root.classList.contains('is-error')) {
        mood('sad');
        say(bubble?.dataset.msgError);
        if (!reduceMotion) {
            root.classList.add('shake');
            root.addEventListener('animationend', () => root.classList.remove('shake'), { once: true });
        }
    }

    // Kedipan sesekali.
    if (!reduceMotion) {
        const blink = () => {
            mascot?.classList.add('blink');
            setTimeout(() => mascot?.classList.remove('blink'), 160);
            setTimeout(blink, 2500 + Math.random() * 3000);
        };
        setTimeout(blink, 2000);
    }

    // Parallax latar: dekorasi bergeser sedikit mengikuti kursor.
    const bg = document.querySelector('[data-auth-bg]');
    if (bg && !reduceMotion) {
        let frame;
        window.addEventListener('pointermove', (e) => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => {
                bg.style.setProperty('--px', ((e.clientX / innerWidth) * 2 - 1).toFixed(3));
                bg.style.setProperty('--py', ((e.clientY / innerHeight) * 2 - 1).toFixed(3));
            });
        });
    }

    // Tagline bergantian.
    const lines = root.querySelectorAll('[data-taglines] li');
    if (lines.length > 1 && !reduceMotion) {
        let i = 0;
        setInterval(() => {
            lines[i].classList.remove('active');
            i = (i + 1) % lines.length;
            lines[i].classList.add('active');
        }, 3200);
    }
}
