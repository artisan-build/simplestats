<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class FileHelper
{
    public static function getFileExtensions4NonWebExecutables(): array
    {
        return [
            // images
            'jpg', 'jpeg', 'png', 'apng', 'gif', 'bmp', 'webp', 'svg', 'tif', 'tiff', 'avif', 'ico',
            // documents
            'css', 'js', 'js.map', 'json', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'xml', 'yaml',
            // media
            'mp3', 'wav', 'flac', 'mp4', 'avi', 'mov', 'mkv', 'webm', 'ogg', 'wmv',
            // archive
            'zip', 'tar', 'gz', '7z', 'rar', 'bak', 'sql', 'tgz',
            // server
            'env', 'ini', 'sh', 'inc', 'exe', 'settings', 'back', 'fileupload', 'conf',
        ];
    }

    public static function hasNonExecutableWebFileExtension(string $filename): bool
    {
        return Str::endsWith(Str::lower($filename), self::getFileExtensions4NonWebExecutables());
    }
}
