<?php

namespace App\Policies;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends OwnedRecordPolicy<Attachment>
 */
class AttachmentPolicy extends OwnedRecordPolicy
{
    /**
     * @param  Attachment  $record
     */
    protected function ownerId(Model $record): ?int
    {
        return $record->uploaded_by;
    }
}
