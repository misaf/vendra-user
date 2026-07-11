<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel();

arch('the user module derives tenancy from the support layer, never a concrete tenant provider')
    ->expect('Misaf\VendraUser')
    ->not->toUse('Misaf\VendraTenant');

arch('the user module never depends on the permission module that builds on it')
    ->expect('Misaf\VendraUser')
    ->not->toUse('Misaf\VendraPermission');
