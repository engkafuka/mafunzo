<?php

namespace App\Support;

class PdfImageDataUri
{
    /**
     * Embed-friendly JPEG data URI for DomPDF (works without GD for JPEG).
     */
    public static function jpegForPdf(string $absolutePath): ?string
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return null;
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg'], true)) {
            return self::jpegDataUriFromFile($absolutePath);
        }

        if (extension_loaded('gd')) {
            return self::convertToJpegDataUriWithGd($absolutePath, $extension);
        }

        if (extension_loaded('imagick') && class_exists(\Imagick::class)) {
            return self::convertToJpegDataUriWithImagick($absolutePath);
        }

        $jpegFallback = self::jpegFallbackPath($absolutePath);
        if ($jpegFallback !== null) {
            return self::jpegDataUriFromFile($jpegFallback);
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

    private static function jpegDataUriFromFile(string $absolutePath): ?string
    {
        $contents = file_get_contents($absolutePath);

        return $contents === false
            ? null
            : 'data:image/jpeg;base64,'.base64_encode($contents);
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
