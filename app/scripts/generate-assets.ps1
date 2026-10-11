Add-Type -AssemblyName System.Drawing

$baseDir = Split-Path -Parent $PSScriptRoot
$imagesDir = Join-Path $baseDir "assets\images"
$sourcePath = Join-Path $imagesDir "icon.png"
$backupPath = Join-Path $imagesDir "icon-original.png"

if (-not (Test-Path $backupPath)) {
    Copy-Item $sourcePath $backupPath
    Write-Host "Backed up original icon to $backupPath"
}

$srcBmp = [System.Drawing.Bitmap]::FromFile($backupPath)
$srcW = $srcBmp.Width
$srcH = $srcBmp.Height
Write-Host "Source image size: ${srcW}x${srcH}"

# Find bounding box of non-white content
$minX = $srcW
$maxX = 0
$minY = $srcH
$maxY = 0

for ($y = 0; $y -lt $srcH; $y += 2) {
    for ($x = 0; $x -lt $srcW; $x += 2) {
        $pixel = $srcBmp.GetPixel($x, $y)
        # Check if pixel is not near-white
        if ($pixel.A -gt 20 -and ($pixel.R -lt 250 -or $pixel.G -lt 250 -or $pixel.B -lt 250)) {
            if ($x -lt $minX) { $minX = $x }
            if ($x -gt $maxX) { $maxX = $x }
            if ($y -lt $minY) { $minY = $y }
            if ($y -gt $maxY) { $maxY = $y }
        }
    }
}

Write-Host "Content bounds: X=$minX..$maxX, Y=$minY..$maxY"
$contentW = [Math]::Max(1, $maxX - $minX + 1)
$contentH = [Math]::Max(1, $maxY - $minY + 1)
Write-Host "Content size: ${contentW}x${contentH}"

function Save-ResizedImage {
    param(
        [string]$outputPath,
        [int]$targetWidth,
        [int]$targetHeight,
        [double]$scaleFactor,
        [System.Drawing.Color]$bgColor,
        [bool]$transparentBg = $false
    )

    $destBmp = New-Object System.Drawing.Bitmap($targetWidth, $targetHeight, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $g = [System.Drawing.Graphics]::FromImage($destBmp)
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality

    if ($transparentBg) {
        $g.Clear([System.Drawing.Color]::Transparent)
    } else {
        $g.Clear($bgColor)
    }

    # Calculate draw size
    $drawW = [int]($targetWidth * $scaleFactor)
    $drawH = [int]($targetHeight * $scaleFactor)
    $destX = [int](($targetWidth - $drawW) / 2)
    $destY = [int](($targetHeight - $drawH) / 2)

    # If content has border padding, draw full src or cropped content
    $srcRect = New-Object System.Drawing.Rectangle(0, 0, $srcW, $srcH)
    $destRect = New-Object System.Drawing.Rectangle($destX, $destY, $drawW, $drawH)

    $g.DrawImage($srcBmp, $destRect, $srcRect, [System.Drawing.GraphicsUnit]::Pixel)
    $g.Dispose()

    # Save PNG
    $destBmp.Save($outputPath, [System.Drawing.Imaging.ImageFormat]::Png)
    $destBmp.Dispose()
    Write-Host "Generated: $outputPath (${targetWidth}x${targetHeight})"
}

# 1. Main app icon (1024x1024, full bleed)
Save-ResizedImage -outputPath (Join-Path $imagesDir "icon.png") -targetWidth 1024 -targetHeight 1024 -scaleFactor 1.0 -bgColor ([System.Drawing.Color]::White)

# 2. Android adaptive icon foreground (1024x1024, safe zone 72% centered)
Save-ResizedImage -outputPath (Join-Path $imagesDir "android-icon-foreground.png") -targetWidth 1024 -targetHeight 1024 -scaleFactor 0.72 -bgColor ([System.Drawing.Color]::White)

# 3. Android adaptive icon background (1024x1024 pure white)
$bgBmp = New-Object System.Drawing.Bitmap(1024, 1024, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
$bgG = [System.Drawing.Graphics]::FromImage($bgBmp)
$bgG.Clear([System.Drawing.Color]::White)
$bgG.Dispose()
$bgBmp.Save((Join-Path $imagesDir "android-icon-background.png"), [System.Drawing.Imaging.ImageFormat]::Png)
$bgBmp.Dispose()
Write-Host "Generated: android-icon-background.png (1024x1024)"

# 4. Splash screen icon (512x512)
Save-ResizedImage -outputPath (Join-Path $imagesDir "splash-icon.png") -targetWidth 512 -targetHeight 512 -scaleFactor 0.90 -bgColor ([System.Drawing.Color]::White)

# 5. Favicon (48x48)
Save-ResizedImage -outputPath (Join-Path $imagesDir "favicon.png") -targetWidth 48 -targetHeight 48 -scaleFactor 1.0 -bgColor ([System.Drawing.Color]::White)

$srcBmp.Dispose()
Write-Host "All assets generated successfully!"
