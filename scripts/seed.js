#!/usr/bin/env node
'use strict';

const { execSync } = require('child_process');
const env = { ...process.env, MSYS_NO_PATHCONV: '1' };

const wpContainer = execSync(
    'docker ps --filter publish=8890 --format {{.Names}}',
    { env }
).toString().trim();

if (!wpContainer) {
    console.error('No running wp-env container found on port 8890. Run: npm run env:start');
    process.exit(1);
}

const cliContainer = wpContainer.replace('wordpress', 'cli');
const seedFile = '/var/www/html/wp-content/plugins/campsflow.pl-wp/scripts/seed.php';

console.log(`Seeding via ${cliContainer}...`);
execSync(
    `docker exec ${cliContainer} wp eval-file ${seedFile} --allow-root`,
    { stdio: 'inherit', env }
);
