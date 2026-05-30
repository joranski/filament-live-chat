<?php

declare(strict_types=1);

namespace Joranski\FilamentLiveChat;

// @package-candidate score=EXTRACTED signals=1,2,4,5 extracted=2026-05-18
// See docs/extraction-candidates.md — depends on joranski/filament-comments.

use Joranski\FilamentLiveChat\Chat\Livewire\ChatPanel;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentLiveChatServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-live-chat')
            ->hasConfigFile()
            ->hasViews();
    }

    public function packageBooted(): void
    {
        Livewire::component('filament-live-chat.chat-panel', ChatPanel::class);
    }
}
