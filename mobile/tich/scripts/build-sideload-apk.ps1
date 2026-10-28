# Release-optimized APK signed with the debug key — for sideload / internal testing only (not Play Store).
$ErrorActionPreference = "Stop"
Set-Location (Join-Path $PSScriptRoot "..")

Write-Host "Building sideload release APK (debug signing)..."
flutter pub get
flutter build apk --release --android-project-arg=debugReleaseSigning=true @args
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

$outDir = Join-Path (Get-Location) "build\app\outputs\flutter-apk"
Write-Host ""
Write-Host "Install or share (use arm64 on most phones):"
Get-ChildItem $outDir -Filter "*release*.apk" | ForEach-Object { Write-Host $_.FullName }
