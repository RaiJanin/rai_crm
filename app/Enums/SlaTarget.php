<?php

namespace App\Enums;

/**
 * The service-level target a ticket is currently measured against.
 */
enum SlaTarget: string
{
    case FirstResponse = 'first_response';
    case Resolution = 'resolution';
}
