# Test that upload-keystore.jks matches android/key.properties (alias + passwords).
# Does not print passwords. Run from mobile/tich.

$ErrorActionPreference = "Stop"
$androidDir = Join-Path $PSScriptRoot "..\android"
$keyPropsPath = Join-Path $androidDir "key.properties"

if (-not (Test-Path $keyPropsPath)) {
    Write-Error "Missing android/key.properties"
}

$props = @{}
Get-Content $keyPropsPath | ForEach-Object {
    $line = $_.Trim()
    if ($line -eq "" -or $line.StartsWith("#")) { return }
    $i = $line.IndexOf("=")
    if ($i -lt 1) { return }
    $k = $line.Substring(0, $i).Trim()
    $v = $line.Substring($i + 1).Trim()
    $props[$k] = $v
}

$storeFile = $props["storeFile"]
$alias = $props["keyAlias"]
if (-not $storeFile -or -not $alias) {
    Write-Error "key.properties must set storeFile and keyAlias"
}

$keystorePath = Join-Path $androidDir $storeFile
if (-not (Test-Path $keystorePath)) {
    Write-Error "Keystore not found: $keystorePath"
}

Write-Host "Keystore: $keystorePath"
Write-Host "Alias:    $alias"
Write-Host ""
Write-Host "Enter the keystore password when keytool prompts (same as storePassword in key.properties)."
Write-Host ""

& keytool -list -v -keystore $keystorePath -alias $alias
if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "If password failed: fix storePassword/keyPassword in key.properties, or recreate the .jks with keytool."
    exit $LASTEXITCODE
}

Write-Host ""
Write-Host "Keystore OK. If Gradle still fails, keyPassword may differ from storePassword — set both in key.properties."
