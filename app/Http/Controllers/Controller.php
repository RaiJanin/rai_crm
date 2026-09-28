<?php

namespace App\Http\Controllers;

use App\Enums\Page;
use App\Enums\ToastType;
use Inertia\Inertia;
use Inertia\Response;

abstract class Controller
{
    /**
     * Render one of the CRM's Inertia pages.
     *
     * @param  array<string, mixed>  $props
     */
    protected function inertia(Page $page, array $props = []): Response
    {
        return Inertia::render($page->value, $props);
    }

    /**
     * Flash a toast notification for the next Inertia response.
     */
    protected function toast(string $message, ToastType $type = ToastType::Success): void
    {
        Inertia::flash('toast', ['type' => $type->value, 'message' => $message]);
    }
}
