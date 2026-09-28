<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $task_id
 * @property int|null $deal_id
 * @property int|null $contact_id
 * @property int|null $ticket_id
 * @property string $description
 * @property int|null $responsible_person_id
 * @property TaskStatus $status
 * @property CarbonImmutable|null $due_date
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 *
 * @method static Builder<Task> pending()
 */
#[Table(key: 'task_id')]
#[Fillable(['deal_id', 'contact_id', 'ticket_id', 'description', 'responsible_person_id', 'status', 'due_date'])]
class Task extends CrmRecord
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    public function label(): string
    {
        return Str::limit($this->description, 60);
    }

    protected function cascadeRelations(): array
    {
        return ['attachments'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'due_date' => 'date:Y-m-d',
        ];
    }

    /**
     * @param  Builder<Task>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', '!=', TaskStatus::Done);
    }

    /**
     * @return BelongsTo<Deal, $this>
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }
}
