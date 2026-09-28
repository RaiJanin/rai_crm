<?php

namespace App\Models\Concerns;

use App\Models\Attachment;
use App\Models\CrmRecord;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Cascades soft deletes, restores and force deletes to child relations.
 *
 * Used by {@see CrmRecord}; subclasses list their soft-deletable child
 * relations in {@see cascadeRelations()}.
 *
 * @mixin CrmRecord
 */
trait CascadesSoftDeletes
{
    /**
     * Relation names whose records follow this model's delete/restore lifecycle.
     *
     * @return list<string>
     */
    abstract protected function cascadeRelations(): array;

    public static function bootCascadesSoftDeletes(): void
    {
        /** @var class-string<Model&SoftDeletes> $model */
        $model = static::class;

        $model::deleted(function (CrmRecord $model) {
            if ($model->isForceDeleting()) {
                return;
            }

            foreach ($model->cascadeRelations() as $relation) {
                $model->cascadeRelation($relation)->get()->each->delete();
            }
        });

        $model::restoring(function (CrmRecord $model) {
            $deletedAt = $model->{$model->getDeletedAtColumn()};

            foreach ($model->cascadeRelations() as $relation) {
                $query = $model->cascadeRelation($relation);
                $column = $query->getRelated()->getQualifiedDeletedAtColumn();

                $query->onlyTrashed()->where($column, '>=', $deletedAt)->get()->each->restore();
            }
        });

        $model::forceDeleting(function (CrmRecord $model) {
            foreach ($model->cascadeRelations() as $relation) {
                $model->cascadeRelation($relation)->withTrashed()->get()->each->forceDelete();
            }

            if (method_exists($model, 'comments')) {
                $model->comments()->delete();
            }
        });
    }

    /**
     * @return HasMany<Deal|Task|Ticket, $this>|MorphMany<Attachment, $this>
     */
    protected function cascadeRelation(string $relation): HasMany|MorphMany
    {
        return $this->{$relation}();
    }
}
