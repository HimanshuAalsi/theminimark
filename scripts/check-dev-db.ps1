# Quick check: is anything listening for MySQL, and is Docker available?
Write-Host "The Minimark — database dev check" -ForegroundColor Cyan
Write-Host ""

$docker = Get-Command docker -ErrorAction SilentlyContinue
if ($docker) {
    Write-Host "[OK] docker is on PATH" -ForegroundColor Green
    Push-Location $PSScriptRoot\..
    try { docker compose ps 2>$null } catch {}
    Pop-Location
} else {
    Write-Host "[--] docker not found — install Docker Desktop to use: npm run dev:db" -ForegroundColor Yellow
}

foreach ($port in @(3306, 3307)) {
    $t = Test-NetConnection -ComputerName 127.0.0.1 -Port $port -WarningAction SilentlyContinue
    if ($t.TcpTestSucceeded) {
        Write-Host "[OK] Something is accepting TCP on 127.0.0.1:$port (often MySQL)" -ForegroundColor Green
    } else {
        Write-Host "[--] Nothing on 127.0.0.1:$port" -ForegroundColor DarkGray
    }
}

Write-Host ""
Write-Host "config.local.php should use port 3306 for XAMPP/MariaDB on the machine, or 3307 + password root for this repo's Docker DB only." -ForegroundColor Gray
Write-Host "Import schema: backend\sql\schema.sql then backend\sql\seed.sql into database 'theminimark'." -ForegroundColor Gray
