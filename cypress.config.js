import { existsSync } from 'node:fs';
import { execFile } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'cypress';

const projectRoot = fileURLToPath(new URL('.', import.meta.url));
const xamppPhp = 'D:\\xampp\\php\\php.exe';

function resolvePhpExecutable() {
    if (process.env.PHP_BINARY) {
        return process.env.PHP_BINARY;
    }

    if (process.platform === 'win32' && existsSync(xamppPhp)) {
        return xamppPhp;
    }

    return 'php';
}

export default defineConfig({
    video: true,
    viewportWidth: 1280,
    viewportHeight: 720,

    e2e: {
        baseUrl: process.env.CYPRESS_BASE_URL || 'http://127.0.0.1:8000',

        setupNodeEvents(on) {
            on('task', {
                seedE2E() {
                    return new Promise((resolve, reject) => {
                        execFile(
                            resolvePhpExecutable(),
                            ['artisan', 'db:seed', '--class=E2ETestSeeder'],
                            { cwd: projectRoot },
                            (error, stdout, stderr) => {
                                if (error) {
                                    reject(new Error(stderr || error.message));

                                    return;
                                }

                                resolve(stdout);
                            },
                        );
                    });
                },
            });
        },
    },
});
