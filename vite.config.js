import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // Storefront & panel penjual punya bundle terpisah agar halaman toko tidak ikut memuat ApexCharts.
            input: ['resources/js/app.js', 'resources/js/seller.js'],
            refresh: true,
        }),
    ],
    build: {
        // Bootstrap/ApexCharts memang besar; peringatan default 500 kB tidak relevan di sini.
        chunkSizeWarningLimit: 900,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
