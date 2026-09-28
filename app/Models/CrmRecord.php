<?php

namespace App\Models;

use App\Contracts\Attachable;
use App\Models\Concerns\CascadesSoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A first-class CRM record: soft-deletable (trash & restore with cascading
 * children) and able to hold file attachments.
 */
abstract class CrmRecord extends CrmModel implements Attachable
{
    use CascadesSoftDeletes, SoftDeletes;

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }
}
