<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $fillable = ['project_id','path','alt','sort_order'];
    
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}