#!/usr/bin/env powershell

Write-Host "==== SCHOOL FACILITY MAINTENANCE SYSTEM - QUICK TEST ====" -ForegroundColor Cyan
Write-Host ""

# Test 1: Check if files exist
Write-Host "1. Checking files..." -ForegroundColor Yellow
$files = @(
    "C:\xampp\htdocs\School_Facility_Maintenance_System\index.php",
    "C:\xampp\htdocs\School_Facility_Maintenance_System\frontend\pages\index.php",
    "C:\xampp\htdocs\School_Facility_Maintenance_System\database\SINGLE_IMPORT.sql"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "   ✅ $(Split-Path $file -Leaf)" -ForegroundColor Green
    } else {
        Write-Host "   ❌ $(Split-Path $file -Leaf) NOT FOUND" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "2. Checking if you need to import database..." -ForegroundColor Yellow
Write-Host "   Go to: http://localhost/School_Facility_Maintenance_System/debug.php" -ForegroundColor Cyan
Write-Host "   Check if database shows ✅ or ❌"
Write-Host ""

Write-Host "3. If database is ❌, import it:" -ForegroundColor Yellow
Write-Host "   mysql -u root < `"$PSScriptRoot\database\SINGLE_IMPORT.sql`"" -ForegroundColor Cyan
Write-Host ""

Write-Host "4. Then visit:" -ForegroundColor Yellow
Write-Host "   http://localhost/School_Facility_Maintenance_System/" -ForegroundColor Cyan
Write-Host ""

Write-Host "5. Login with:" -ForegroundColor Yellow
Write-Host "   Email: admin@school.edu" -ForegroundColor Cyan
Write-Host "   Password: admin123" -ForegroundColor Cyan
Write-Host ""

Write-Host "==== DONE ====" -ForegroundColor Cyan
