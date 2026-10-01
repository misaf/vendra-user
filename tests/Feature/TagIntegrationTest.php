<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Tests\Feature;

use LogicException;
use Misaf\VendraSupport\Capabilities\TagIntegration;
use Misaf\VendraSupport\Contracts\TagResolver;
use Misaf\VendraSupport\Support\TagRelationship;
use Misaf\VendraUser\Models\User;

it('builds a user typed tag relation through the support contract', function (): void {
    $resolver = $this->mock(TagResolver::class);
    $resolver->shouldReceive('available')->andReturnTrue();
    $resolver->shouldReceive('relationship')->andReturn(new TagRelationship(UserTestTag::class));

    $relation = (new User)->tags();

    expect($relation->getRelated())->toBeInstanceOf(UserTestTag::class)
        ->and($relation->getTable())->toBe('taggables')
        ->and($relation->toBase()->wheres)->toContainEqual([
            'type' => 'Basic',
            'column' => 'tags.type',
            'operator' => '=',
            'value' => User::TAG_TYPE,
            'boolean' => 'and',
        ]);
});

it('keeps user tags unavailable when no tag resolver is registered', function (): void {
    app()->offsetUnset(TagResolver::class);

    expect(TagIntegration::isAvailable())->toBeFalse()
        ->and(fn () => (new User)->tags())
        ->toThrow(LogicException::class, 'Install a tag provider to use tags.');
});
