<?php

namespace App\Support;

use GdImage;

/**
 * Reads the colour a logo paints its own corners with, so the card behind it can match the mark.
 */
final class LogoTone
{
    public const WHITE = '#ffffff';

    /**
     * @return array{hex: string, dark: bool}|null
     */
    public static function fromBinary(string $binary): ?array
    {
        if ($binary === '' || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $image = @imagecreatefromstring($binary);

        if (! $image instanceof GdImage) {
            return null;
        }

        try {
            if (! imageistruecolor($image)) {
                imagepalettetotruecolor($image);
            }

            $pixels = self::edgePixels($image);
        } finally {
            imagedestroy($image);
        }

        if (count($pixels) < 8) {
            return null;
        }

        $hex = sprintf(
            '#%02x%02x%02x',
            self::median(array_column($pixels, 0)),
            self::median(array_column($pixels, 1)),
            self::median(array_column($pixels, 2)),
        );

        return ['hex' => $hex, 'dark' => self::luminance($hex) < 150];
    }

    public static function isDark(?string $hex): bool
    {
        return $hex ? self::luminance($hex) < 150 : false;
    }

    /**
     * Only the border is sampled: it is the paper the mark is printed on, while the middle holds the logo itself.
     *
     * @return list<array{int, int, int}>
     */
    private static function edgePixels(GdImage $image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $spots = [];

        for ($step = 1; $step <= 6; $step++) {
            $x = (int) max(0, min($width - 1, intdiv($width * $step, 7)));
            $y = (int) max(0, min($height - 1, intdiv($height * $step, 7)));

            $spots[] = [$x, 0];
            $spots[] = [$x, $height - 1];
            $spots[] = [0, $y];
            $spots[] = [$width - 1, $y];
        }

        $pixels = [];

        foreach ($spots as [$x, $y]) {
            $pixel = self::pixel($image, $x, $y);

            if ($pixel !== null) {
                $pixels[] = $pixel;
            }
        }

        return $pixels;
    }

    /**
     * @return array{int, int, int}|null
     */
    private static function pixel(GdImage $image, int $x, int $y): ?array
    {
        $color = imagecolorat($image, $x, $y);

        // A transparent edge lets the card through, so it says nothing about the logo's own background.
        if ((($color >> 24) & 0x7F) > 96) {
            return null;
        }

        return [($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF];
    }

    private static function median(array $values): int
    {
        sort($values);
        $middle = intdiv(count($values), 2);

        return count($values) % 2 === 1
            ? (int) $values[$middle]
            : (int) round(((int) $values[$middle - 1] + (int) $values[$middle]) / 2);
    }

    private static function luminance(string $hex): int
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x') ?: [255, 255, 255];

        return (int) round(0.299 * $r + 0.587 * $g + 0.114 * $b);
    }
}
