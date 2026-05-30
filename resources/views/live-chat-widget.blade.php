@php
    use Joranski\FilamentLiveChat\Chat\Livewire\ChatPanel;
    use Joranski\FilamentLiveChat\Chat\Support\CollaboratorPresence;
    use Joranski\FilamentComments\Support\CommentAuthor;

    $viewerCount = ($record?->exists ?? false)
        ? app(CollaboratorPresence::class)->viewerCount(record: $record, except: auth()->user())
        : 0;
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('Live coordination') }}
        </x-slot>

        <x-slot name="description">
            @if ($viewerCount > 0)
                {{ trans_choice(':count other person is|:count other people are', $viewerCount, ['count' => $viewerCount]) }}
                {{ __('viewing this record right now.') }}
            @else
                {{ __('Quick back-and-forth while working this record. Promote important messages to the audit comments.') }}
            @endif
        </x-slot>

        <div class="flex flex-wrap items-center gap-3">
            @if ($viewerCount > 0 && $record?->exists)
                <div class="flex -space-x-2">
                    @foreach (app(CollaboratorPresence::class)->viewers(record: $record, except: auth()->user()) as $viewer)
                        <flux:avatar
                            size="sm"
                            :name="CommentAuthor::displayName($viewer)"
                            class="ring-2 ring-white dark:ring-gray-900"
                        />
                    @endforeach
                </div>
            @endif

            <flux:button type="button" variant="primary" icon="chat-bubble-left-right" wire:click="openChat">
                {{ __('Open live chat') }}
            </flux:button>
        </div>
    </x-filament::section>

    <flux:modal wire:model="isOpen" class="w-full max-w-2xl">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Live coordination') }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                {{ __('Messages here are for quick coordination. Use “Save to comments” to add important notes to the audit trail.') }}
            </flux:text>

            @if ($record?->exists)
                @livewire(
                    ChatPanel::class,
                    ['record' => $record],
                    key('live-chat-panel-'.($record->getKey() ?? 'new'))
                )
            @endif

            <div class="flex justify-end">
                <flux:button type="button" variant="ghost" wire:click="closeChat">
                    {{ __('Close') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</x-filament-widgets::widget>
