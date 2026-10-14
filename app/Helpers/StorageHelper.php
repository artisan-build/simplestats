<?php

namespace App\Helpers;

class StorageHelper
{
    /**
     * The disk every process of this installation can read and write.
     *
     * On a single server that is the local disk, which is what SimpleStats has
     * always used: an upload lands in storage/app and the queue worker that
     * picks it up runs on the same machine. When the web and queue processes
     * run in separate containers, the local disk is private to each of them,
     * so anything one process hands to another has to live somewhere both can
     * reach. Laravel Cloud attaches object storage for that and the framework
     * registers it as a disk, so its name is the right answer there.
     *
     * @param  mixed  $platformDiskConfig  LARAVEL_CLOUD_DISK_CONFIG, read in the config file
     */
    public static function sharedDisk(mixed $platformDiskConfig): string
    {
        return self::platformDisk($platformDiskConfig) ?? 'local';
    }

    /**
     * The name of the object storage disk the hosting platform attached to this
     * installation, or null when there is none.
     *
     * Laravel Cloud passes the attached buckets in LARAVEL_CLOUD_DISK_CONFIG
     * and Illuminate\Foundation\Cloud registers each one under
     * config('filesystems.disks.<name>') while the configuration loads.
     *
     * @param  mixed  $platformDiskConfig  the raw LARAVEL_CLOUD_DISK_CONFIG value
     */
    public static function platformDisk(mixed $platformDiskConfig): ?string
    {
        if (! laravel_cloud() || ! is_string($platformDiskConfig)) {
            return null;
        }

        $disks = json_decode($platformDiskConfig, true);

        if (! is_array($disks)) {
            return null;
        }

        $names = [];

        foreach ($disks as $disk) {
            if (! is_array($disk) || ! isset($disk['disk']) || ! is_string($disk['disk'])) {
                continue;
            }

            // Cloud lets one of the attached buckets be the default disk.
            if ($disk['is_default'] ?? false) {
                return $disk['disk'];
            }

            $names[] = $disk['disk'];
        }

        return $names[0] ?? null;
    }
}
