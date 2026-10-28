# Build a Google Play App Bundle (AAB) for upload to Play Console.
# Requires android/key.properties + upload-keystore.jks (see android/key.properties.example).

$ErrorActionPreference = "Stop"
Set-Location (Join-Path $PSScriptRoot "..")

$keyProps = Join-Path (Get-Location) "android\key.properties"
if (-not (Test-Path $keyProps)) {
    Write-Error "Missing android/key.properties. Copy android/key.properties.example and add your upload keystore."
}

flutter pub get
flutter build appbundle --release @args
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

$bundle = Join-Path (Get-Location) "build\app\outputs\bundle\release\app-release.aab"
Write-Host ""
Write-Host "Upload this file in Play Console (Release > Create new release):"
Write-Host $bundle
