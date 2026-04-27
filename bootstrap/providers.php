<?php

use App\Providers\AppServiceProvider;
use Src\Modules\Authentication\AuthenticationServiceProvider;
use Src\Modules\Accounts\AccountsServiceProvider;
use Src\Modules\Securities\SecuritiesServiceProvider;
use Src\Modules\Pairs\PairsServiceProvider;
use Src\Modules\Tags\TagsServiceProvider;
use Src\Modules\Trades\TradesServiceProvider;

return [
    AppServiceProvider::class,
    AuthenticationServiceProvider::class,
    AccountsServiceProvider::class,
    SecuritiesServiceProvider::class,
    PairsServiceProvider::class,
    TagsServiceProvider::class,
    TradesServiceProvider::class,
];
