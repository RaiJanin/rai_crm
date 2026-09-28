<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hashids Salt
    |--------------------------------------------------------------------------
    |
    | Secret used to encode model ids in URLs. Each model mixes in its table
    | name, so the same id produces a different hash per record type.
    | Changing this invalidates every previously shared link.
    |
    */

    'salt' => env('HASHIDS_SALT', env('APP_KEY', '')),

    /*
    |--------------------------------------------------------------------------
    | Minimum Hash Length
    |--------------------------------------------------------------------------
    */

    'min_length' => (int) env('HASHIDS_MIN_LENGTH', 10),

];
