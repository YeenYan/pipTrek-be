<?php

namespace Src\Modules\Accounts\Domain;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot model for the account_securities table.
 *
 * Represents a many-to-many link between an Account and a Security.
 * Using a dedicated Pivot model gives us UUID primary keys and timestamps
 * on the pivot rows without any extra setup.
 */
class AccountSecurity extends Pivot
{
    use HasUuids;

    protected $table = 'account_securities';

    public $incrementing = false;
    protected $keyType = 'string';

    /** Track when a security was added/removed from an account */
    public $timestamps = true;
}