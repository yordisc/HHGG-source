#!/usr/bin/env bash
set -euo pipefail

URL=""
REQUESTS=200
OUTPUT_DIR="docs/benchmarks"
CONTAINER_NAME=""

usage() {
  cat <<'EOF'
Uso:
  sh scripts/profile-serving.sh --url <https://app.example.com> [--requests 200] [--container <name>] [--output-dir docs/benchmarks]

Opciones:
  --url         URL base a medir (requerido).
  --requests    Numero total de requests secuenciales para baseline (default: 200).
  --container   Nombre del contenedor Docker para snapshot de memoria (opcional).
  --output-dir  Carpeta de salida para el reporte Markdown (default: docs/benchmarks).
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --url)
      URL="${2:-}"
      shift 2
      ;;
    --requests)
      REQUESTS="${2:-}"
      shift 2
      ;;
    --container)
      CONTAINER_NAME="${2:-}"
      shift 2
      ;;
    --output-dir)
      OUTPUT_DIR="${2:-}"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Argumento desconocido: $1" >&2
      usage
      exit 1
      ;;
  esac
done

if [[ -z "$URL" ]]; then
  echo "Error: --url es obligatorio." >&2
  usage
  exit 1
fi

if ! [[ "$REQUESTS" =~ ^[0-9]+$ ]] || [[ "$REQUESTS" -lt 1 ]]; then
  echo "Error: --requests debe ser un entero positivo." >&2
  exit 1
fi

mkdir -p "$OUTPUT_DIR"

tmp_raw="$(mktemp)"
tmp_ttfb="$(mktemp)"
tmp_total="$(mktemp)"
trap 'rm -f "$tmp_raw" "$tmp_ttfb" "$tmp_total"' EXIT

echo "Ejecutando baseline de $REQUESTS requests sobre $URL"

for _ in $(seq 1 "$REQUESTS"); do
  metrics="$(curl -sS -o /dev/null -w '%{http_code} %{time_starttransfer} %{time_total}' "$URL")"
  echo "$metrics" >> "$tmp_raw"
done

awk '{print $2 * 1000}' "$tmp_raw" | sort -n > "$tmp_ttfb"
awk '{print $3 * 1000}' "$tmp_raw" | sort -n > "$tmp_total"

percentile() {
  local p="$1"
  local file="$2"

  awk -v p="$p" '
    { a[NR] = $1 }
    END {
      if (NR == 0) {
        print "0.00"
        exit
      }
      idx = int((p / 100) * NR)
      if (idx < 1) idx = 1
      if (idx > NR) idx = NR
      printf "%.2f", a[idx]
    }
  ' "$file"
}

avg_ms() {
  local file="$1"
  awk '{sum += $1; n += 1} END { if (n == 0) { print "0.00" } else { printf "%.2f", sum / n } }' "$file"
}

failures="$(awk '$1 >= 400 { c += 1 } END { print c + 0 }' "$tmp_raw")"

ttfb_avg="$(avg_ms "$tmp_ttfb")"
ttfb_p50="$(percentile 50 "$tmp_ttfb")"
ttfb_p95="$(percentile 95 "$tmp_ttfb")"
ttfb_p99="$(percentile 99 "$tmp_ttfb")"

total_avg="$(avg_ms "$tmp_total")"
total_p50="$(percentile 50 "$tmp_total")"
total_p95="$(percentile 95 "$tmp_total")"
total_p99="$(percentile 99 "$tmp_total")"

mem_snapshot="n/a"
if [[ -n "$CONTAINER_NAME" ]] && command -v docker >/dev/null 2>&1; then
  mem_snapshot="$(docker stats --no-stream --format '{{.Name}} {{.MemUsage}}' "$CONTAINER_NAME" 2>/dev/null || true)"
  if [[ -z "$mem_snapshot" ]]; then
    mem_snapshot="n/a"
  fi
fi

timestamp="$(date +%Y%m%d-%H%M%S)"
report_file="$OUTPUT_DIR/phase4-baseline-$timestamp.md"

cat > "$report_file" <<EOF
# Baseline Fase 4

- URL: $URL
- Requests: $REQUESTS
- Fallos HTTP (>= 400): $failures
- Snapshot memoria contenedor: $mem_snapshot

## TTFB (ms)

- Avg: $ttfb_avg
- P50: $ttfb_p50
- P95: $ttfb_p95
- P99: $ttfb_p99

## Total Time (ms)

- Avg: $total_avg
- P50: $total_p50
- P95: $total_p95
- P99: $total_p99

## Decision Gate

- Mantener stack actual si P95 total < 800 ms y memoria estable sin OOM.
- Evaluar migracion de serving si P95 total >= 800 ms o hay presion de RAM sostenida.
EOF

echo "Reporte generado: $report_file"
echo "TTFB p95: $ttfb_p95 ms | Total p95: $total_p95 ms | Fallos: $failures"
