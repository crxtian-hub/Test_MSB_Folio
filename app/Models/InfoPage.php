<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfoPage extends Model
{
    protected $fillable = [
        'subtitle',
        'photo_path',
        'email',
        'instagram_url',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
