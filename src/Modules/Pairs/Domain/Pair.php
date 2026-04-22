<?php

namespace Src\Modules\Pairs\Domain;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Pair extends Model
{
    use HasUuids;

    protected $table = 'pairs';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'symbol',
        'base_currency',
        'quote_currency'
    ];

    protected function casts(): array
    {
        return [
            //
        ];
    }

    public function trades()
    {

    }
}
