<?php

namespace App\Enums;

/**
 * Flash toast variants understood by the frontend (resources/js/lib/flashToast.ts).
 */
enum ToastType: string
{
    case Success = 'success';
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
}
