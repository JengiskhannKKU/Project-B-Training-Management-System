<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use Carbon\CarbonInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use RuntimeException;

class CertificateRenderer
{
    private const DEFAULT_CANVAS_WIDTH = 1600;
    private const DEFAULT_CANVAS_HEIGHT = 1200;
    private const DEFAULT_DATE_FORMAT = 'Y-m-d';

    public function buildCertificateData(Certificate $certificate): array
    {
        $certificate->loadMissing([
            'user:id,name',
            'program:id,name',
            'session:id,title,program_id,start_date,end_date',
            'session.program:id,name',
            'issuer:id,name',
            'enrollment.session:id,title,program_id,start_date,end_date',
            'enrollment.session.program:id,name',
        ]);

        $session = $certificate->session ?? $certificate->enrollment?->session;
        $program = $certificate->program ?? $session?->program;

        return [
            'name' => $certificate->user?->name,
            'program' => $program?->name,
            'session' => $session?->title,
            'course' => $session?->title ?? $program?->name,
            'start_date' => $session?->start_date,
            'end_date' => $session?->end_date,
            'issued_at' => $certificate->issued_at,
            'issued_by' => $certificate->issuer?->name,
            'certificate_code' => $certificate->certificate_code,
            'verify_url' => url("/verify/{$certificate->certificate_code}"),
        ];
    }

    public function render(Certificate $certificate, CertificateTemplate $template): array
    {
        if (!extension_loaded('gd')) {
            throw new RuntimeException('GD extension is required for certificate rendering.');
        }

        $data = $this->buildCertificateData($certificate);
        $layout = is_array($template->layout_config) ? $template->layout_config : [];

        $image = $this->createCanvas($template, $layout);
        $defaultColor = $this->resolveColor($image, $template->text_color);

        foreach ($layout as $field => $config) {
            if ($field === 'qr' || $field === 'canvas') {
                continue;
            }

            $value = $data[$field] ?? null;
            if ($value === null) {
                continue;
            }

            $this->drawText($image, $value, (array) $config, $template, $defaultColor);
        }

        if (isset($layout['qr'])) {
            $this->drawQr($image, $data, (array) $layout['qr']);
        }

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        unset($image);

        return [
            'mime_type' => 'image/png',
            'binary' => $binary,
        ];
    }

    private function createCanvas(CertificateTemplate $template, array $layout)
    {
        if ($template->background_image) {
            $image = imagecreatefromstring($template->background_image);
            if (!$image) {
                throw new RuntimeException('Unable to read certificate background image.');
            }

            return $image;
        }

        $width = (int) ($layout['canvas']['width'] ?? self::DEFAULT_CANVAS_WIDTH);
        $height = (int) ($layout['canvas']['height'] ?? self::DEFAULT_CANVAS_HEIGHT);
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $width, $height, $white);

