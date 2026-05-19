#!/usr/bin/env node
const { execSync } = require('child_process');
const path = require('path');
const md5 = require('../node_modules/@wordpress/env/lib/md5');

function run(cmd) {
    try {
        return execSync(cmd, { stdio: 'pipe' }).toString().trim();
    } catch (_) {
        return '';
    }
}

const configFile = path.join(path.resolve(__dirname, '..'), '.wp-env.json');
const hash = md5(configFile);

execSync('npx wp-env stop', { stdio: 'inherit' });

const toRemove = run(`docker ps -aq --filter name=${hash}`).split('\n').filter(Boolean);
if (toRemove.length === 0) {
    console.log('✓ No containers to remove');
    process.exit(0);
}

execSync(`docker rm -f ${toRemove.join(' ')}`, { stdio: 'inherit' });
console.log(`✓ Removed ${toRemove.length} container(s)`);
