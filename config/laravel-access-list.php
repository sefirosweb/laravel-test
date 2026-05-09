<?php

declare(strict_types=1);

return [
    'prefix' => 'acl',
    'middleware' => ['web'],
    'AccessList' => Sefirosweb\LaravelAccessList\Http\Models\AccessList::class,
    'Role' => Sefirosweb\LaravelAccessList\Http\Models\Role::class,
    'User' => Sefirosweb\LaravelAccessList\Http\Models\User::class,
];
