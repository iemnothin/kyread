import * as bootstrap from 'bootstrap';
import { initCommon, initPageLoader, initRipple, initWishlist } from './common';
import { initImageSearch, initSuggestions } from './search';
import { initAssistant } from './assistant';
import { t } from './i18n';
import { initLottie } from './lottie';

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
    let discount = 0;
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
        form.querySelector('[data-sum=discount]').textContent = '−' + rupiah(discount);
        form.querySelector('[data-voucher-row]').classList.toggle('d-none', discount === 0);
        form.querySelector('[data-sum=total]').textContent = rupiah(subtotal + shipping - discount);
    };

    form.addEventListener('change', (e) => e.target.name === 'courier' && recalc());

    // Voucher: cek ke server, tampilkan potongan. Validasi akhir tetap dilakukan saat pesanan dibuat.
    const input = form.querySelector('#voucher_code');
    const applyBtn = form.querySelector('[data-voucher-apply]');
    const errorEl = form.querySelector('[data-voucher-error]');
    const okEl = form.querySelector('[data-voucher-ok]');
    const token = document.querySelector('meta[name=csrf-token]')?.content;

    const applyVoucher = async () => {
        const code = input.value.trim();
        discount = 0;
        input.classList.remove('is-invalid');
        okEl.classList.add('d-none');
        if (!code) return recalc();

        applyBtn.disabled = true;
        try {
            const res = await fetch(form.dataset.voucherUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ code }),
            });
            const data = await res.json();
            if (!res.ok) {
                errorEl.textContent = data.errors?.voucher_code?.[0] ?? data.message ?? t('Voucher tidak bisa dipakai.');
                input.classList.add('is-invalid');
            } else {
                discount = data.discount;
                input.value = data.code;
                okEl.textContent = `${data.message} (${data.store_name})`;
                okEl.classList.remove('d-none');
            }
        } catch {
            errorEl.textContent = t('Gagal memeriksa voucher. Coba lagi.');
            input.classList.add('is-invalid');
        } finally {
            applyBtn.disabled = false;
            recalc();
        }
    };

    applyBtn.addEventListener('click', applyVoucher);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyVoucher();
        }
    });
    input.addEventListener('input', () => {
        if (discount) {
            discount = 0;
            okEl.classList.add('d-none');
            recalc();
        }
    });

    recalc();
    if (input.value.trim()) applyVoucher();

}

initCommon();
initWishlist();
initRipple();
initPageLoader();
initLottie();
initQuantitySteppers();
initCheckout();
initSuggestions();
initImageSearch();
initAssistant();
