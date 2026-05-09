<?php

declare(strict_types=1);

return [
    'prefix' => 'acl',
    'middleware' => ['web'],
    'AccessList' => Sefirosweb\LaravelAccessList\Http\Models\AccessList::class,
    'Role' => Sefirosweb\LaravelAccessList\Http\Models\Role::class,
    'User' => App\Models\User::class,
];
