<?php

namespace App\Services;

use RuntimeException;

class ImageProcessingService
{
    public const STANDARD_WIDTH = 1920;
    public const STANDARD_HEIGHT = 1080;

    /**
     * Resize uploaded image to standard dimensions (1920x1080) with letterboxing.
     *
     * This method preserves aspect ratio by using letterboxing (black bars)
     * to prevent distortion of logos, text, or design elements.
     *
     * @param string $imageContent Binary image data
     * @param string $mimeType Image MIME type
     * @return array ['content' => binary, 'mime_type' => string]
     * @throws RuntimeException if GD operations fail
     */
    public function resizeToStandard(string $imageContent, string $mimeType): array
    {
        if (!extension_loaded('gd')) {
            throw new RuntimeException('GD extension is required for image processing.');
        }

        // Create GD image from binary string
        $sourceImage = @imagecreatefromstring($imageContent);
        if (!$sourceImage) {
            throw new RuntimeException('Unable to read image data. Invalid or corrupted image.');
        }

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        // Check if already standard size
        if ($sourceWidth === self::STANDARD_WIDTH && $sourceHeight === self::STANDARD_HEIGHT) {
            imagedestroy($sourceImage);
            return [
                'content' => $imageContent,
                'mime_type' => 'image/png',
            ];
        }

        // Create destination canvas with standard dimensions
        $destImage = imagecreatetruecolor(self::STANDARD_WIDTH, self::STANDARD_HEIGHT);
        if (!$destImage) {
            imagedestroy($sourceImage);
            throw new RuntimeException('Unable to create destination image canvas.');
        }

        // Fill with black background (letterboxing)
        $black = imagecolorallocate($destImage, 0, 0, 0);
        imagefilledrectangle($destImage, 0, 0, self::STANDARD_WIDTH, self::STANDARD_HEIGHT, $black);

        // Calculate scale factor to fit within standard dimensions while preserving aspect ratio
        $scaleWidth = self::STANDARD_WIDTH / $sourceWidth;
        $scaleHeight = self::STANDARD_HEIGHT / $sourceHeight;
        $scale = min($scaleWidth, $scaleHeight);

        $newWidth = (int) round($sourceWidth * $scale);
        $newHeight = (int) round($sourceHeight * $scale);

        // Calculate position to center the scaled image
        $destX = (int) round((self::STANDARD_WIDTH - $newWidth) / 2);
        $destY = (int) round((self::STANDARD_HEIGHT - $newHeight) / 2);

        // Resize and copy with high quality
        $success = imagecopyresampled(
            $destImage,
            $sourceImage,
            $destX,
            $destY,
            0,
            0,
            $newWidth,
            $newHeight,
            $sourceWidth,
            $sourceHeight
        );

        if (!$success) {
            imagedestroy($sourceImage);
            imagedestroy($destImage);
            throw new RuntimeException('Failed to resize image.');
        }

        // Convert to PNG binary with best compression
        ob_start();
        imagepng($destImage, null, 9); // Compression level 9 (best)
        $binary = ob_get_clean();

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($destImage);

        if ($binary === false) {
            throw new RuntimeException('Failed to encode image as PNG.');
        }

        return [
            'content' => $binary,
            'mime_type' => 'image/png',
        ];
    }

    /**
     * Get dimensions of image from binary content without loading full GD resource.
     *
     * This is a fast, memory-efficient way to check image dimensions.
     *
     * @param string $imageContent Binary image data
     * @return array ['width' => int, 'height' => int]
     * @throws RuntimeException if unable to determine dimensions
     */
    public function getImageDimensions(string $imageContent): array
    {
        $imageInfo = @getimagesizefromstring($imageContent);

        if ($imageInfo === false) {
            throw new RuntimeException('Unable to determine image dimensions. Invalid image data.');
        }

        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
        ];
    }

    /**
     * Check if image dimensions match standard (1920x1080).
     *
     * @param string $imageContent Binary image data
     * @return bool True if dimensions are exactly 1920x1080
     */
    public function isStandardDimension(string $imageContent): bool
    {
        try {
            $dimensions = $this->getImageDimensions($imageContent);
            return $dimensions['width'] === self::STANDARD_WIDTH
                && $dimensions['height'] === self::STANDARD_HEIGHT;
        } catch (RuntimeException $e) {
            return false;
        }
    }
}
