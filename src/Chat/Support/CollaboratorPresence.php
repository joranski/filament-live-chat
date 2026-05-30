<?php

declare(strict_types=1);

namespace Joranski\FilamentLiveChat\Chat\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Joranski\FilamentComments\Support\CommentModels;

final class CollaboratorPresence
{
    public function heartbeat(Model $record, Authenticatable $user): void
    {
        $key = $this->cacheKey($record);
        $ttl = (int) config('filament-live-chat.presence_ttl_seconds', 45);
        $cutoff = now()->subSeconds($ttl)->timestamp;

        /** @var array<int, int> $presence */
        $presence = Cache::get($key, []);
        $presence[(int) $user->getAuthIdentifier()] = now()->timestamp;

        $presence = array_filter(
            $presence,
            fn (int $timestamp): bool => $timestamp >= $cutoff,
        );

        Cache::put($key, $presence, $ttl * 2);
    }

    /**
     * @return Collection<int, Model>
     */
    public function viewers(Model $record, ?Authenticatable $except = null): Collection
    {
        $key = $this->cacheKey($record);
        $ttl = (int) config('filament-live-chat.presence_ttl_seconds', 45);
        $cutoff = now()->subSeconds($ttl)->timestamp;

        /** @var array<int, int> $presence */
        $presence = Cache::get($key, []);

        $userIds = collect($presence)
            ->filter(fn (int $timestamp): bool => $timestamp >= $cutoff)
            ->keys()
            ->map(fn (mixed $id): int => (int) $id)
            ->when($except !== null, fn (Collection $ids) => $ids->reject(
                fn (int $id): bool => $id === (int) $except->getAuthIdentifier(),
            ))
            ->values()
            ->all();

        if ($userIds === []) {
            return collect();
        }

        return CommentModels::userQuery()
            ->whereIn('id', $userIds)
            ->get(['id', 'name']);
    }

    public function viewerCount(Model $record, ?Authenticatable $except = null): int
    {
        return $this->viewers(record: $record, except: $except)->count();
    }

    protected function cacheKey(Model $record): string
    {
        return sprintf(
            'collaboration.presence.%s.%s',
            str_replace('\\', '.', $record->getMorphClass()),
            (string) $record->getKey(),
        );
    }
}
