# Adds 100 catalogue rows (ids 8001+) into the RUNNING Docker MySQL used by docker-compose.yml.
# From the repository root, with containers up:  docker compose up -d
# Then:  powershell -ExecutionPolicy Bypass -File scripts/import-bulk-products-docker.ps1

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $root
if (-not (Test-Path (Join-Path $root 'docker-compose.yml'))) {
  Write-Error "Run this from the repo that contains docker-compose.yml (expected at $root)"
}

$sqlPath = Join-Path $root 'backend\sql\seed_extra_products.sql'
if (-not (Test-Path $sqlPath)) {
  Write-Error "Missing $sqlPath — run: cd backend/sql && node generate_extra_products.mjs"
}

Write-Host "Importing bulk products from: $sqlPath"
Get-Content -Path $sqlPath -Raw -Encoding UTF8 | docker compose exec -T db mysql -uroot -proot theminimark
if ($LASTEXITCODE -ne 0) {
  Write-Error "mysql import failed (exit $LASTEXITCODE). Is Docker running? Try: docker compose up -d"
}
Write-Host "Done. Check: curl http://localhost:8888/v1/products  (or via Vite /api/v1/products) — meta.count should be 112."
