#!/usr/bin/env bash
set -euo pipefail

cd /workspaces/CertificacionHHGG-source

run_step() {
	local title="$1"
	shift
	echo ""
	echo "==> $title"
	"$@"
}

run_step "Unit tests" php artisan test tests/Unit
run_step "Feature tests" php artisan test tests/Feature
run_step "Full suite" php artisan test

if command -v phpdbg >/dev/null 2>&1; then
	run_step "Coverage (phpdbg)" phpdbg -qrr artisan test --coverage
elif php -m | grep -qi '^xdebug$'; then
	run_step "Coverage (xdebug)" env XDEBUG_MODE=coverage php artisan test --coverage
elif php -m | grep -qi '^pcov$'; then
	run_step "Coverage (pcov)" php -d pcov.enabled=1 artisan test --coverage
else
	echo ""
	echo "[WARN] No hay driver de cobertura (phpdbg/xdebug/pcov)."
	echo "[WARN] Ejecuta: phpdbg -qrr artisan test --coverage"
	echo "[WARN] o instala Xdebug/PCOV para habilitar cobertura."
fi
