<?php

class SignatureExtractor
{
    /**
     * Extracts signature strokes from any photo/scan by removing paper background,
     * converting it into a crisp transparent PNG, and auto-cropping to signature bounds.
     *
     * @param string $sourcePath Path to uploaded/source image
     * @param string $destinationPath Path where the transparent PNG will be saved
     * @param int $threshold Luminance threshold (150-240, default 205)
     * @return array ['success' => bool, 'message' => string, 'path' => string]
     */
    public static function extractAndSave(string $sourcePath, string $destinationPath, int $threshold = 205): array
    {
        if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
            return ['success' => false, 'message' => 'Source image not found.'];
        }

        $rawContents = file_get_contents($sourcePath);
        if (!$rawContents) {
            return ['success' => false, 'message' => 'Failed to read image contents.'];
        }

        $srcImg = @imagecreatefromstring($rawContents);
        if (!$srcImg) {
            return ['success' => false, 'message' => 'Invalid image format or corrupted file.'];
        }

        $origWidth = imagesx($srcImg);
        $origHeight = imagesy($srcImg);

        // Limit maximum processing dimension to 2000px for high performance and low memory
        $maxDim = 2000;
        if ($origWidth > $maxDim || $origHeight > $maxDim) {
            $scale = min($maxDim / $origWidth, $maxDim / $origHeight);
            $newWidth = (int) ($origWidth * $scale);
            $newHeight = (int) ($origHeight * $scale);
            $scaledImg = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($scaledImg, false);
            imagesavealpha($scaledImg, true);
            imagecopyresampled($scaledImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($srcImg);
            $srcImg = $scaledImg;
            $origWidth = $newWidth;
            $origHeight = $newHeight;
        }

        // Create canvas for transparent output
        $outImg = imagecreatetruecolor($origWidth, $origHeight);
        imagealphablending($outImg, false);
        imagesavealpha($outImg, true);

        // Track bounding box of actual signature ink
        $minX = $origWidth;
        $maxX = 0;
        $minY = $origHeight;
        $maxY = 0;
        $inkPixels = 0;

        $lowerThreshold = max(50, $threshold - 55);

        for ($y = 0; $y < $origHeight; $y++) {
            for ($x = 0; $x < $origWidth; $x++) {
                $rgba = imagecolorat($srcImg, $x, $y);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                // Standard perceptual luminance
                $luminance = (int) (0.299 * $r + 0.587 * $g + 0.114 * $b);

                if ($luminance >= $threshold) {
                    // Paper / White / Gray background -> 100% transparent
                    $color = imagecolorallocatealpha($outImg, 255, 255, 255, 127);
                } elseif ($luminance <= $lowerThreshold) {
                    // Solid dark ink stroke -> Opaque (alpha = 0), slightly darken for crispness
                    $darkR = (int) max(0, $r * 0.7);
                    $darkG = (int) max(0, $g * 0.7);
                    $darkB = (int) max(0, $b * 0.7);
                    $color = imagecolorallocatealpha($outImg, $darkR, $darkG, $darkB, 0);

                    // Track bounding box
                    if ($x < $minX) $minX = $x;
                    if ($x > $maxX) $maxX = $x;
                    if ($y < $minY) $minY = $y;
                    if ($y > $maxY) $maxY = $y;
                    $inkPixels++;
                } else {
                    // Transition zone: smooth anti-aliased gradient
                    $ratio = ($luminance - $lowerThreshold) / ($threshold - $lowerThreshold);
                    $alpha = (int) round($ratio * 127); // 0 to 127 in GD
                    $color = imagecolorallocatealpha($outImg, $r, $g, $b, $alpha);

                    if ($alpha < 100) {
                        if ($x < $minX) $minX = $x;
                        if ($x > $maxX) $maxX = $x;
                        if ($y < $minY) $minY = $y;
                        if ($y > $maxY) $maxY = $y;
                        $inkPixels++;
                    }
                }

                imagesetpixel($outImg, $x, $y, $color);
            }
        }

        imagedestroy($srcImg);

        // Auto-Crop with 12px padding around signature
        $finalImg = $outImg;
        if ($inkPixels > 10 && $minX <= $maxX && $minY <= $maxY) {
            $padding = 12;
            $cropX = max(0, $minX - $padding);
            $cropY = max(0, $minY - $padding);
            $cropW = min($origWidth - $cropX, ($maxX - $minX) + ($padding * 2));
            $cropH = min($origHeight - $cropY, ($maxY - $minY) + ($padding * 2));

            $cropped = imagecreatetruecolor($cropW, $cropH);
            imagealphablending($cropped, false);
            imagesavealpha($cropped, true);

            // Fill transparent
            $trans = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
            imagefill($cropped, 0, 0, $trans);

            imagecopy($cropped, $outImg, 0, 0, $cropX, $cropY, $cropW, $cropH);
            imagedestroy($outImg);
            $finalImg = $cropped;
        }

        // Ensure directory exists
        $destDir = dirname($destinationPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0777, true);
        }

        $saved = imagepng($finalImg, $destinationPath, 9);
        imagedestroy($finalImg);

        if (!$saved) {
            return ['success' => false, 'message' => 'Failed to save processed transparent PNG image.'];
        }

        return [
            'success' => true,
            'message' => 'Signature extracted and saved successfully.',
            'path' => $destinationPath,
        ];
    }
}
