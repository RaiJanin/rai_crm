<?php

namespace App\Enums;

/**
 * Whether a task or ticket list shows everyone's records or only the viewer's.
 */
enum ListScope: string
{
    case All = 'all';
    case Mine = 'mine';
}
