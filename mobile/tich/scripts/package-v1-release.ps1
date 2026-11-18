# Build v1.0.0 sideload APKs and copy into releases/v1.0.0/ for ICT upload.
param([switch]$SkipBuild)

$ErrorActionPreference = "Stop"
$root = Join-Path $PSScriptRoot ".."
Set-Location $root

$version = "1.0.0"
$build = "1"
$dest = Join-Path $root "releases\v1.0.0"
New-Item -ItemType Directory -Force -Path $dest | Out-Null

if (-not $SkipBuild) {
    Write-Host "Building TICH mobile $version (build $build)..."
    flutter pub get
    flutter build apk --release --split-per-abi --android-project-arg=debugReleaseSigning=true
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
} else {
    Write-Host "SkipBuild: copying existing APKs from build\app\outputs\flutter-apk"
}

$outDir = Join-Path $root "build\app\outputs\flutter-apk"
$canonical = "tich-mobile-$version-build$build-android.apk"
$maps = @{
    "arm64-v8a"   = $canonical
    "armeabi-v7a" = "tich-mobile-$version-build$build-android-armeabi-v7a.apk"
    "x86_64"      = "tich-mobile-$version-build$build-android-x86_64.apk"
}

foreach ($abi in $maps.Keys) {
    $src = Join-Path $outDir "app-$abi-release.apk"
    if (Test-Path $src) {
        Copy-Item -Force $src (Join-Path $dest $maps[$abi])
        Write-Host "Copied $($maps[$abi])"
    }
}

$fat = Join-Path $outDir "app-release.apk"
if (Test-Path $fat) {
    Copy-Item -Force $fat (Join-Path $dest "tich-$version-build$build-android-universal.apk")
    Write-Host "Copied universal APK"
}

Write-Host ""
Write-Host "Upload arm64 APK via ICT > Mobile app releases."
Write-Host "Release notes: releases\v1.0.0\release-notes.txt"