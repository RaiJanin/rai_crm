<?php

namespace App\Enums;

use App\Contracts\HasLabel;

enum TicketChannel: string implements HasLabel
{
    case Email = 'email';
    case Phone = 'phone';
    case Chat = 'chat';
    case Web = 'web';
    case WalkIn = 'walk_in';
    case Social = 'social';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Chat => 'Chat',
            self::Web => 'Web form',
            self::WalkIn => 'Walk-in',
            self::Social => 'Social media',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $channel) => ['value' => $channel->value, 'label' => $channel->label()], self::cases());
    }
}
