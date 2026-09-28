<?php

namespace App\Models;

use App\Enums\AccountKind;
use Carbon\CarbonImmutable;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $contact_id
 * @property int|null $company_id
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $title
 * @property string|null $source
 * @property int|null $created_by
 * @property-read string $full_name
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Table(key: 'contact_id')]
#[Fillable(['company_id', 'first_name', 'last_name', 'email', 'phone', 'title', 'source', 'created_by'])]
#[Appends(['full_name'])]
class Contact extends Account
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    protected function cascadeRelations(): array
    {
        return ['deals', 'tickets', 'tasks', 'attachments'];
    }

    public function label(): string
    {
        return $this->full_name;
    }

    public function reportKind(): AccountKind
    {
        return AccountKind::Contact;
    }

    public function reportSubtitle(): ?string
    {
        return collect([$this->title, $this->company?->name])->filter()->implode(' · ') ?: null;
    }

    /**
     * A customer report shows the person's own details instead of a contact list.
     */
    public function reportContacts(): Collection
    {
        return new Collection;
    }

    public function prepareForReport(): static
    {
        return $this->load('company:company_id,name');
    }

    public function followUpContactIds(): array
    {
        return [$this->contact_id];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->last_name));
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
        return $this->hasMany(Task::class, 'contact_id');
    }
}
