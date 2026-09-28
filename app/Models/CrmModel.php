<?php

namespace App\Models;

use App\Models\Concerns\HasHashId;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for every CRM model.
 *
 * Gives each model a hashed route key and a human-readable label, so shared
 * code (activity feeds, trash, reports) can treat records uniformly.
 */
abstract class CrmModel extends Model
{
    use HasHashId;

    /**
     * Short human-readable name of the record, e.g. a deal title.
     */
    abstract public function label(): string;

    /**
     * Reference to this record for the UI: morph type, hashed id and label.
     *
     * @return array{type: string, id: string|null, label: string}
     */
    public function toSubject(): array
    {
        return ['type' => $this->getMorphClass(), 'id' => $this->hash_id, 'label' => $this->label()];
    }
}
