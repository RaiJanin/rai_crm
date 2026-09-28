<?php

namespace App\Models;

use App\Contracts\Commentable;
use App\Enums\AccountKind;
use App\Enums\DealStage;
use App\Models\Concerns\HasComments;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer account — a company or an individual contact — that owns deals
 * and support tickets and can be reported on.
 *
 * Deals and tickets reference the account by its own key name
 * (company_id / contact_id), so the relations are defined once here.
 */
abstract class Account extends CrmRecord implements Commentable
{
    use HasComments;

    /**
     * Which kind of account report this account gets.
     */
    abstract public function reportKind(): AccountKind;

    /**
     * Heading shown under the account name on reports.
     */
    abstract public function reportSubtitle(): ?string;

    /**
     * People listed in the account report.
     *
     * @return Collection<int, Contact>
     */
    abstract public function reportContacts(): Collection;

    /**
     * Contacts whose own tasks count as follow-ups for this account.
     *
     * @return Builder<Contact>|list<int>
     */
    abstract public function followUpContactIds(): Builder|array;

    /**
     * Load what the account header on a report needs.
     */
    public function prepareForReport(): static
    {
        return $this;
    }

    /**
     * @return HasMany<Deal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, $this->getKeyName());
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, $this->getKeyName());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Pipeline, won revenue (12 months), open tickets and CSAT for account lists.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function withReportTotals(Builder $query): void
    {
        $query
            ->withCount([
                'deals as open_deals_count' => fn ($query) => $query->open(),
                'tickets as open_tickets_count' => fn ($query) => $query->active(),
            ])
            ->withSum(['deals as pipeline_value' => fn ($query) => $query->open()], 'value')
            ->withSum(['deals as won_value' => fn ($query) => $query
                ->where('stage', DealStage::Won)
                ->where('closed_at', '>=', now()->subYear())], 'value')
            ->withAvg(['tickets as csat_average' => fn ($query) => $query->whereNotNull('satisfaction')], 'satisfaction');
    }
}
