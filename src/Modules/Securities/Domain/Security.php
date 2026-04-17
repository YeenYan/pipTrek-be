<?php

    namespace Src\Modules\Securities\Domain;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Src\Modules\Accounts\Domain\Account;

class Security extends Model
{
    use HasUuids;

    protected $table = 'securities';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name'
    ];

    protected function casts(): array
    {
        return [
            //
        ];
    }

    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_securities', 'security_id', 'account_id');
    }

}