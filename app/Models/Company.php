<?php

namespace App\Models;

use App\Enums\AccountKind;
use Carbon\CarbonImmutable;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $company_id
 * @property string $name
 * @property string|null $industry
 * @property string|null $website
 * @property string|null $phone
 * @property string|null $address
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Table(key: 'company_id')]
#[Fillable(['name', 'industry', 'website', 'phone', 'address', 'created_by'])]
class Company extends Account
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    /**
     * Contacts, deals and tickets outlive their company (company is optional),
     * so only attachments follow the company's lifecycle.
     */
    protected function cascadeRelations(): array
    {
        return ['attachments'];
    }

    public function label(): string
    {
        return $this->name;
    }

    public function reportKind(): AccountKind
    {
        return AccountKind::Company;
    }

    public function reportSubtitle(): ?string
    {
        return $this->industry;
    }

    public function reportContacts(): Collection
    {
        return $this->contacts()->orderBy('first_name')->get();
    }

    public function followUpContactIds(): Builder
    {
        return Contact::where('company_id', $this->company_id)->select('contact_id');
    }

    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'company_id');
    }
}
