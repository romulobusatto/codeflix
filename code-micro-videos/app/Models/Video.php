<?php

namespace App\Models;

use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use SoftDeletes, Uuid;

    const RATING_LIST = ['L', '10', '12', '14', '16', '18'];

    protected $fillable = [
        'title',
        'description',
        'rating',
        'year_launched',
        'duration',
        'opened'
    ];
    protected $dates = ['deleted_at'];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $casts = [
        'id' => 'string',
        'title' => 'string',
        'description' => 'string',
        'rating' => 'string',
        'year_launched' => 'integer',
        'duration' => 'integer',
        'opened' => 'boolean',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }
}
