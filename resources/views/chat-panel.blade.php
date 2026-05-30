@php
    use Joranski\FilamentComments\Support\CommentAuthor;

    $canCreate = auth()->user()?->can('create', config('filament-comments.comment_model')) ?? false;
    $pollInterval = (string) config('filament-live-chat.poll_interval', '4s');
@endphp

<div
    class="fi-chat-panel flex flex-col gap-4"
    wire:poll.4s="refreshPanel"
>
    @if ($this->viewers->isNotEmpty())
        <flux:callout variant="secondary" icon="users">
            {{ __('Also here: :names', [
                'names' => $this->viewers->map(fn ($user) => CommentAuthor::displayName($user))->implode(', '),
            ]) }}
        </flux:callout>
    @endif

    @if ($canCreate)
        <div class="flex flex-col gap-3">
            {{ $this->form }}

            <div>
                <flux:button
                    type="button"
                    variant="primary"
                    wire:click="addMessage"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="addMessage">{{ __('Send') }}</span>
                    <span wire:loading wire:target="addMessage">{{ __('Sending…') }}</span>
                </flux:button>
            </div>
        </div>
    @endif

    @if ($this->chatMessages->isNotEmpty())
        <div class="max-h-96 overflow-y-auto rounded-xl border border-zinc-200 dark:border-white/10 divide-y divide-zinc-200 dark:divide-white/10 bg-white dark:bg-white/5">
            @foreach ($this->chatMessages as $message)
                @include('filament-live-chat::partials.message-item', ['message' => $message])
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 dark:border-white/15 px-6 py-10 text-center text-zinc-500 dark:text-zinc-400">
            <flux:icon.chat-bubble-left-right class="mb-2 size-10" />
            <flux:text size="sm">{{ __('No live messages yet. Start coordinating with your team.') }}</flux:text>
        </div>
    @endif
</div>
