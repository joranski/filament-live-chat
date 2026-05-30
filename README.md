# joranski/filament-live-chat

Record-scoped **live coordination** for Filament: polling chat, collaborator presence, and a one-click **promote to audit comments** bridge.

Ephemeral chat lives in the same `comments` table as your audit trail (`group = 'chat'`). Important messages can be promoted into permanent comments consumed by [`joranski/filament-comments`](https://github.com/joranski/filament-comments).

---

## Requirements

| Dependency | Version |
|------------|---------|
| PHP | ^8.3 |
| Laravel | ^12 |
| Filament | ^5 |
| Livewire | ^4 |
| Flux UI Pro | ^2 |
| joranski/filament-comments | ^0.1 |

Install comments first — this package reuses its mention parser, notifier, and author helpers.

---

## Quick start

### 1. Install both packages

```bash
composer require joranski/filament-comments
composer require joranski/filament-live-chat
```

Configure comments (required):

```php
// config/filament-comments.php
return [
    'comment_model' => \App\Models\Comment::class,
    'user_model' => \App\Models\User::class,
    'excluded_groups' => [
        \Joranski\FilamentComments\Support\CommentGroups::DELAY,
        \Joranski\FilamentComments\Support\CommentGroups::CHAT, // hide chat from audit panel
    ],
];
```

Publish live-chat config (optional):

```bash
php artisan vendor:publish --tag=filament-live-chat-config
```

### 2. Add chat scopes to your Comment model

Live chat queries `activeChat()` on the comment model:

```php
use Joranski\FilamentComments\Support\CommentGroups;

public const GROUP_CHAT = CommentGroups::CHAT;
public const STATE_PROMOTED = CommentGroups::STATE_PROMOTED;

public function scopeActiveChat($query)
{
    return $query
        ->where('group', self::GROUP_CHAT)
        ->where(function ($q) {
            $q->whereNull('state')
              ->orWhere('state', '!=', self::STATE_PROMOTED);
        });
}

public function isChatMessage(): bool
{
    return $this->group === self::GROUP_CHAT;
}

public function isPromoted(): bool
{
    return $this->state === self::STATE_PROMOTED;
}
```

### 3. Add the widget to a Filament edit page

```php
// app/Filament/Resources/ServiceOrders/Pages/EditServiceOrder.php
use Joranski\FilamentComments\Comments\Widgets\CommentsWidget;
use Joranski\FilamentLiveChat\Chat\Widgets\LiveChatWidget;

protected function getFooterWidgets(): array
{
    return [
        LiveChatWidget::class,  // sort 4 — coordination first
        CommentsWidget::class,  // sort 5 — audit trail
    ];
}
```

That’s it. Open a saved record, click **Open live chat**, send messages, promote when needed.

---

## How it works

```
┌─────────────────────────────────────────────────────────────┐
│  Edit page footer                                           │
│  ┌──────────────────┐  ┌─────────────────────────────────┐  │
│  │ LiveChatWidget   │  │ CommentsWidget (filament-comments)│  │
│  │ • presence       │  │ • excludes chat group           │  │
│  │ • Flux modal     │  │ • pins, replies, audit trail    │  │
│  └────────┬─────────┘  └─────────────────────────────────┘  │
│           │ opens ChatPanel (polling every 4s)                │
└───────────┼─────────────────────────────────────────────────┘
            ▼
   comments table, group = 'chat'
            │
            │  "Save to comments" (PromoteChatMessage)
            ▼
   new comment (no group) + chat row state = 'promoted'
```

| Storage | `group` | `state` | Visible in |
|---------|---------|---------|------------|
| Live chat message | `chat` | null | Chat panel only |
| Promoted chat message | `chat` | `promoted` | Hidden from chat (archived) |
| Audit comment | null | null | Comments panel |

---

## Configuration reference

```php
// config/filament-live-chat.php
use Joranski\FilamentComments\Support\CommentGroups;

return [
    'group' => CommentGroups::CHAT,
    'poll_interval' => '4s',        // wire:poll on chat panel
    'presence_ttl_seconds' => 45,   // cache TTL for “viewing now”
    'message_limit' => 100,         // max messages loaded per record
];
```

---

## Integration patterns

### A. Footer widget (recommended)

`LiveChatWidget` shows:

- Viewer count + avatar stack (others viewing the same record)
- **Open live chat** button → Flux modal with `ChatPanel`

Requires a saved `$record` on an Filament `EditRecord` page.

### B. Standalone Livewire

Registered component: `filament-live-chat.chat-panel`

```blade
<livewire:filament-live-chat.chat-panel :record="$serviceOrder" />
```

The panel:

- Polls for new messages (`wire:poll.4s`)
- Records presence on mount and each poll
- Sends plain-text messages (mentions supported via `@Name`)
- Offers **Save to comments** per message

### C. Programmatic promote

```php
use Joranski\FilamentLiveChat\Chat\Support\PromoteChatMessage;

$auditComment = app(PromoteChatMessage::class)(
    chatMessage: $chatMessage,
    commentable: $order,
    promotedBy: auth()->user(),
);
```

Creates a normal comment (no `group`), marks the chat row `state = promoted`, and fires mention notifications if applicable.

---

## Presence

`CollaboratorPresence` stores active viewers in cache keyed by commentable type + id.

- TTL: `presence_ttl_seconds` (default 45s)
- Refreshed when the chat panel mounts / polls
- Excludes the current user from the “others viewing” count

Requires a working cache driver (Redis, database, etc.) in production.

---

## Authorization

Chat reuses **filament-comments** policy checks:

- **Send message:** `create` on comment model
- **Promote:** author or user with `update` on the chat message

Configure `CommentPolicy` in your host app the same way as the comments package.

---

## UX conventions

1. **Live chat** = quick coordination while editing a record.
2. **Comments panel** = audit-grade notes (pins, replies, search).
3. **Promote** = move an important chat line into the audit trail without copy-paste.

Keep `CommentGroups::CHAT` in `filament-comments.excluded_groups` so chat never duplicates in the audit panel.

---

## Customization

### Publish views

```bash
php artisan vendor:publish --tag=filament-live-chat-views
```

### Adjust polling

Change `poll_interval` in config (e.g. `'2s'`, `'10s'`). Balance freshness vs. server load.

### Subclass the widget

```php
class OrderLiveChatWidget extends LiveChatWidget
{
    protected static ?int $sort = 1;
}
```

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| Modal opens but no messages | Record must be saved; commentable needs `comments()` |
| `activeChat()` not found | Add scope to host Comment model (see above) |
| Presence always zero | Check cache driver; open same record in two browsers |
| Promote fails | Message must be active chat (`isChatMessage()` && not promoted) |
| Flux components missing | Install `livewire/flux-pro` and publish Flux assets |

---

## When *not* to use this package

- **Real-time WebSocket chat** — this package uses polling, not Echo/Pusher.
- **Customer-facing chat** — built for internal Filament staff coordination.
- **Separate chat database** — messages are comments with `group = chat`.

For those cases, integrate a dedicated chat product and skip this package.

---

## Related

- [`joranski/filament-comments`](../filament-comments/README.md) — audit comment panels
- Host delay/topic panels — keep domain-specific groups out of `excluded_groups` and render separate widgets

---

## License

MIT
