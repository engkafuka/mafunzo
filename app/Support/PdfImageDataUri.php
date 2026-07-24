<?php

namespace App\Support;

class PdfImageDataUri
{
    /**
     * Embed-friendly image data URI for DomPDF.
     * Prefers JPEG when conversion is possible; otherwise embeds PNG/JPEG bytes directly
     * so identity-card PDFs work on hosts without the PHP GD extension.
     */
    public static function jpegForPdf(string $absolutePath): ?string
    {
        return self::dataUriForPdf($absolutePath);
    }

    public static function dataUriForPdf(string $absolutePath): ?string
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return null;
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg'], true)) {
            return self::dataUriFromFile($absolutePath, 'image/jpeg');
        }

        if (extension_loaded('gd')) {
            $converted = self::convertToJpegDataUriWithGd($absolutePath, $extension);
            if ($converted !== null) {
                return $converted;
            }
        }

        if (extension_loaded('imagick') && class_exists(\Imagick::class)) {
            $converted = self::convertToJpegDataUriWithImagick($absolutePath);
            if ($converted !== null) {
                return $converted;
            }
        }

        $jpegFallback = self::jpegFallbackPath($absolutePath);
        if ($jpegFallback !== null) {
            return self::dataUriFromFile($jpegFallback, 'image/jpeg');
        }

        // DomPDF can embed PNG without GD when provided as a data URI.
        if ($extension === 'png') {
            return self::dataUriFromFile($absolutePath, 'image/png');
        }

        return null;
    }

    private static function jpegFallbackPath(string $absolutePath): ?string
    {
        $candidate = preg_replace('/\.[^.]+$/', '.jpg', $absolutePath);
        if (is_string($candidate) && is_file($candidate) && is_readable($candidate)) {
            return $candidate;
        }

        return null;
    }

    private static function dataUriFromFile(string $absolutePath, string $mime): ?string
    {
        $contents = file_get_contents($absolutePath);

        return $contents === false
            ? null
            : 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private static function convertToJpegDataUriWithGd(string $absolutePath, string $extension): ?string
    {
        $image = match ($extension) {
            'png' => @imagecreatefrompng($absolutePath),
            'gif' => @imagecreatefromgif($absolutePath),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : false,
            'jpg', 'jpeg' => @imagecreatefromjpeg($absolutePath),
            default => false,
        };

        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 90);
        $jpeg = ob_get_clean();

        imagedestroy($image);
        imagedestroy($canvas);

        return $jpeg === false ? null : 'data:image/jpeg;base64,'.base64_encode($jpeg);
    }

    private static function convertToJpegDataUriWithImagick(string $absolutePath): ?string
    {
        try {
            $imagick = new \Imagick($absolutePath);
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(90);
            $jpeg = $imagick->getImageBlob();

            return 'data:image/jpeg;base64,'.base64_encode($jpeg);
        } catch (\Throwable) {
            return null;
        }
    }
}
