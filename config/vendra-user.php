<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Filament Panels
    |--------------------------------------------------------------------------
    |
    | Here you may specify the Filament panels that the user administration UI
    | is registered on. The user navigation only appears within the panels
    | listed here. You may provide a single panel ID or an array of IDs to
    | mount the module across multiple panels.
    |
    | Supported: "admin" (string), ["admin", "vendor"] (array)
    |
    */

    'panels' => ['admin'],

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | This value determines the sidebar navigation group that the Users cluster
    | is nested under. You may provide a translation key or a literal label,
    | allowing you to file the user UI alongside a host application's own
    | groups. When left empty, the module's default group is used.
    |
    */

    'navigation_group' => 'vendra-support::navigation.groups.Customers',

];
