<?php

namespace App\Enums;

use App\Contracts\HasLabel;

enum DealStage: string implements HasLabel
{
    case Lead = 'lead';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Won, self::Lost], true);
    }

    /**
     * Stages that represent a deal still in progress.
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return array_values(array_filter(self::cases(), fn (self $stage) => ! $stage->isClosed()));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $stage) => ['value' => $stage->value, 'label' => $stage->label()], self::cases());
    }
}
