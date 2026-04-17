<?php

namespace Src\Modules\Accounts\Domain;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Src\Modules\Authentication\Domain\Authentication;
use Src\Modules\Securities\Domain\Security;

class Account extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'accounts';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'name',
        'broker',
        'platform',
        'account_mode',
        'account_type',
        'leverage',
        'starting_balance',
        'target_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'leverage' => 'float',
            'starting_balance' => 'float',
            'target_amount' => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(Authentication::class, 'user_id');
    }

    /**
     * Many-to-many: an account can trade multiple securities.
     *
     * Uses the account_securities pivot table with our custom AccountSecurity
     * pivot model so that pivot rows carry UUID primary keys and timestamps.
     */
    public function securities()
    {
        return $this->belongsToMany(
            Security::class,
            'account_securities',
            'account_id',
            'security_id'
        )
        ->using(AccountSecurity::class)
        ->withTimestamps();
    }
}