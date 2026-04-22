<?php

namespace Src\Modules\Tags\Domain;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasUuids;

    protected $table = 'tags';
    public $incrementing = false;

    public $timestamps = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tag_name'
    ];

    protected function casts(): array
    {
        return [
            //
        ];
    }

}
