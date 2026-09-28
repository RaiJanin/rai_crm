<?php

namespace App\Support;

use App\Enums\DealStage;
use App\Enums\SlaState;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Number;

/**
 * Server-side formatting for print templates, mirroring the app's
 * useFormatters() composable (currency and locale from config/crm.php).
 */
class ReportFormat
{
    public static function money(float|int|string|null $value): string
    {
        return (string) Number::currency(
            (float) ($value ?? 0),
            in: config('crm.currency'),
            locale: self::locale(),
            precision: 0,
        );
    }

    public static function date(?CarbonInterface $value): string
    {
        return $value?->timezone(config('crm.timezone'))->locale(self::locale())->isoFormat('MMM D, YYYY') ?? '—';
    }

    /**
     * Calendar date without timezone conversion (e.g. expected close date).
     */
    public static function day(?CarbonInterface $value): string
    {
        return $value?->locale(self::locale())->isoFormat('MMM D, YYYY') ?? '—';
    }

    public static function dateTime(?CarbonInterface $value): string
    {
        return $value?->timezone(config('crm.timezone'))->locale(self::locale())->isoFormat('MMM D, YYYY h:mm A') ?? '—';
    }

    /**
     * Human-friendly length of time, e.g. "45 min", "6.5 h", "3.2 days".
     */
    public static function duration(?float $hours): string
    {
        return match (true) {
            $hours === null => '—',
            $hours < 1 => round($hours * 60).' min',
            $hours < 48 => $hours.' h',
            default => round($hours / 24, 1).' days',
        };
    }

    public static function percent(?float $value): string
    {
        return $value === null ? '—' : $value.'%';
    }

    /**
     * Badge tone for a deal stage: open stages are amber, outcomes use status colours.
     */
    public static function stageTone(DealStage $stage): string
    {
        return match ($stage) {
            DealStage::Won => 'green',
            DealStage::Lost => 'red',
            default => 'amber',
        };
    }

    public static function ticketStatusTone(TicketStatus $status): string
    {
        return match ($status) {
            TicketStatus::New => 'blue',
            TicketStatus::Open => 'amber',
            TicketStatus::Resolved => 'green',
            default => 'gray',
        };
    }

    public static function priorityTone(TicketPriority $priority): string
    {
        return match ($priority) {
            TicketPriority::Low => 'gray',
            TicketPriority::Medium => 'blue',
            TicketPriority::High => 'amber',
            TicketPriority::Urgent => 'red',
        };
    }

    /**
     * @return array{0: string, 1: string} [tone, label]
     */
    public static function sla(SlaState $state): array
    {
        return [$state->tone(), $state->label()];
    }

    private static function locale(): string
    {
        return str_replace('-', '_', (string) config('crm.locale'));
    }
}
