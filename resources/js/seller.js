import * as bootstrap from 'bootstrap';
import ApexCharts from 'apexcharts';
import { initCommon, initPageLoader, initRipple } from './common';
import { t } from './i18n';

window.bootstrap = bootstrap;

const select = (sel) => document.querySelector(sel);

// --- Perilaku layout NiceAdmin (hanya bagian yang dipakai) -------------------
select('.toggle-sidebar-btn')?.addEventListener('click', () => document.body.classList.toggle('toggle-sidebar'));
select('.search-bar-toggle')?.addEventListener('click', (e) => {
    e.preventDefault();
    select('.search-bar')?.classList.toggle('search-bar-show');
});

const header = select('#header');
const backToTop = select('.back-to-top');
const onScroll = () => {
    header?.classList.toggle('header-scrolled', window.scrollY > 100);
    backToTop?.classList.toggle('active', window.scrollY > 100);
};
window.addEventListener('scroll', onScroll, { passive: true });
onScroll();

document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));

// --- Grafik penjualan dashboard ---------------------------------------------
const chartEl = select('#salesChart');
if (chartEl) {
    const data = JSON.parse(chartEl.dataset.chart);
    new ApexCharts(chartEl, {
        chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'Barlow, sans-serif' },
        series: [{ name: t('Penjualan'), data: data.map((d) => d.total) }],
        xaxis: { categories: data.map((d) => d.date) },
        yaxis: { labels: { formatter: (v) => t('Rp :amount rb', { amount: Math.round(v / 1000) }) } },
        tooltip: { y: { formatter: (v) => 'Rp ' + v.toLocaleString('id-ID') } },
        colors: ['#43aaff'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
        noData: { text: t('Belum ada penjualan') },
    }).render();
}

initCommon();
initRipple();
initPageLoader();
