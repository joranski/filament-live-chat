@php
    use Joranski\FilamentComments\Support\CommentAuthor;

    $author = $message->user;
    $canDelete = CommentAuthor::canDelete($message);
    $canPromote = auth()->user()?->can('create', config('filament-comments.comment_model')) ?? false;
@endphp

<div class="flex items-start gap-3 p-4" wire:key="chat-message-{{ $message->id }}">
    <flux:avatar
        size="sm"
        :name="CommentAuthor::displayName($author)"
        class="shrink-0"
    />

    <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                @include('filament-comments::partials.message-author', [
                    'author' => $author,
                    'comment' => $message,
                ])
            </div>

            <div class="flex shrink-0 items-center gap-1">
                @if ($canPromote)
                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        icon="arrow-up-on-square"
                        wire:click="promoteMessage({{ $message->id }})"
                        wire:loading.attr="disabled"
                        wire:target="promoteMessage({{ $message->id }})"
                        :title="__('Save to comments')"
                    />
                @endif

                @if ($canDelete)
                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        icon="trash"
                        color="danger"
                        wire:click="deleteMessage({{ $message->id }})"
                        wire:confirm="{{ __('Delete this message?') }}"
                        wire:loading.attr="disabled"
                        wire:target="deleteMessage({{ $message->id }})"
                    />
                @endif
            </div>
        </div>

        <div class="prose prose-sm dark:prose-invert mt-2 max-w-none text-zinc-700 dark:text-zinc-300 [&>*:first-child]:mt-0 [&>*:last-child]:mb-0">
            {!! $this->renderMessageBody($message) !!}
        </div>
    </div>
</div>
