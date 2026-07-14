<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Tests\Unit;

use Misaf\VendraSupport\Contracts\TagResolver;
use Misaf\VendraSupport\Support\EloquentTagResolver;
use Misaf\VendraSupport\Support\TagRelationship;
use Misaf\VendraUser\Models\User;

it('builds a user typed tag relation through the support contract', function (): void {
    app()->instance(TagResolver::class, new EloquentTagResolver(new TagRelationship(UserTestTag::class)));

    $relation = (new User())->tags();

    expect($relation->getRelated())->toBeInstanceOf(UserTestTag::class)
        ->and($relation->getTable())->toBe('taggables')
        ->and($relation->toBase()->wheres)->toContainEqual([
            'type'     => 'Basic',
            'column'   => 'tags.type',
            'operator' => '=',
            'value'    => User::TAG_TYPE,
            'boolean'  => 'and',
        ]);
});
