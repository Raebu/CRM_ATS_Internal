#!/usr/bin/env bash
# Non-destructive checks only. Run on the Contabo staging host as root.
set -uo pipefail
APP=/var/www/raeburn-talent-staging
FAILED=0
check() { if "$@" >/dev/null 2>&1; then printf 'PASS: %s\n' "$*"; else printf 'FAIL: %s\n' "$*"; FAILED=1; fi; }

printf '%s\n' '=== Raeburn Talent staging security preflight ==='
check test -f "$APP/artisan"
for bin in /usr/bin/bwrap /usr/bin/qpdf /usr/bin/pdfinfo /usr/bin/pdftotext /usr/bin/pdftoppm /usr/bin/tesseract /usr/bin/python3; do
  check test -x "$bin"
done
check /usr/bin/python3 -c 'import img2pdf'
check systemctl is-active --quiet clamav-daemon
check sudo -u raeburnstaging /usr/bin/bwrap --unshare-net --die-with-parent --ro-bind /usr /usr --ro-bind /bin /bin --ro-bind-try /lib /lib --ro-bind-try /lib64 /lib64 --dev-bind /dev /dev --proc /proc --tmpfs /tmp /bin/sh -c 'test -x /usr/bin/qpdf'
if [ -f "$APP/.env" ]; then
  if grep -Eq '^TALENT_CV_UPLOADS_ENABLED=(true|1)$' "$APP/.env"; then
    echo 'FAIL: CV uploads are enabled in .env (must remain off pending acceptance)'
    FAILED=1
  else
    echo 'PASS: CV upload env flag not explicitly enabled'
  fi
fi
echo '=== Production HTTP status (redirect is normal) ==='
curl -sS --max-time 15 -o /dev/null -w '%{http_code}\n' https://crm.theraeburngroup.com/ || FAILED=1
echo '=== Result ==='
if [ "$FAILED" -ne 0 ]; then
 echo 'Preflight NOT ready. Do not enable CV processing.'
 exit 1
fi
echo 'Dependencies and isolation preflight passed; manual acceptance is STILL required.'
