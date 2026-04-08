<?php

namespace App\Support;

class UploadLimit
{
    public const PROJECT_IMAGE_MAX_KILOBYTES = 10240;
    public const PROJECT_OTHER_PHOTOS_MAX_BYTES = 100 * 1024 * 1024;

    public static function parseShorthandBytes(null|int|string $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => (int) round($number * 1024 * 1024 * 1024),
            'm' => (int) round($number * 1024 * 1024),
            'k' => (int) round($number * 1024),
            default => (int) round($number),
        };
    }

    public static function postMaxBytes(): int
    {
        return self::parseShorthandBytes(ini_get('post_max_size'));
    }

    public static function uploadMaxBytes(): int
    {
        return self::parseShorthandBytes(ini_get('upload_max_filesize'));
    }

    public static function validationMaxBytes(int $kilobytes = 5120): int
    {
        return $kilobytes * 1024;
    }

    public static function effectiveImageMaxBytes(int $kilobytes = 5120): int
    {
        $limits = array_filter([
            self::uploadMaxBytes(),
            self::validationMaxBytes($kilobytes),
        ]);

        return $limits === [] ? 0 : min($limits);
    }

    public static function projectImageMaxKilobytes(): int
    {
        return self::PROJECT_IMAGE_MAX_KILOBYTES;
    }

    public static function effectiveProjectImageMaxBytes(): int
    {
        return self::effectiveImageMaxBytes(self::projectImageMaxKilobytes());
    }

    public static function effectiveTotalMaxBytes(int $targetBytes): int
    {
        $limits = array_filter([
            $targetBytes,
            self::postMaxBytes(),
        ]);

        return $limits === [] ? 0 : min($limits);
    }

    public static function projectOtherPhotosMaxBytes(): int
    {
        return self::PROJECT_OTHER_PHOTOS_MAX_BYTES;
    }

    public static function effectiveProjectOtherPhotosMaxBytes(): int
    {
        return self::effectiveTotalMaxBytes(self::projectOtherPhotosMaxBytes());
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / (1024 * 1024 * 1024), 1), '0'), '.') . ' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / (1024 * 1024), 1), '0'), '.') . ' MB';
        }

        if ($bytes >= 1024) {
            return rtrim(rtrim(number_format($bytes / 1024, 1), '0'), '.') . ' KB';
        }

        return $bytes . ' B';
    }
}
