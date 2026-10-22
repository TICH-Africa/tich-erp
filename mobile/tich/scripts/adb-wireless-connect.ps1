# Pair + connect Android wireless debugging (Windows).
# Usage:
#   1. Phone: Developer options → Wireless debugging → Pair device with pairing code
#   2. Run:
#      .\scripts\adb-wireless-connect.ps1 -PairHost 192.168.0.109 -PairPort 12345 -PairCode 123456 -ConnectPort 37231
param(
  [Parameter(Mandatory = $true)] [string] $PairHost,
  [Parameter(Mandatory = $true)] [int] $PairPort,
  [Parameter(Mandatory = $true)] [string] $PairCode,
  [Parameter(Mandatory = $true)] [int] $ConnectPort
)

$adb = "$env:LOCALAPPDATA\Android\Sdk\platform-tools\adb.exe"
if (-not (Test-Path $adb)) { throw "adb not found at $adb" }

& $adb kill-server | Out-Null
Start-Sleep -Seconds 1
& $adb start-server | Out-Null

Write-Host "Pairing $PairHost`:$PairPort ..."
$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName = $adb
$psi.Arguments = "pair ${PairHost}:${PairPort}"
$psi.UseShellExecute = $false
$psi.RedirectStandardInput = $true
$psi.RedirectStandardOutput = $true
$psi.RedirectStandardError = $true
$proc = [System.Diagnostics.Process]::Start($psi)
$proc.StandardInput.WriteLine($PairCode)
$proc.StandardInput.Close()
$pairOut = $proc.StandardOutput.ReadToEnd()
$pairErr = $proc.StandardError.ReadToEnd()
$proc.WaitForExit(20000) | Out-Null
Write-Host ($pairOut + $pairErr)
if ($proc.ExitCode -ne 0 -and $pairOut + $pairErr -notmatch 'Successfully paired') {
  throw "Pairing failed. Keep the Pair dialog open and use a fresh code/port."
}

Write-Host "Connecting ${PairHost}:${ConnectPort} ..."
& $adb connect "${PairHost}:${ConnectPort}" 2>&1
& $adb devices -l
