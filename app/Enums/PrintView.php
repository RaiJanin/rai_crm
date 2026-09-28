<?php

namespace App\Enums;

/**
 * Blade print templates (resources/views/print/*.blade.php).
 */
enum PrintView: string
{
    case Account = 'print.account';
    case Overview = 'print.overview';

    public function view(): string
    {
        return $this->value;
    }
}
