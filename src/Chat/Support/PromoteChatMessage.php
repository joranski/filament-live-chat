<?php

declare(strict_types=1);

namespace Joranski\FilamentLiveChat\Chat\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Joranski\FilamentComments\Support\CommentGroups;
use Joranski\FilamentComments\Support\CommentMentionNotifier;
use Joranski\FilamentComments\Support\CommentMentionParser;

final class PromoteChatMessage
{
    public function __invoke(Model $chatMessage, Model $commentable, Authenticatable $promotedBy): Model
    {
        if (! method_exists($chatMessage, 'isChatMessage') || ! $chatMessage->isChatMessage()
            || (method_exists($chatMessage, 'isPromoted') && $chatMessage->isPromoted())) {
            throw new InvalidArgumentException('Only active chat messages can be promoted to comments.');
        }

        $mentionedUserIds = app(CommentMentionParser::class)->parseUserIds((string) $chatMessage->comment);

        $promotedComment = $commentable->comments()->create([
            'user_id' => $chatMessage->user_id,
            'comment' => $chatMessage->comment,
            'active' => true,
            'mentioned_user_ids' => $mentionedUserIds !== [] ? $mentionedUserIds : null,
        ]);

        $chatMessage->update([
            'state' => CommentGroups::STATE_PROMOTED,
        ]);

        if ($mentionedUserIds !== []) {
            app(CommentMentionNotifier::class)->notify(
                comment: $promotedComment,
                commentable: $commentable,
                author: $promotedBy,
            );
        }

        return $promotedComment;
    }
}
