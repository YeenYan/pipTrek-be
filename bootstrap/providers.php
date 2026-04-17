<?php

use App\Providers\AppServiceProvider;
use Src\Modules\Authentication\AuthenticationServiceProvider;
use Src\Modules\Accounts\AccountsServiceProvider;
use Src\Modules\Securities\SecuritiesServiceProvider;

return [
    AppServiceProvider::class,
    AuthenticationServiceProvider::class,
    AccountsServiceProvider::class,
    SecuritiesServiceProvider::class
];
