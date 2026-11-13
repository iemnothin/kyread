/**
 * Animasi Lottie (resources/lottie/*.json, dipakai juga oleh aplikasi Android).
 *
 *   <div data-lottie="success" data-loop="false"> …ikon cadangan… </div>
 *
 * Isi elemen menjadi cadangan sampai animasi siap. File JSON dimuat terpisah (lazy) hanya
 * jika dipakai di halaman. Dengan prefers-reduced-motion, animasi berhenti di frame terakhir.
 */
import lottie from 'lottie-web/build/player/lottie_light';

const files = import.meta.glob('../lottie/*.json', { import: 'default' });
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

async function mount(el) {
    el.dataset.lottieReady = '1';
    const load = files[`../lottie/${el.dataset.lottie}.json`];
    if (!load) return;

    const animationData = await load();
    const fallback = [...el.childNodes];
    const anim = lottie.loadAnimation({
        container: el,
        renderer: 'svg',
        loop: el.dataset.loop !== 'false' && !reduceMotion,
        autoplay: false,
        animationData,
        rendererSettings: { preserveAspectRatio: 'xMidYMid meet', progressiveLoad: true },
    });
    el.lottie = anim;

    anim.addEventListener('DOMLoaded', () => {
        fallback.forEach((node) => node.remove());
        el.classList.add('lottie-loaded');
        if (reduceMotion) {
            anim.goToAndStop(anim.totalFrames - 1, true);
        } else if (!el.closest('[data-lottie-paused]')) {
            anim.play();
        }
    });
}

export function initLottie(root = document) {
    root.querySelectorAll('[data-lottie]:not([data-lottie-ready])').forEach(mount);
}

/** Jalankan/hentikan animasi di dalam elemen (mis. loading screen yang disembunyikan). */
export function setLottiePlaying(container, playing) {
    if (!container) return;
    playing ? container.removeAttribute('data-lottie-paused') : container.setAttribute('data-lottie-paused', '');
    container.querySelectorAll('[data-lottie]').forEach((el) => {
        if (!el.lottie || reduceMotion) return;
        playing ? el.lottie.goToAndPlay(0, true) : el.lottie.pause();
    });
}
