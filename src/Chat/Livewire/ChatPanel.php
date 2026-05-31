<?php

declare(strict_types=1);

namespace Joranski\FilamentLiveChat\Chat\Livewire;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Joranski\FilamentComments\Concerns\InteractsWithCommentMentionAutocomplete;
use Joranski\FilamentComments\Support\CommentAuthor;
use Joranski\FilamentComments\Support\CommentContentRenderer;
use Joranski\FilamentComments\Support\CommentGroups;
use Joranski\FilamentComments\Support\CommentMentionNotifier;
use Joranski\FilamentComments\Support\CommentMentionParser;
use Joranski\FilamentComments\Support\CommentModels;
use Joranski\FilamentLiveChat\Chat\Support\CollaboratorPresence;
use Joranski\FilamentLiveChat\Chat\Support\PromoteChatMessage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatPanel extends Component implements HasForms
{
    use InteractsWithCommentMentionAutocomplete;
    use InteractsWithForms;

    public ?Model $record = null;

    /** @var array<string, mixed> */
    public array $chatFormData = [
        'body' => null,
    ];

    public function mount(?Model $record = null): void
    {
        $this->record = $record;
        $this->recordPresence();
        $this->form->fill(['body' => null]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('body')
                    ->hiddenLabel()
                    ->placeholder(__('Quick coordination — use @Name to mention someone'))
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->statePath('chatFormData');
    }

    #[Computed]
    public function chatMessages(): Collection
    {
        if (! $this->record?->exists || ! method_exists($this->record, 'comments')) {
            return collect();
        }

        $limit = (int) config('filament-live-chat.message_limit', 100);

        return $this->record->comments()
            ->with('user')
            ->activeChat()
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    #[Computed]
    public function viewers(): Collection
    {
        if (! $this->record?->exists) {
            return collect();
        }

        return app(CollaboratorPresence::class)->viewers(
            record: $this->record,
            except: auth()->user(),
        );
    }

    public function addMessage(): void
    {
        if (! auth()->user()?->can('create', CommentModels::commentClass())) {
            return;
        }

        if (! $this->record?->exists || ! method_exists($this->record, 'comments')) {
            return;
        }

        $body = trim((string) ($this->form->getState()['body'] ?? ''));

        if (! filled(strip_tags($body)) || strlen(trim(strip_tags($body))) < 2) {
            Notification::make()
                ->title(__('Message must be at least 2 characters.'))
                ->warning()
                ->send();

            return;
        }

        $comment = $this->record->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $body,
            'active' => true,
            'group' => config('filament-live-chat.group', CommentGroups::CHAT),
            'mentioned_user_ids' => app(CommentMentionParser::class)->parseUserIds($body),
        ]);

        app(CommentMentionNotifier::class)->notify(
            comment: $comment,
            commentable: $this->record,
            author: auth()->user(),
        );

        $this->form->fill(['body' => null]);
        unset($this->chatMessages);

        Notification::make()
            ->title(__('Message sent.'))
            ->success()
            ->send();
    }

    public function promoteMessage(int $messageId): void
    {
        if (! auth()->user()?->can('create', CommentModels::commentClass()) || ! $this->record?->exists) {
            return;
        }

        $message = $this->record->comments()->activeChat()->find($messageId);

        if (! $message instanceof Model) {
            return;
        }

        app(PromoteChatMessage::class)(
            chatMessage: $message,
            commentable: $this->record,
            promotedBy: auth()->user(),
        );

        unset($this->chatMessages);

        Notification::make()
            ->title(__('Saved to comments.'))
            ->success()
            ->send();
    }

    public function deleteMessage(int $messageId): void
    {
        $message = $this->record?->comments()->activeChat()->find($messageId);

        if (! $message instanceof Model || ! CommentAuthor::canDelete($message)) {
            return;
        }

        $message->delete();
        unset($this->chatMessages);

        Notification::make()
            ->title(__('Message deleted.'))
            ->success()
            ->send();
    }

    public function renderMessageBody(Model $message): string
    {
        return (string) app(CommentContentRenderer::class)->render(
            html: (string) ($message->comment ?? ''),
            mentionedUserIds: $message->mentioned_user_ids,
        );
    }

    #[On('chat-panel-refresh')]
    public function refreshPanel(): void
    {
        $this->recordPresence();
        unset($this->chatMessages, $this->viewers);
    }

    public function recordPresence(): void
    {
        if (! $this->record?->exists || ! auth()->user()) {
            return;
        }

        app(CollaboratorPresence::class)->heartbeat(
            record: $this->record,
            user: auth()->user(),
        );
    }

    public function render(): View
    {
        return view('filament-live-chat::chat-panel');
    }
}
