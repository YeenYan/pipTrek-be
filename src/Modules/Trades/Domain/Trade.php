<?php

namespace Src\Modules\Trades\Domain;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Src\Modules\Accounts\Domain\Account;
use Src\Modules\Pairs\Domain\Pair;

class Trade extends Model
{
    use HasUuids;

    protected $table = 'trades';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'account_id',
        'pair_id',
        'trade_type',
        'lot_size',
        'entry_price',
        'exit_price',
        'stop_loss',
        'take_profit',
        'profit_loss_amount',
        'balance_before',
        'balance_after',
        'outcome',
        'trade_datetime',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'trade_type' => 'string',
            'lot_size' => 'float',
            'entry_price' => 'float',
            'exit_price' => 'float',
            'stop_loss' => 'float',
            'take_profit' => 'float',
            'profit_loss_amount' => 'float',
            'balance_before' => 'float',
            'balance_after' => 'float',
            'outcome' => 'string',
            'trade_datetime' => 'datetime',
            'notes' => 'string',
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function pair()
    {
        return $this->belongsTo(Pair::class, 'pair_id');
    }
}