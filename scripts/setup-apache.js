#!/usr/bin/env node
/**
 * Post-start setup for wp-env:
 * 1. Enables AllowOverride All in Apache VHost
 * 2. Sets permalink structure + flushes rewrite rules
 * 3. Seeds CampsFlow plugin options for local dev (oaza-test tenant)
 */

const { execSync } = require('child_process');

const env = { ...process.env, MSYS_NO_PATHCONV: '1' };

// Dev seed values — must match supabase/seed/01_tenants.sql
const DEV_TENANT_SLUG = 'oaza-test';
const DEV_API_KEY     = 'wp-sync-oaza-test-dev-key';
const DEV_API_URL     = 'http://host.docker.internal:3601';

function run(cmd, opts = {}) {
    try {
        return execSync(cmd, { env, stdio: 'pipe', ...opts }).toString().trim();
    } catch (e) {
        return e.message;
    }
}

const wpContainer  = run("docker ps --filter publish=8890 --format {{.Names}}").split('\n')[0];
const cliContainer = wpContainer.replace('wordpress', 'cli');

if (!wpContainer) {
    console.error('WordPress container not found on port 8890');
    process.exit(1);
}

console.log(`WP:  ${wpContainer}`);
console.log(`CLI: ${cliContainer}`);

// 1. AllowOverride All (idempotent — skip if already set)
const vhost = run(`docker exec ${wpContainer} cat /etc/apache2/sites-enabled/000-default.conf`);
if (!vhost.includes('AllowOverride All')) {
    run(`docker exec ${wpContainer} bash -c "sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html\\n\\t<Directory /var/www/html>\\n\\t\\tAllowOverride All\\n\\t</Directory>|' /etc/apache2/sites-enabled/000-default.conf"`);
    run(`docker exec ${wpContainer} apache2ctl graceful`);
    console.log('✓ Apache AllowOverride All set');
} else {
    console.log('✓ Apache already configured');
}

// 2. Permalink structure
run(`docker exec ${cliContainer} wp option update permalink_structure /%postname%/ --allow-root`);
console.log('✓ Permalink structure set');

// 3. Flush rewrite rules + generate .htaccess
run(`docker exec ${cliContainer} wp rewrite flush --hard --allow-root`);
console.log('✓ Rewrite rules flushed');

// 4. CampsFlow plugin options — dev seed
run(`docker exec ${cliContainer} wp option update campsflow_tenant_slug "${DEV_TENANT_SLUG}" --allow-root`);
run(`docker exec ${cliContainer} wp option update campsflow_api_key "${DEV_API_KEY}" --allow-root`);
run(`docker exec ${cliContainer} wp option update campsflow_api_url "${DEV_API_URL}" --allow-root`);
console.log(`✓ CampsFlow options set (tenant: ${DEV_TENANT_SLUG})`);

console.log('\nDev environment ready → http://localhost:8890');
console.log(`  Tenant: ${DEV_TENANT_SLUG}`);
console.log(`  API:    http://host.docker.internal:3601/api/v1/public/${DEV_TENANT_SLUG}/events`);
