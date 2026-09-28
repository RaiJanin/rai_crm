<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A trashed record cannot be restored yet (e.g. its parent is still trashed).
 */
class RestoreBlockedException extends RuntimeException
{
    //
}