        return $image;
    }

    private function drawText($image, $value, array $config, CertificateTemplate $template, ?int $defaultColor): void
    {
        $canvasWidth = imagesx($image);
        $canvasHeight = imagesy($image);

        $x = $this->resolveCoordinate($config['x'] ?? 0, $canvasWidth);
        $y = $this->resolveCoordinate($config['y'] ?? 0, $canvasHeight);

        // Support both 'fontSize' and 'size' for backward compatibility
        $size = (int) ($config['fontSize'] ?? $config['size'] ?? $template->font_size ?? 16);
        $color = $this->resolveColor($image, $config['color'] ?? $template->text_color) ?? $defaultColor;

        if ($color === null) {
            $color = imagecolorallocate($image, 0, 0, 0);
        }

        if ($value instanceof CarbonInterface) {
            $format = $config['format'] ?? self::DEFAULT_DATE_FORMAT;
            $value = $value->format($format);
        }

        $text = (string) $value;

        // Get font style if specified
        $fontStyle = $config['fontStyle'] ?? 'normal';
        $fontFamily = $config['fontFamily'] ?? $config['font'] ?? $template->font_family;

        // Resolve font path with style support
        $fontPath = $this->resolveFontPathWithStyle($fontFamily, $fontStyle);

        if ($fontPath) {
            // imagettftext treats y as the baseline.
            imagettftext($image, $size, 0, $x, $y, $color, $fontPath, $text);
            return;
        }

        $font = $this->mapToBuiltInFont($size);
        imagestring($image, $font, $x, $y, $text, $color);
    }

    private function drawQr($image, array $data, array $config): void
    {
        $payload = $data['verify_url'] ?? $data['certificate_code'] ?? null;
        if (!$payload) {
            return;
        }

        $canvasWidth = imagesx($image);
        $canvasHeight = imagesy($image);

        $size = (int) ($config['size'] ?? 160);
        $x = $this->resolveCoordinate($config['x'] ?? 0, $canvasWidth);
        $y = $this->resolveCoordinate($config['y'] ?? 0, $canvasHeight);
        $width = $this->resolveCoordinate($config['width'] ?? $size, $canvasWidth);
        $height = $this->resolveCoordinate($config['height'] ?? $size, $canvasHeight);

        $qrBinary = $this->buildQrCode($payload, $size);
        $qrImage = imagecreatefromstring($qrBinary);
        if (!$qrImage) {
            return;
        }

        imagecopyresampled(
            $image,
            $qrImage,
            $x,
            $y,
            0,
            0,
            $width,
            $height,
            imagesx($qrImage),
            imagesy($qrImage)
        );

        unset($qrImage);
    }

    private function buildQrCode(string $payload, int $size): string
    {
        $result = (new Builder())->build(
            writer: new PngWriter(),
            data: $payload,
            size: $size,
            margin: 0
        );

        return $result->getString();
    }

    private function resolveFontPath(?string $fontFamily): ?string
    {
        if (!$fontFamily) {
            return null;
        }

        if (is_file($fontFamily)) {
            return $fontFamily;
        }

        $storagePath = storage_path('app/fonts/' . $fontFamily);
        if (is_file($storagePath)) {
            return $storagePath;
        }

        return null;
    }

    private function resolveFontPathWithStyle(?string $fontFamily, string $fontStyle = 'normal'): ?string
    {
        if (!$fontFamily) {
            return null;
        }

        // If fontStyle is normal, use the original font
        if ($fontStyle === 'normal') {
            return $this->resolveFontPath($fontFamily);
        }

        // Try to find a styled font variant
        // For example: Prompt-Regular.ttf -> Prompt-Bold.ttf or Prompt-Italic.ttf
        $styledFontFamily = $this->getStyledFontName($fontFamily, $fontStyle);

        // Try the styled font first
        $styledPath = $this->resolveFontPath($styledFontFamily);
        if ($styledPath) {
            return $styledPath;
        }

        // Fallback to original font if styled version not found
        return $this->resolveFontPath($fontFamily);
    }

    private function getStyledFontName(string $fontFamily, string $fontStyle): string
    {
        // Common patterns for font file naming
        $patterns = [
            'bold' => ['Regular' => 'Bold', 'regular' => 'bold', '-Regular' => '-Bold', '-regular' => '-bold'],
            'italic' => ['Regular' => 'Italic', 'regular' => 'italic', '-Regular' => '-Italic', '-regular' => '-italic'],
        ];

        if (!isset($patterns[$fontStyle])) {
            return $fontFamily;
        }

        // Try to replace common patterns
        foreach ($patterns[$fontStyle] as $search => $replace) {
            if (str_contains($fontFamily, $search)) {
                return str_replace($search, $replace, $fontFamily);
            }
        }

        // If no pattern matched, try to insert style before extension
        // Example: MyFont.ttf -> MyFont-Bold.ttf
        $extension = pathinfo($fontFamily, PATHINFO_EXTENSION);
        $baseName = pathinfo($fontFamily, PATHINFO_FILENAME);

        if ($extension) {
            $styleCapitalized = ucfirst($fontStyle);
            return "{$baseName}-{$styleCapitalized}.{$extension}";
        }

        return $fontFamily;
    }

    private function resolveColor($image, ?string $color): ?int
    {
        if (!$color) {
            return null;
        }

        $color = trim($color);
        if (preg_match('/^#?([0-9a-fA-F]{3})$/', $color, $matches) === 1) {
            $hex = $matches[1];
            $color = sprintf(
                '%s%s%s%s%s%s',
                $hex[0],
                $hex[0],
                $hex[1],
                $hex[1],
                $hex[2],
                $hex[2]
            );
        } elseif (preg_match('/^#?([0-9a-fA-F]{6})$/', $color, $matches) === 1) {
            $color = $matches[1];
        } else {
            return null;
        }

        $red = hexdec(substr($color, 0, 2));
        $green = hexdec(substr($color, 2, 2));
        $blue = hexdec(substr($color, 4, 2));

        return imagecolorallocate($image, $red, $green, $blue);
    }

    private function mapToBuiltInFont(int $size): int
    {
        if ($size >= 24) {
            return 5;
        }

        if ($size >= 18) {
            return 4;
        }

        if ($size >= 14) {
            return 3;
        }

        if ($size >= 10) {
            return 2;
        }

        return 1;
    }

    /**
     * Resolve coordinate value (supports both pixel integers and percentage strings).
     *
     * @param mixed $value Coordinate value (int or "50%")
     * @param int $dimension Canvas dimension (width or height)
     * @return int Resolved pixel value
     */
    private function resolveCoordinate($value, int $dimension): int
    {
        // Handle percentage strings (e.g., "50%")
        if (is_string($value) && str_ends_with($value, '%')) {
            $percentage = (float) rtrim($value, '%');
            return (int) round($dimension * $percentage / 100);
        }

        // Handle pixel values (int or numeric string)
        return (int) $value;
    }
}
