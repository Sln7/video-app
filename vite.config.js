import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
            protocol: 'wss',
            port: 443,
            // Faz o laravel-vite-plugin escrever https://localhost:443 no hot file,
            // então o @vite do Blade gera URLs que passam pelo Caddy (não pela 5173 direta).
            clientPort: 443,
        },
    },
});
