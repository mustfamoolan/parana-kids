# Script لتحويل ملف Firebase credentials إلى Base64
# للاستخدام في Laravel Cloud

$filePath = "storage/app/parana-kids-firebase-adminsdk-fbsvc-aabd2ef994.json"

if (Test-Path $filePath) {
    Write-Host "Converting file to Base64..." -ForegroundColor Green

    # قراءة الملف وتحويله إلى Base64
    $fileBytes = [IO.File]::ReadAllBytes($filePath)
    $base64 = [Convert]::ToBase64String($fileBytes)

    # تنظيف Base64 (إزالة أي مسافات أو أسطر جديدة)
    $base64 = $base64.Trim()
    $base64 = $base64 -replace '\s+', ''

    Write-Host "`n=== COPY THIS TO Laravel Cloud Environment Variables ===" -ForegroundColor Yellow
    Write-Host "FIREBASE_CREDENTIALS_BASE64=$base64" -ForegroundColor Cyan
    Write-Host "`n=== END ===" -ForegroundColor Yellow
    Write-Host "`nLength: $($base64.Length) characters" -ForegroundColor Gray

    # حفظ في ملف أيضاً (بدون مسافات أو أسطر جديدة)
    $outputFile = "firebase-credentials-base64.txt"
    [System.IO.File]::WriteAllText($outputFile, $base64, [System.Text.Encoding]::ASCII)
    Write-Host "`nBase64 string saved to: $outputFile" -ForegroundColor Green
    Write-Host "IMPORTANT: Copy the ENTIRE string without any spaces or line breaks!" -ForegroundColor Yellow
} else {
    Write-Host "Error: File not found at: $filePath" -ForegroundColor Red
    Write-Host "Please make sure the file exists in the correct location." -ForegroundColor Yellow
}
