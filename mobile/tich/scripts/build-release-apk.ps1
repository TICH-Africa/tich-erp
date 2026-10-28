# Production-signed APKs (one per CPU — smaller than a single 60MB "fat" APK).
# Requires android/key.properties + upload-keystore.jks.
$ErrorActionPreference = "Stop"
Set-Location (Join-Path $PSScriptRoot "..")

$keyProps = Join-Path (Get-Location) "android\key.properties"
if (-not (Test-Path $keyProps)) {
    Write-Error "Missing android/key.properties. For internal tests use .\scripts\build-sideload-apk.ps1 instead."
}

flutter pub get
flutter build apk --release --split-per-abi @args
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

$outDir = Join-Path (Get-Location) "build\app\outputs\flutter-apk"
Write-Host ""
Write-Host "Share the APK that matches the phone (usually arm64-v8a):"
Get-ChildItem $outDir -Filter "*-release.apk" | ForEach-Object { Write-Host $_.FullName }
