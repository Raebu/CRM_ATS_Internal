#!/usr/bin/env bash
# Read-only Contabo staging P0 checks. Does not modify databases, systemd or nginx.
set -Eeuo pipefail
APP=/var/www/raeburn-talent-staging
DB=raeburn_talent_staging
fail(){ echo "FAIL: $*" >&2; exit 1; }
[ "$(id -u)" -eq 0 ] || fail "Run as root on the Contabo VPS"
[ -d "$APP/.git" ] && [ ! -L "$APP" ] || fail "Unexpected staging path"
[ "$(stat -c %U "$APP")" = raeburnstaging ] || fail "Unexpected staging owner"
[ -f "$APP/.env" ] && [ "$(stat -c %a "$APP/.env")" = 600 ] || fail "Private staging .env missing/permissions changed"
[ -f /root/raeburn-staging-secrets/mysql-password ] || fail "DB credential file missing"
cd "$APP"
php <<'PHP'
<?php
$env=file_get_contents('.env');
foreach (['APP_ENV'=>'staging','APP_DEBUG'=>'false','DB_DATABASE'=>'raeburn_talent_staging','DB_USERNAME'=>'raeburnstaging','MAIL_MAILER'=>'log','QUEUE_CONNECTION'=>'database','TALENT_PUBLIC_APPLICATIONS_ENABLED'=>'false','TALENT_EXTERNAL_EMAIL_ENABLED'=>'false','TALENT_OFFER_DELIVERY_ENABLED'=>'false','TALENT_VERIFIED_OFFER_ACCEPTANCE_ENABLED'=>'false'] as $k=>$v) {
 if (!preg_match('/^'.preg_quote($k,'/').'="?'.preg_quote($v,'/').'"?$/m',$env)) throw new RuntimeException("Unexpected setting: $k");
 echo "PASS: $k\n";
}
PHP
password="$(cat /root/raeburn-staging-secrets/mysql-password)"
MYSQL_PWD="$password" mysql -u raeburnstaging -h localhost "$DB" -N -e 'SELECT DATABASE()' | grep -Fxq "$DB" || fail "Staging DB login"
if MYSQL_PWD="$password" mysql -u raeburnstaging -h localhost raeburn_crm -N -e 'SELECT 1' >/dev/null 2>&1; then fail "Staging account can access production"; fi
unset password
echo "PASS: DB isolation"
systemctl is-active --quiet php8.3-fpm && echo "PASS: PHP-FPM active"
test -S /run/php/raeburn-talent-staging.sock && echo "PASS: Staging PHP socket"
nginx -t
if ss -ltnH '( sport = :8088 )' | grep -v '127.0.0.1:8088' | grep -q .; then fail "Port 8088 bound beyond loopback"; fi
curl --max-time 10 -sS -o /dev/null -w 'Staging localhost HTTP: %{http_code}\n' -H 'Host: staging-crm.theraeburngroup.com' http://127.0.0.1:8088/admin/login
echo "Production reachability (not application health):"
curl --max-time 10 -sS -o /dev/null -w '%{http_code}\n' https://crm.theraeburngroup.com/
echo "DNS resolution (must equal Contabo before certificate issuance):"
getent ahostsv4 staging-crm.theraeburngroup.com | head -n 1 || true
echo "QUEUE SERVICE:"
systemctl is-active raeburn-talent-staging-queue.service 2>/dev/null || true
echo "CLAMAV TCP LISTENER:"
if ss -ltnH '( sport = :3310 )' | grep -q '127.0.0.1:3310'; then echo "TCP clamd bound on loopback"; else echo "Not confirmed (do not enable CV ingestion)"; fi
echo "OFF-HOST BACKUP: cannot be verified by this script; require a restore test and checksum evidence."
echo "E2E INTEGRATIONS: cannot be verified by this script; external email and calendars remain disabled."
echo "=== PREFLIGHT COMPLETE (NOT LAUNCH APPROVAL) ==="
