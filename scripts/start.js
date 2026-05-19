#!/usr/bin/env node
const { execSync, spawnSync } = require('child_process');

function run(cmd) {
    try {
        return execSync(cmd, { stdio: 'pipe' }).toString().trim();
    } catch (_) {
        return '';
    }
}

function removeWpEnvContainers() {
    const toRemove = run('docker ps -aq')
        .split('\n')
        .filter(id => {
            if (!id) return false;
            const name = run(`docker inspect --format {{.Name}} ${id}`).replace(/^\//, '');
            return /^[a-f0-9]{32}-(mysql|wordpress|cli|tests)/.test(name);
        });

    if (toRemove.length > 0) {
        execSync(`docker rm -f ${toRemove.join(' ')}`, { stdio: 'pipe' });
        console.log(`✓ Removed ${toRemove.length} existing container(s)`);
    }
}

function sleep(ms) {
    Atomics.wait(new Int32Array(new SharedArrayBuffer(4)), 0, 0, ms);
}

function isDevUp() {
    return run('docker ps --filter publish=8890 --format {{.Names}}').trim() !== '';
}

const alreadyUp = isDevUp();

if (alreadyUp) {
    console.log('Dev WordPress already running → http://localhost:8890');
} else {
    removeWpEnvContainers();

    const MAX_ATTEMPTS = 5;

    for (let attempt = 1; attempt <= MAX_ATTEMPTS; attempt++) {
        console.log(`\nStarting wp-env (attempt ${attempt}/${MAX_ATTEMPTS})…`);
        const result = spawnSync('npx', ['wp-env', 'start'], {
            stdio: 'inherit',
            shell: true,
            env: { ...process.env, NODE_OPTIONS: '--use-system-ca' },
        });

        if (result.status === 0) break;

        if (isDevUp()) {
            console.log('Dev WordPress is up — skipping retry (tests env issue, non-critical).');
            break;
        }

        if (attempt === MAX_ATTEMPTS) {
            console.error('\nwp-env failed after all attempts. Run: npm run env:clean');
            process.exit(1);
        }

        console.log('Dev not up yet, waiting 20s for containers to initialize…');
        sleep(20_000);
    }

    execSync('node scripts/setup-apache.js', { stdio: 'inherit' });
}
