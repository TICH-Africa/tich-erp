# Print compile/min/target SDK and version for Google Play checks.
$ErrorActionPreference = "Stop"
Set-Location (Join-Path $PSScriptRoot "..\android")
& .\gradlew.bat :app:printPlaySdkInfo --quiet @args
