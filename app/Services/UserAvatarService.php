<?php

namespace App\Services;

use App\Exceptions\UserAvatarException;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Exceptions\ImageException;
use Intervention\Image\ImageManager;
use Throwable;

class UserAvatarService
{
    private const MAX_DIMENSION = 4096;

    private const OUTPUT_SIZE = 512;

    public function replace(User $user, UploadedFile $file): string
    {
        try {
            $image = (new ImageManager(new Driver))
                ->decodePath($file->getRealPath())
                ->orient();

            if ($image->width() > self::MAX_DIMENSION || $image->height() > self::MAX_DIMENSION) {
                throw new UserAvatarException(
                    'AVATAR_DIMENSIONS_EXCEEDED',
                    422,
                    __('Kích thước ảnh đại diện vượt quá giới hạn cho phép.')
                );
            }

            $encoded = $image
                ->cover(self::OUTPUT_SIZE, self::OUTPUT_SIZE)
                ->encode(new WebpEncoder(quality: 85));
        } catch (UserAvatarException $e) {
            throw $e;
        } catch (ImageException|\InvalidArgumentException $e) {
            throw new UserAvatarException(
                'AVATAR_INVALID_IMAGE',
                422,
                __('Tệp ảnh đại diện không hợp lệ.'),
                $e
            );
        } catch (Throwable $e) {
            throw new UserAvatarException(
                'AVATAR_PROCESSING_FAILED',
                422,
                __('Không thể xử lý ảnh đại diện này.'),
                $e
            );
        }

        $newPath = sprintf('avatars/%d/%s.webp', $user->getKey(), Str::random(40));

        try {
            $stored = Storage::disk('public')->put($newPath, (string) $encoded, [
                'visibility' => 'public',
            ]);
        } catch (Throwable $e) {
            throw new UserAvatarException(
                'AVATAR_STORAGE_FAILED',
                503,
                __('Không thể lưu ảnh đại diện vào lúc này.'),
                $e
            );
        }

        if (! $stored) {
            throw new UserAvatarException(
                'AVATAR_STORAGE_FAILED',
                503,
                __('Không thể lưu ảnh đại diện vào lúc này.')
            );
        }

        try {
            $oldPath = DB::transaction(function () use ($user, $newPath): ?string {
                $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
                $oldPath = $lockedUser->avatar_path;
                $lockedUser->avatar_path = $newPath;
                $lockedUser->save();

                return $oldPath;
            });
        } catch (Throwable $e) {
            $this->deleteManagedPath($newPath, $user->getKey());

            throw new UserAvatarException(
                'AVATAR_UPDATE_FAILED',
                500,
                __('Không thể cập nhật ảnh đại diện vào lúc này.'),
                $e
            );
        }

        $user->avatar_path = $newPath;
        $this->deleteManagedPath($oldPath, $user->getKey());

        return $this->urlFor($user);
    }

    public function remove(User $user): ?string
    {
        try {
            $oldPath = DB::transaction(function () use ($user): ?string {
                $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
                $oldPath = $lockedUser->avatar_path;
                $lockedUser->avatar_path = null;
                $lockedUser->save();

                return $oldPath;
            });
        } catch (Throwable $e) {
            throw new UserAvatarException(
                'AVATAR_UPDATE_FAILED',
                500,
                __('Không thể xóa ảnh đại diện vào lúc này.'),
                $e
            );
        }

        $user->avatar_path = null;
        $this->deleteManagedPath($oldPath, $user->getKey());

        return $this->urlFor($user);
    }

    public function urlFor(User $user): ?string
    {
        if ($this->isManagedPath($user->avatar_path, $user->getKey())) {
            $url = Storage::disk('public')->url($user->avatar_path);
            if (! Str::startsWith($url, ['http://', 'https://'])) {
                $url = rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
            }

            return $this->canonicalHttpsUrl($url);
        }

        return $this->canonicalHttpsUrl($user->avatar);
    }

    public function deleteManagedPath(?string $path, int|string $userId): bool
    {
        if (! $this->isManagedPath($path, $userId)) {
            return false;
        }

        try {
            return Storage::disk('public')->delete($path);
        } catch (Throwable $e) {
            Log::warning('Managed user avatar cleanup failed.', [
                'user_id' => $userId,
                'exception' => $e::class,
            ]);

            return false;
        }
    }

    private function isManagedPath(?string $path, int|string $userId): bool
    {
        if (! is_string($path) || $path === '') {
            return false;
        }

        return preg_match(
            '#^avatars/'.preg_quote((string) $userId, '#').'/[A-Za-z0-9]{40}\.webp$#',
            $path
        ) === 1;
    }

    private function canonicalHttpsUrl(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);
        $parts = parse_url($url);
        if (! is_array($parts)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)) {
            return null;
        }

        return preg_replace('#^http://#i', 'https://', $url);
    }
}
