<?php

namespace App\Services\Reports;

use App\Enums\Page;
use App\Enums\PrintView;
use Carbon\CarbonImmutable;

/**
 * A report that can be shown as an app page and rendered as a print template.
 */
abstract class Report
{
    protected readonly CarbonImmutable $generatedAt;

    public function __construct()
    {
        $this->generatedAt = now();
    }

    /**
     * Inertia page component for the on-screen report.
     */
    abstract public function page(): Page;

    /**
     * Blade view for the print template.
     */
    abstract public function printView(): PrintView;

    /**
     * Report data shared by the page and the print template.
     *
     * @return array<string, mixed>
     */
    abstract public function data(): array;

    /**
     * Props for the Inertia page; override to reshape data for the browser.
     *
     * @return array<string, mixed>
     */
    public function pageProps(): array
    {
        return $this->data();
    }
}
