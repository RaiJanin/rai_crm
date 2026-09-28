<?php

namespace App\Models\Concerns;

use Hashids\Hashids;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Exposes the model in URLs as a Hashids string instead of its numeric key.
 *
 * The hash is appended to serialized output as `hash_id` and used for route
 * model binding, so `route('deals.show', $deal)` yields `/deals/Xk2vQ9pL0a`.
 *
 * @property-read string|null $hash_id
 *
 * @mixin Model
 */
trait HasHashId
{
    /** @var array<class-string, Hashids> */
    private static array $hashids = [];

    public function initializeHasHashId(): void
    {
        $this->append('hash_id');
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function hashId(): Attribute
    {
        return Attribute::get(fn () => $this->getKey() === null ? null : static::hashids()->encode($this->getKey()));
    }

    public function getRouteKey(): mixed
    {
        return $this->hash_id;
    }

    public function getRouteKeyName(): string
    {
        return 'hash_id';
    }

    /**
     * @param  Model|Builder|Relation<*, *, *>  $query
     * @param  mixed  $value
     * @param  string|null  $field
     * @return Builder
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($field !== null && $field !== 'hash_id') {
            return parent::resolveRouteBindingQuery($query, $value, $field);
        }

        $id = static::decodeHashId((string) $value);

        return $id === null ? $query->whereRaw('1 = 0') : $query->whereKey($id);
    }

    /**
     * Decode a hash to its numeric key, or null when it isn't a valid hash.
     */
    public static function decodeHashId(?string $hash): ?int
    {
        if ($hash === null || $hash === '') {
            return null;
        }

        $decoded = static::hashids()->decode($hash);

        if (count($decoded) !== 1 || static::hashids()->encode($decoded[0]) !== $hash) {
            return null;
        }

        return (int) $decoded[0];
    }

    protected static function hashids(): Hashids
    {
        // One encoder per model class: the table name salts each model's hashes.
        return self::$hashids[static::class] ??= new Hashids(
            app(static::class)->getTable().'|'.config('hashids.salt'),
            config('hashids.min_length'),
        );
    }
}
