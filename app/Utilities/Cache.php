<?php
/**
 * Created by PhpStorm.
 * User: reynier.delarosa
 * Date: 16/03/2018
 * Time: 14:49
 */

namespace App\Utilities;


class Cache
{
    public static function InvalidateFileOpCache(string $filename): void
    {
        if (!opcache_invalidate ($filename)){
            throw new \Exception('Unable invalidate cache for file:' + $filename);
        }
    }
}