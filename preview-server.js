import path from 'node:path';
import { fileURLToPath } from 'node:url';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import vueJsx from '@vitejs/plugin-vue-jsx';
import { createServer } from 'vite';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const server = await createServer({
    configFile: false,
    root: __dirname,
    plugins: [
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        vueJsx(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    server: {
        port: 3000,
        host: 'localhost',
        strictPort: false,
    },
});

await server.listen();
server.printUrls();
