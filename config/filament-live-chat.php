<?php

declare(strict_types=1);

use Joranski\FilamentComments\Support\CommentGroups;

return [
    'group' => CommentGroups::CHAT,
    'poll_interval' => '4s',
    'presence_ttl_seconds' => 45,
    'message_limit' => 100,
];
