<?php

namespace Src\Modules\Accounts\Domain;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Src\Modules\Accounts\Domain\Account;

class AccountGoal extends Model
{
    use HasUuids;

    protected $table = 'account_goals';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'account_id',
        'starting_balance',
        'current_balance',
        'target_amount',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'starting_balance' => 'float',
            'current_balance' => 'float',
            'target_amount' => 'float',
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

}
