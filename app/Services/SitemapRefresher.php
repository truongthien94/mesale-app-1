<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

final class SitemapRefresher
{
    private static bool $scheduled = false;

    public static function afterResponse(): void
    {
        if (self::$scheduled) {
            return;
        }

        self::$scheduled = true;

        app()->terminating(static function (): void {
            $lock = Cache::lock('sitemap-generate-shared', 300);
            $acquired = false;

            try {
                $acquired = $lock->get();

                if ($acquired) {
                    Artisan::call('sitemap:generate');
                }
            } catch (\Throwable $exception) {
                report($exception);
            } finally {
                if ($acquired) {
                    $lock->release();
                }

                self::$scheduled = false;
            }
        });
    }
}
