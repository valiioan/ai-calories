# Dot-source so PATH applies to the current PowerShell session:
#   . .\scripts\dev-env.ps1

$phpDir = Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe'
$nodeDir = Join-Path $env:LOCALAPPDATA 'nodejs-v24\node-v24.20.0-win-x64'
$prepend = @()

if (Test-Path (Join-Path $phpDir 'php.exe')) {
    $prepend += $phpDir
}

if (Test-Path (Join-Path $nodeDir 'node.exe')) {
    $prepend += $nodeDir
}

if ($prepend.Count -gt 0) {
    $env:Path = ($prepend -join ';') + ';' + $env:Path
}

function composer {
    & php (Join-Path $env:LOCALAPPDATA 'Composer\composer.phar') @args
}

Write-Host "php  $((php -v | Select-Object -First 1))"
Write-Host "node $(node -v)"
Write-Host "composer $((composer --version))"
