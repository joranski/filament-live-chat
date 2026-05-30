<?php

declare(strict_types=1);

namespace Joranski\FilamentLiveChat\Chat\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class LiveChatWidget extends Widget
{
    protected string $view = 'filament-live-chat::live-chat-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public ?Model $record = null;

    public bool $isOpen = false;

    public function openChat(): void
    {
        $this->isOpen = true;
    }

    public function closeChat(): void
    {
        $this->isOpen = false;
    }
}
