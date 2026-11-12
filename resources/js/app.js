import '../css/app.css';
import * as bootstrap from 'bootstrap';
import { initCommon } from './common';
import { initImageSearch, initSuggestions } from './search';
import { initAssistant } from './assistant';

window.bootstrap = bootstrap;

const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');

/** Tombol − / + di samping input jumlah: <div data-qty> <button data-step="-1"> <input> <button data-step="1"> */
function initQuantitySteppers() {
    document.querySelectorAll('[data-qty]').forEach((group) => {
        const input = group.querySelector('input');
        group.querySelectorAll('[data-step]').forEach((btn) =>
            btn.addEventListener('click', () => {
                Number(btn.dataset.step) > 0 ? input.stepUp() : input.stepDown();
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }),
        );
    });
}

/** Hitung ulang ongkir di checkout (rumus sama dengan App\Enums\Courier::costFor). */
function initCheckout() {
    const form = document.getElementById('checkout-form');
    if (!form) return;

    const subtotal = Number(form.dataset.subtotal);
    const recalc = () => {
        const courier = form.querySelector('input[name=courier]:checked');
        if (!courier) return;
        const rate = Number(courier.dataset.rate);
        let shipping = 0;
        form.querySelectorAll('[data-shipping-weight]').forEach((el) => {
            const cost = Math.max(1, Math.ceil(Number(el.dataset.shippingWeight) / 1000)) * rate;
            el.textContent = rupiah(cost);
            shipping += cost;
        });
        form.querySelector('[data-sum=shipping]').textContent = rupiah(shipping);
        form.querySelector('[data-sum=total]').textContent = rupiah(subtotal + shipping);
    };

    form.addEventListener('change', (e) => e.target.name === 'courier' && recalc());
    recalc();

    // Cegah klik ganda yang membuat pesanan dobel.
    form.addEventListener('submit', () => {
        const btn = form.querySelector('[type=submit]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses…';
    });
}

initCommon();
initQuantitySteppers();
initCheckout();
initSuggestions();
initImageSearch();
initAssistant();
