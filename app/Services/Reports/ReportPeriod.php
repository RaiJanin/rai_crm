<?php

namespace App\Services\Reports;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * A reporting window such as "last 90 days" or "all time".
 */
final class ReportPeriod
{
    public const DEFAULT = '12m';

    /**
     * @var array<string, array{label: string, days: int|null}>
     */
    private const OPTIONS = [
        '30d' => ['label' => 'Last 30 days', 'days' => 30],
        '90d' => ['label' => 'Last 90 days', 'days' => 90],
        '12m' => ['label' => 'Last 12 months', 'days' => 365],
        'all' => ['label' => 'All time', 'days' => null],
    ];

    private function __construct(
        public readonly string $value,
        public readonly string $label,
        public readonly ?CarbonImmutable $since,
    ) {}

    public static function from(?string $value): self
    {
        $value ??= self::DEFAULT;

        if (! isset(self::OPTIONS[$value])) {
            throw new InvalidArgumentException("Unknown report period [{$value}].");
        }

        ['label' => $label, 'days' => $days] = self::OPTIONS[$value];

        return new self($value, $label, $days === null ? null : now()->subDays($days)->startOfDay());
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::OPTIONS);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (string $value) => ['value' => $value, 'label' => self::OPTIONS[$value]['label']],
            self::values(),
        );
    }

    public function includes(?CarbonImmutable $moment): bool
    {
        return $this->since === null || ($moment !== null && $moment >= $this->since);
    }
}
