#!/usr/bin/env node
/**
 * Builds a distributable ZIP for the campsflow WordPress plugin.
 * Run via: npm run release
 *
 * Reads version from campsflow.php and outputs dist/campsflow-vX.Y.Z.zip
 */

const { execSync } = require('child_process');
const fs   = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');

function readVersion() {
    const main = fs.readFileSync(path.join(ROOT, 'campsflow.php'), 'utf8');
    const m = main.match(/define\('CAMPSFLOW_VERSION',\s*'([^']+)'\)/);
    if (!m) throw new Error('Cannot find CAMPSFLOW_VERSION in campsflow.php');
    return m[1];
}

function run(cmd) {
    console.log(`> ${cmd}`);
    execSync(cmd, { stdio: 'inherit', cwd: ROOT });
}

const version = readVersion();
const zipName = `campsflow-v${version}.zip`;
const outPath = path.join(ROOT, 'dist', zipName);

console.log(`\nBuilding ${zipName}…\n`);

fs.mkdirSync(path.join(ROOT, 'dist'), { recursive: true });

if (fs.existsSync(outPath)) fs.rmSync(outPath);

const env = { ...process.env, MSYS_NO_PATHCONV: '1' };

run([
    'docker run --rm',
    `-v "${ROOT}:/src"`,
    `-v "${path.join(ROOT, 'dist')}:/dist"`,
    'php:8.2-cli',
    `bash -c "`,
    `apt-get update -q && apt-get install -q -y rsync zip > /dev/null 2>&1 &&`,
    `curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer > /dev/null 2>&1 &&`,
    `cp -r /src /tmp/build-src && cd /tmp/build-src &&`,
    `composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader --quiet &&`,
    `mkdir -p /tmp/stage/campsflow &&`,
    `rsync -a --exclude-from=.distignore . /tmp/stage/campsflow/ &&`,
    `cd /tmp/stage && zip -r /dist/${zipName} campsflow/ -q &&`,
    `ls -lh /dist/${zipName}"`,
].join(' '));

console.log(`\n✓ dist/${zipName}\n`);
