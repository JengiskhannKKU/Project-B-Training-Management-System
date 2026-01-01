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
        $x = (int) ($config['x'] ?? 0);
        $y = (int) ($config['y'] ?? 0);
        $size = (int) ($config['size'] ?? $template->font_size ?? 16);
        $color = $this->resolveColor($image, $config['color'] ?? $template->text_color) ?? $defaultColor;

        if ($color === null) {
            $color = imagecolorallocate($image, 0, 0, 0);
        }

        if ($value instanceof CarbonInterface) {
            $format = $config['format'] ?? self::DEFAULT_DATE_FORMAT;
            $value = $value->format($format);
        }

        $text = (string) $value;
        $fontPath = $this->resolveFontPath($config['font'] ?? $template->font_family);

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

        $size = (int) ($config['size'] ?? 160);
        $x = (int) ($config['x'] ?? 0);
        $y = (int) ($config['y'] ?? 0);
        $width = (int) ($config['width'] ?? $size);
        $height = (int) ($config['height'] ?? $size);

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
}
