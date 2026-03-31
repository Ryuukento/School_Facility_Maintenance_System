param(
    [string]$InputFile = "school_facility_maintenance.sql",
    [string]$TargetDatabase = "school_facility_maintenance",
    [string]$OutputDirectory = "."
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Resolve-InputPath {
    param([string]$FilePath)

    if ([System.IO.Path]::IsPathRooted($FilePath)) {
        return (Resolve-Path -Path $FilePath).Path
    }

    $scriptDir = Split-Path -Parent $PSCommandPath
    return (Resolve-Path -Path (Join-Path $scriptDir $FilePath)).Path
}

$inputPath = Resolve-InputPath -FilePath $InputFile
$scriptDir = Split-Path -Parent $PSCommandPath

if ([System.IO.Path]::IsPathRooted($OutputDirectory)) {
    $outDir = $OutputDirectory
} else {
    $outDir = Join-Path $scriptDir $OutputDirectory
}

if (-not (Test-Path -Path $outDir)) {
    New-Item -Path $outDir -ItemType Directory -Force | Out-Null
}

$lines = Get-Content -Path $inputPath
if (-not $lines -or $lines.Count -eq 0) {
    throw "Input SQL file is empty: $inputPath"
}

$targetMarker = "-- Database: ``$TargetDatabase``"
$startIndex = -1

for ($i = 0; $i -lt $lines.Count; $i++) {
    if ($lines[$i].Trim() -eq $targetMarker) {
        $startIndex = $i
        break
    }
}

if ($startIndex -lt 0) {
    throw "Could not find target database section for '$TargetDatabase' in $inputPath"
}

$headerLines = @()

for ($i = 0; $i -lt $startIndex; $i++) {
    $line = $lines[$i]

    if ($line -match '^--\s+Database:\s+`phpmyadmin`$') {
        continue
    }

    if ($line -match '^CREATE DATABASE IF NOT EXISTS\s+`phpmyadmin`') {
        continue
    }

    if ($line -match '^USE\s+`phpmyadmin`;') {
        continue
    }

    if ($line -match 'pma__') {
        continue
    }

    $headerLines += $line
}

$headerLines = $headerLines | Where-Object {
    $_ -notmatch '^-- NOTE: Removed phpMyAdmin internal tables' -and
    $_ -notmatch '^-- NOTE: This dump now targets School Facility Maintenance System schema only\.'
}

$appLines = $lines[$startIndex..($lines.Count - 1)]

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$outputFileName = "$TargetDatabase-clean-$timestamp.sql"
$outputPath = Join-Path $outDir $outputFileName

$newContent = @()
$newContent += $headerLines
$newContent += '-- NOTE: Removed phpMyAdmin internal tables (pma__*) for clean app-only import.'
$newContent += '-- NOTE: This dump now targets School Facility Maintenance System schema only.'
$newContent += ''
$newContent += $appLines

Set-Content -Path $outputPath -Value $newContent -Encoding UTF8

Write-Host "Created clean backup: $outputPath"
Write-Host "Source file: $inputPath"
Write-Host "Target database section starts at line: $($startIndex + 1)"