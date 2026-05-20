#!/usr/bin/env node
/**
 * Interactive release script.
 * Usage: node scripts/release.js
 *
 * 1. Asks: patch / minor / major
 * 2. Bumps version in campsflow.php
 * 3. Runs tests + static analysis
 * 4. Builds ZIP via Docker
 * 5. Commits, tags, pushes
 */

const { execSync, spawnSync } = require('child_process');
const fs      = require('fs');
const path    = require('path');
const readline = require('readline');

const ROOT    = path.resolve(__dirname, '..');
const MAIN    = path.join(ROOT, 'campsflow.php');

// ── Helpers ───────────────────────────────────────────────────────────────────

function run(cmd, opts = {}) {
    console.log(`\n> ${cmd}`);
    execSync(cmd, { stdio: 'inherit', cwd: ROOT, ...opts });
}

function readVersion() {
    const src = fs.readFileSync(MAIN, 'utf8');
    const m = src.match(/define\('CAMPSFLOW_VERSION',\s*'([^']+)'\)/);
    if (!m) throw new Error('CAMPSFLOW_VERSION not found');
    return m[1];
}

function bumpVersion(current, type) {
    const [maj, min, pat] = current.split('.').map(Number);
    if (type === 'major') return `${maj + 1}.0.0`;
    if (type === 'minor') return `${maj}.${min + 1}.0`;
    return `${maj}.${min}.${pat + 1}`;
}

function writeVersion(newVer) {
    let src = fs.readFileSync(MAIN, 'utf8');
    src = src.replace(/ \* Version:\s+[\d.]+/, ` * Version:     ${newVer}`);
    src = src.replace(/define\('CAMPSFLOW_VERSION',\s*'[\d.]+'\)/, `define('CAMPSFLOW_VERSION', '${newVer}')`);
    fs.writeFileSync(MAIN, src);
}

function ask(rl, question) {
    return new Promise(resolve => rl.question(question, resolve));
}

// ── Main ──────────────────────────────────────────────────────────────────────

async function main() {
    const rl = readline.createInterface({ input: process.stdin, output: process.stdout });

    const current = readVersion();
    console.log(`\nAktualna wersja: ${current}`);
    console.log(`  patch  → ${bumpVersion(current, 'patch')}`);
    console.log(`  minor  → ${bumpVersion(current, 'minor')}`);
    console.log(`  major  → ${bumpVersion(current, 'major')}\n`);

    const type = await ask(rl, 'Typ release [patch/minor/major]: ');
    if (!['patch', 'minor', 'major'].includes(type.trim())) {
        console.error('Nieprawidłowy typ. Wpisz: patch, minor lub major');
        rl.close();
        process.exit(1);
    }

    const newVer = bumpVersion(current, type.trim());
    const confirm = await ask(rl, `Wydać v${newVer}? [t/n]: `);
    rl.close();

    if (confirm.trim().toLowerCase() !== 't') {
        console.log('Anulowano.');
        process.exit(0);
    }

    console.log(`\n=== Bumping do v${newVer} ===`);
    writeVersion(newVer);

    console.log('\n=== Testy + analiza ===');
    const env = { ...process.env, MSYS_NO_PATHCONV: '1' };
    run([
        'docker run --rm',
        `-v "${ROOT}:/src"`,
        'php:8.2-cli bash -c "',
        'apt-get update -q && apt-get install -q -y unzip > /dev/null 2>&1 &&',
        'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer > /dev/null 2>&1 &&',
        'cp -r /src /tmp/test-src && cd /tmp/test-src &&',
        'composer install --no-interaction --prefer-dist --quiet &&',
        'vendor/bin/phpunit --testsuite unit &&',
        'vendor/bin/phpstan analyse --memory-limit=1G --no-progress"',
    ].join(' '), { env });

    console.log('\n=== Budowanie ZIP ===');
    const zipName = `campsflow-v${newVer}.zip`;
    const outPath = path.join(ROOT, 'dist', zipName);
    fs.mkdirSync(path.join(ROOT, 'dist'), { recursive: true });
    if (fs.existsSync(outPath)) fs.rmSync(outPath);

    const distDir = path.join(ROOT, 'dist');
    run([
        'docker run --rm',
        `-v "${ROOT}:/src" -v "${distDir}:/dist"`,
        'php:8.2-cli bash -c "',
        'apt-get update -q && apt-get install -q -y rsync zip > /dev/null 2>&1 &&',
        'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer > /dev/null 2>&1 &&',
        'cp -r /src /tmp/build-src && cd /tmp/build-src &&',
        'composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader --quiet &&',
        'mkdir -p /tmp/stage/campsflow &&',
        'rsync -a --exclude-from=.distignore . /tmp/stage/campsflow/ &&',
        `cd /tmp/stage && zip -r /dist/${zipName} campsflow/ -q &&`,
        `ls -lh /dist/${zipName}"`,
    ].join(' '), { env });

    console.log('\n=== Commit + tag + push ===');
    run(`git add campsflow.php`);
    run(`git commit -m "release: v${newVer}"`);
    run(`git tag v${newVer}`);
    run(`git push origin main --tags`);

    console.log(`\n✓ v${newVer} wydana! ZIP: dist/${zipName}`);
    console.log('  GitHub Actions buduje release — poczekaj ~2 min przed aktualizacją w WP.\n');
}

main().catch(err => { console.error(err.message); process.exit(1); });
