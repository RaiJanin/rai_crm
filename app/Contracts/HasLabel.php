<?php

namespace App\Contracts;

use BackedEnum;

interface HasLabel extends BackedEnum
{
    public function label(): string;
}