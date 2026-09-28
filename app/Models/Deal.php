<?php

namespace App\Models;

use App\Contracts\Commentable;
use App\Enums\DealStage;
use App\Models\Concerns\HasComments;
use Carbon\CarbonImmutable;
use Database\Factories\DealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $deal_id
 * @property string $title
 * @property int $contact_id
 * @property int|null $company_id
 * @property string $value
 * @property DealStage $stage
 * @property CarbonImmutable|null $expected_close_date
 * @property CarbonImmutable|null $closed_at
 * @property string|null $notes
 * @property int|null $created_by
 * @property-read User|null $creator
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 *
 * @method static Builder<Deal> open()
 */
#[Table(key: 'deal_id')]
#[Fillable(['title', 'contact_id', 'company_id', 'value', 'stage', 'expected_close_date', 'notes', 'created_by'])]
class Deal extends CrmRecord implements Commentable
{
    /** @use HasFactory<DealFactory> */
    use HasComments, HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Deal $deal) {
            if ($deal->isDirty('stage')) {
                $deal->closed_at = $deal->stage->isClosed() ? now() : null;
            }
        });
    }

    public function label(): string
    {
        return $this->title;
    }

    protected function cascadeRelations(): array
    {
        return ['tasks', 'attachments'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'stage' => DealStage::class,
            'expected_close_date' => 'date:Y-m-d',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Deal>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('stage', DealStage::open());
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'deal_id');
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'deal_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
