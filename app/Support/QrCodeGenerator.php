<?php

namespace App\Support;

use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeGenerator
{
    public static function pngDataUri(string $content, int $size = 200): string
    {
        $scale = max(1, (int) round($size / 25));
        $outputInterface = extension_loaded('gd') ? QRGdImagePNG::class : QRMarkupSVG::class;

        $options = new QROptions([
            'outputInterface' => $outputInterface,
            'scale' => $scale,
            'outputBase64' => true,
        ]);

        $dataUri = (new QRCode($options))->render($content);

        if (str_starts_with($dataUri, 'data:')) {
            return $dataUri;
        }

        $mime = $outputInterface === QRGdImagePNG::class ? 'image/png' : 'image/svg+xml';

        return 'data:'.$mime.';base64,'.$dataUri;
    }
}
