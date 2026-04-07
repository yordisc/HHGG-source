import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const vitePublicUrl = process.env.VITE_PUBLIC_URL;

const server = {};

if (vitePublicUrl) {
    const publicUrl = new URL(vitePublicUrl);

    server.origin = vitePublicUrl;
    server.hmr = {
        protocol: publicUrl.protocol === 'https:' ? 'wss' : 'ws',
        host: publicUrl.hostname,
        clientPort: publicUrl.port
            ? Number(publicUrl.port)
            : (publicUrl.protocol === 'https:' ? 443 : 80),
    };
}

export default defineConfig({
    server,
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
