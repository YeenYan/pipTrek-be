<?php

use App\Providers\AppServiceProvider;
use Src\Modules\Authentication\AuthenticationServiceProvider;
use Src\Modules\Accounts\AccountsServiceProvider;

return [
    AppServiceProvider::class,
    AuthenticationServiceProvider::class,
    AccountsServiceProvider::class,
];
