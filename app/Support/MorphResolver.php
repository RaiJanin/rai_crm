<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Resolves "type + id" pairs from forms (e.g. commentable_type=deal) to a
 * model through the morph map, requiring it to implement a given contract.
 */
class MorphResolver
{
    /**
     * @template TContract of object
     *
     * @param  class-string<TContract>  $contract
     * @return TContract
     */
    public function resolve(string $alias, int $id, string $contract): object
    {
        $class = Relation::getMorphedModel($alias);

        abort_unless($class !== null && is_a($class, $contract, true), 404);

        $record = $class::query()->findOrFail($id);

        abort_unless($record instanceof $contract, 404);

        return $record;
    }

    /**
     * Morph aliases whose models implement the contract.
     *
     * @param  class-string  $contract
     * @return list<string>
     */
    public function aliasesFor(string $contract): array
    {
        return array_keys(array_filter(
            Relation::morphMap(),
            fn (string $class) => is_a($class, $contract, true),
        ));
    }
}
