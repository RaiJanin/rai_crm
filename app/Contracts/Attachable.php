<?php

namespace App\Contracts;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A record that files can be attached to.
 */
interface Attachable
{
    /**
     * @return MorphMany<Attachment, *>
     */
    public function attachments(): MorphMany;

    /**
     * Morph alias of the record, e.g. "deal" (provided by Eloquent models).
     *
     * @return string
     */
    public function getMorphClass();
}
