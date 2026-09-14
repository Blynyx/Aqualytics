import { execFile } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'cypress';

const projectRoot = fileURLToPath(new URL('.', import.meta.url));
const phpExecutable = 'D:\\xampp\\php\\php.exe';

export default defineConfig({
    video: true,
    viewportWidth: 1280,
    viewportHeight: 720,

    e2e: {
        baseUrl: 'http://127.0.0.1:8000',

        setupNodeEvents(on) {
            on('task', {
                seedE2E() {
                    return new Promise((resolve, reject) => {
                        execFile(
                            phpExecutable,
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
