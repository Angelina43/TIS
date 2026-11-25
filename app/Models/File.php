<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = [
        'name',
        'url',
        'file_id',
        'author',
        'co_author',
    ];

    protected function casts(): array
    {
        return [
            'co_author' => 'array',
        ];
    }

}
