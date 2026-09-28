<?php

namespace App\Models;

use App\Contracts\Commentable;
use App\Enums\SlaState;
use App\Enums\SlaTarget;
use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Concerns\HasComments;
use Carbon\CarbonImmutable;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer concern: question, incident, complaint, request, etc.
 *
 * @property int $ticket_id
 * @property string $subject
 * @property string $description
 * @property int $contact_id
 * @property int|null $company_id
 * @property int|null $deal_id
 * @property TicketType $type
 * @property TicketPriority $priority
 * @property TicketStatus $status
 * @property TicketChannel $channel
 * @property int|null $assignee_id
 * @property int|null $created_by
 * @property CarbonImmutable|null $first_response_due_at
 * @property CarbonImmutable|null $resolution_due_at
 * @property CarbonImmutable|null $first_responded_at
 * @property CarbonImmutable|null $resolved_at
 * @property CarbonImmutable|null $closed_at
 * @property string|null $resolution
 * @property int|null $satisfaction
 * @property-read string $reference
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 *
 * @method static Builder<Ticket> active()
 * @method static Builder<Ticket> breached()
 */
#[Table(key: 'ticket_id')]
#[Fillable([
    'subject', 'description', 'contact_id', 'company_id', 'deal_id', 'type', 'priority',
    'status', 'channel', 'assignee_id', 'created_by', 'resolution', 'satisfaction',
])]
#[Appends(['reference'])]
class Ticket extends CrmRecord implements Commentable
{
    /** @use HasFactory<TicketFactory> */
    use HasComments, HasFactory;

    /**
     * How close to a deadline a ticket counts as "due soon".
     */
    public const DUE_SOON_HOURS = 2;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'question',
        'priority' => 'medium',
        'status' => 'new',
        'channel' => 'email',
    ];

    protected static function booted(): void
    {
        static::saving(fn (Ticket $ticket) => $ticket->syncLifecycle());
    }

    public function label(): string
    {
        return "{$this->reference} · {$this->subject}";
    }

    /**
     * Tickets keep a conversation with the customer, not just internal notes.
     */
    public function acceptsCustomerReplies(): bool
    {
        return true;
    }

    /**
     * The first reply logged to the customer meets the first-response SLA.
     */
    public function commentAdded(Comment $comment): void
    {
        if ($comment->is_public) {
            $this->markResponded();
        }
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
            'type' => TicketType::class,
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'channel' => TicketChannel::class,
            'first_response_due_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'first_responded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'satisfaction' => 'integer',
        ];
    }

    /**
     * Keep SLA targets and lifecycle timestamps consistent with priority/status.
     */
    public function syncLifecycle(): void
    {
        $openedAt = $this->created_at ?? now();

        if ($this->first_response_due_at === null || $this->isDirty('priority')) {
            $this->first_response_due_at = $openedAt->addHours($this->priority->firstResponseHours());
            $this->resolution_due_at = $openedAt->addHours($this->priority->resolutionHours());
        }

        if (! $this->isDirty('status')) {
            return;
        }

        if ($this->status->isActive()) {
            // Reopened: clear any previous outcome.
            $this->resolved_at = null;
            $this->closed_at = null;

            return;
        }

        $this->resolved_at ??= now();

        if ($this->status === TicketStatus::Closed) {
            $this->closed_at ??= now();
        }
    }

    /**
     * Record the first reply to the customer (stops the first-response SLA clock).
     */
    public function markResponded(): void
    {
        if ($this->first_responded_at !== null) {
            return;
        }

        $this->first_responded_at = now();

        if ($this->status === TicketStatus::New) {
            $this->status = TicketStatus::Open;
        }

        $this->save();
    }

    /**
     * SLA state for display:
     *  - active tickets: breached | due_soon | on_track
     *  - finished tickets: met | missed (resolution target).
     *
     * @return array{state: SlaState, due_at: CarbonImmutable|null, target: SlaTarget|null}
     */
    public function sla(): array
    {
        if (! $this->status->isActive()) {
            $met = $this->resolved_at !== null && $this->resolution_due_at !== null
                && $this->resolved_at->lessThanOrEqualTo($this->resolution_due_at);

            return ['state' => $met ? SlaState::Met : SlaState::Missed, 'due_at' => null, 'target' => null];
        }

        [$target, $dueAt] = $this->first_responded_at === null
            ? [SlaTarget::FirstResponse, $this->first_response_due_at]
            : [SlaTarget::Resolution, $this->resolution_due_at];

        // A resolution target can be breached even before the first reply.
        if ($this->resolution_due_at?->isPast()) {
            [$target, $dueAt] = [SlaTarget::Resolution, $this->resolution_due_at];
        }

        $state = match (true) {
            $dueAt === null => SlaState::OnTrack,
            $dueAt->isPast() => SlaState::Breached,
            $dueAt->lessThanOrEqualTo(now()->addHours(self::DUE_SOON_HOURS)) => SlaState::DueSoon,
            default => SlaState::OnTrack,
        };

        return ['state' => $state, 'due_at' => $dueAt, 'target' => $target];
    }

    /**
     * Human-friendly reference quoted to customers, e.g. TCK-00042.
     *
     * @return Attribute<string, never>
     */
    protected function reference(): Attribute
    {
        return Attribute::get($this->formatReference(...));
    }

    private function formatReference(): string
    {
        return 'TCK-'.str_pad((string) $this->ticket_id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::active());
    }

    /**
     * Active tickets past their first-response or resolution target.
     *
     * @param  Builder<Ticket>  $query
     */
    #[Scope]
    protected function breached(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::active())
            ->where(fn ($query) => $query
                ->where('resolution_due_at', '<', now())
                ->orWhere(fn ($query) => $query
                    ->whereNull('first_responded_at')
                    ->where('first_response_due_at', '<', now())));
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
     * @return BelongsTo<Deal, $this>
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'ticket_id');
    }
}
