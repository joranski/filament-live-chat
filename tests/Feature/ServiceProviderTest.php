<?php

declare(strict_types=1);

use Joranski\FilamentLiveChat\Chat\Livewire\ChatPanel;
use Livewire\LivewireManager;

test('service provider registers the chat panel livewire component', function (): void {
    /** @var LivewireManager $livewire */
    $livewire = app(LivewireManager::class);

    expect($livewire->getClassComponent('filament-live-chat.chat-panel'))
        ->toBe(ChatPanel::class);
});
