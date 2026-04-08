<?php

namespace App\Models;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


class Project extends Model
{
    protected $fillable = [
        'title',
        'place',
        'date',
        'slug',
        'cover_image',
        'sort_order',
        'meta',
        'published_at',
    ];
    
    protected $casts = [
        'meta' => 'array',
        'published_at' => 'datetime',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
    
    public function photos()
    {
        return $this->hasMany(Photo::class)->orderBy('sort_order');
    }
}
