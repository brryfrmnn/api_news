<?php

namespace App\Modules\Category\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    
    protected $table = 'categories';
    use SoftDeletes;
    use HasSlug;
    protected $hidden = ['admin_id','deleted_at','parent_id','slug'];
    // protected $appends = ['full_name'];
    
    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function admin()
    {
        return $this->belongsTo('App\Modules\User\Model\User','admin_id');
    }

    public function post()
    {
        return $this->hasMany('App\Modules\Post\Model\Post','category_id');
    }

    public function child()
    {
        return $this->belongsTo(Self::class, 'parent_id');
    }

    public function parent()
    {
        return $this->hasMany(Self::class, 'parent_id');
    }
}
