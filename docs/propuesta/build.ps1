<#
    Compila el anteproyecto a PDF con Chrome/Edge en modo headless.

    Uso:
        .\build.ps1
        .\build.ps1 -Company "Distribuidora Ejemplo S.A."
        .\build.ps1 -OutFile "C:\ruta\Propuesta.pdf"

    El PDF se genera en la raíz del repositorio como Anteproyecto_pideaca.pdf
#>
param(
    [string]$Company = "",
    [string]$OutFile = ""
)

$ErrorActionPreference = "Stop"

$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$repo = Split-Path -Parent (Split-Path -Parent $here)
if ([string]::IsNullOrWhiteSpace($OutFile)) { $OutFile = Join-Path $repo "Anteproyecto_pideaca.pdf" }

$browser = @(
    "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
    "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
    "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe",
    "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
) | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $browser) { throw "No se encontró Chrome ni Edge para compilar el PDF." }

$source = Join-Path $here "anteproyecto-pideaca.html"
$temp = Join-Path $here "_build.html"

if (-not [string]::IsNullOrWhiteSpace($Company)) {
    $content = Get-Content -Path $source -Raw -Encoding UTF8
    $content = $content.Replace("[Nombre de la Empresa]", $Company.Trim())
    Set-Content -Path $temp -Encoding UTF8 -Value $content
    $source = $temp
}

$url = "file:///" + ($source -replace '\\', '/')
$profile = Join-Path $env:TEMP ("pideaca-pdf-" + [guid]::NewGuid().ToString("N").Substring(0, 8))

# Chrome escribe su progreso en stderr: se silencia sin abortar el script.
$previous = $ErrorActionPreference
$ErrorActionPreference = "Continue"
& $browser --headless=new --disable-gpu --no-pdf-header-footer `
    --run-all-compositor-stages-before-draw --virtual-time-budget=8000 `
    --user-data-dir="$profile" --print-to-pdf="$OutFile" "$url" 2>&1 | Out-Null

$ErrorActionPreference = $previous

if (Test-Path $temp) { Remove-Item $temp -Force }
Remove-Item $profile -Recurse -Force -ErrorAction SilentlyContinue

if (-not (Test-Path $OutFile)) { throw "No se pudo generar el PDF." }

$size = [math]::Round((Get-Item $OutFile).Length / 1KB, 0)
Write-Host "PDF generado: $OutFile ($size KB)"
