#!/usr/bin/env node
const { execSync } = require('child_process');

function run(cmd) {
    try {
        return execSync(cmd, { stdio: 'pipe' }).toString().trim();
    } catch (_) {
        return '';
    }
}

function findWpEnvContainers() {
    return run('docker ps -aq')
        .split('\n')
        .filter(id => {
            if (!id) return false;
            const name = run(`docker inspect --format {{.Name}} ${id}`).replace(/^\//, '');
            return /^[a-f0-9]{32}-(mysql|wordpress|cli|tests)/.test(name);
        });
}

const toStop = findWpEnvContainers();
if (toStop.length === 0) {
    console.log('✓ No wp-env containers running');
    process.exit(0);
}

execSync(`docker rm -f ${toStop.join(' ')}`, { stdio: 'inherit' });
console.log(`✓ Removed ${toStop.length} container(s)`);
