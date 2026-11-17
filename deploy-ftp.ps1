<#
.SYNOPSIS
  Empaqueta PideAca y lo sube por FTP/FTPS (puerto 21) al hosting.
  Pensado para cuando el panel Plesk (:8443) no responde pero el FTP sí.

.DESCRIPTION
  1. Crea pideaca-deploy.zip excluyendo .git, node_modules, .env, mobile, etc.
  2. Lo sube por FTPS explícito al directorio indicado.
  La extracción se hace luego desde el Administrador de archivos del panel
  (o Plesk File Manager) y el resto sigue en DEPLOY_PLESK_FTP.md.

.EXAMPLE
  # Pide usuario/clave de forma interactiva (no quedan en historial):
  .\deploy-ftp.ps1 -FtpHost ftp.pideaca.com -FtpUser miusuario_ft

.EXAMPLE
  # Con variables de entorno (CI):
  $env:FTP_HOST='ftp.pideaca.com'; $env:FTP_USER='u'; $env:FTP_PASS='p'
  .\deploy-ftp.ps1
#>
[CmdletBinding()]
param(
  [string]$FtpHost = $env:FTP_HOST,
  [string]$FtpUser = $env:FTP_USER,
  [string]$FtpPass = $env:FTP_PASS,
  [int]$Port = 21,
  [string]$RemoteDir = '/',
  [string]$ZipName = 'pideaca-deploy.zip',
  [switch]$PlainFtp
)

$ErrorActionPreference = 'Stop'

if (-not $FtpHost) { $FtpHost = Read-Host 'FTP host (ej: ftp.pideaca.com o la IP del correo de bienvenida)' }
if (-not $FtpUser) { $FtpUser = Read-Host 'FTP usuario' }
if (-not $FtpPass) {
  $sec = Read-Host 'FTP clave' -AsSecureString
  $FtpPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec)
  )
}

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$zipPath = Join-Path $projectRoot $ZipName

$excludeDirs = @('.git', 'node_modules', 'mobile', '.idea', '.vscode', 'vendor/bin/.phpunit')
$excludeFiles = @(
  '.env', '.env.backup', $ZipName, 'deploy-ftp.ps1',
  'phpunit.xml', 'phpunit.result.cache'
)
$excludePaths = @(
  'storage/logs/*', 'storage/framework/sessions/*',
  'storage/framework/views/*', 'storage/framework/cache/*',
  'bootstrap/cache/*.php', 'public/storage'
)

Add-Type -AssemblyName System.IO.Compression.FileSystem

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

$allFiles = Get-ChildItem -Path $projectRoot -Recurse -File -Force | Where-Object {
  $full = $_.FullName
  $rel = $full.Substring($projectRoot.Length + 1).Replace('\', '/')

  foreach ($d in $excludeDirs) {
    if ($rel -eq $d -or $rel.StartsWith("$d/")) { return $false }
  }
  foreach ($f in $excludeFiles) {
    if ($rel -eq $f -or $rel.EndsWith("/$f")) { return $false }
  }
  foreach ($p in $excludePaths) {
    $rx = '^' + [regex]::Escape($p).Replace('\*', '.*') + '$'
    if ($rel -match $rx) { return $false }
  }
  return $true
}

Write-Host "Archivos a empaquetar: $($allFiles.Count)"
$zip = [IO.Compression.ZipFile]::Open($zipPath, 'Create')

try {
  foreach ($file in $allFiles) {
    $rel = $file.FullName.Substring($projectRoot.Length + 1)
    [IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
      $zip, $file.FullName, $rel, [IO.Compression.CompressionLevel]::Optimal
    ) | Out-Null
  }
}
finally {
  $zip.Dispose()
}

$sizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 1)
Write-Host "ZIP creado: $ZipName ($sizeMb MB). Subiendo por FTPS al puerto $Port..."

function Send-FtpFile {
  param([string]$LocalPath, [string]$RemoteName)

  $uri = "ftp://${FtpHost}:${Port}$( $RemoteDir.TrimEnd('/') )/$RemoteName"
  $uri = $uri -replace ' ', '%20'

  $req = [Net.FtpWebRequest]::Create($uri)
  $req.Method = [Net.WebRequestMethods+Ftp]::UploadFile
  $req.Credentials = New-Object Net.NetworkCredential($FtpUser, $FtpPass)
  $req.UseBinary = $true
  $req.UsePassive = $true
  $req.KeepAlive = $false
  $req.EnableSsl = (-not $PlainFtp)
  if ($req.EnableSsl) {
    # FTPS explícito (puerto 21): valida el certificado del servidor.
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
  }

  $bytes = [IO.File]::ReadAllBytes($LocalPath)
  $req.ContentLength = $bytes.Length

  $stream = $req.GetRequestStream()
  try {
    $stream.Write($bytes, 0, $bytes.Length)
  }
  finally {
    $stream.Close()
  }

  $resp = $req.GetResponse()
  try {
    Write-Host "OK: $($resp.StatusDescription.Trim())"
  }
  finally {
    $resp.Close()
  }
}

Send-FtpFile -LocalPath $zipPath -RemoteName $ZipName

Write-Host ''
Write-Host 'Listo. Pasos siguientes (ver DEPLOY_PLESK_FTP.md):'
Write-Host "  1. En el panel: extraer $ZipName dentro de la carpeta pideaca/ (misma altura que httpdocs/)."
Write-Host '  2. Document root del dominio -> pideaca/public.'
Write-Host '  3. Crear .env (plantilla: .env.example) y correr tareas programadas.'
