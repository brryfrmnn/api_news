<?php

namespace App\Modules\Post\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use DB;
use Laravel\Scout\Searchable;

class Post extends Model
{
    
    protected $table = 'posts';
    use SoftDeletes;
    use HasSlug;
    protected $hidden = ['category_id','admin_id','editor_id','writer_id'];
    protected $appends = ['content'];
    
    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function admin()
    {
        return $this->belongsTo('App\Modules\User\Model\User', 'admin_id');
    }

    public function writer()
    {
        return $this->belongsTo('App\Modules\User\Model\User', 'writer_id');
    }

    public function editor()
    {
        return $this->belongsTo('App\Modules\User\Model\User', 'editor_id');
    }

    public function category()
    {
        return $this->belongsTo('App\Modules\Category\Model\Category', 'category_id');
    }

    public function comment()
    {
        return $this->hasMany('App\Modules\Post\Model\Comment', 'post_id');
    }

    //=============scope query==============

    public function scopeSearch($query, $q, $result_type)
    {
        if ($q != null) {
            if ($result_type == 'slug') {
                $data = $query->where('slug','LIKE','%'.$q.'%');
            } else if ($result_type == 'id') {
                $data = $query->where('id',$q);
            } else if($result_type == 'title'){
                $data = $query->where(DB::Raw('lower(title)'),'LIKE','%'.$q.'%');
            } else if ($result_type == 'username') {
                $data = $query->whereHas('writer',function($result) {
                  $result->where('username',$q);
                });
            } else if ($result_type == 'first_name') {
                $data = $query->whereHas('writer',function($result) {
                      $result->where('first_name',$q);
                });
            } else if ($result_type == 'email') {
                $data = $query->whereHas('writer',function($result) {
                      $result->where('email',$q);
                });
            } else {
                $data = $query->where(DB::Raw('lower(title)'),'LIKE','%'.$q.'%');
            }
        } else {
            return null;
       // return $query->where(DB::Raw('lower(title)')); 
        }
        return $data;
    }

    public function scopeFilter($query, $filter)
    {
        if ($filter == 'pending') {
            $data = $query->where('status',0);
        } else if ($filter == 'publish') {
            $data = $query->where('status',1);
        } else if ($filter == 'draft') {
            $data = $query->where('status',2);
        } else if ($filter == 'suspend') {
            $data = $query->where('status',3);
        } else {
            $data = null;
        }
        return $data;
    }

    public function scopeSort($query, $sort)
    {
        if ($sort == 'latest') {
            $data = $query->orderBy('id','desc');
        } else if ($sort == 'oldest') {

        }
    }

    public function scopeByCategory($query,$category)
    {
        if ($category!= null) {
            $data = $query->whereHas('category', function($q) use ($category) {
                $q->where('slug',$category);
            });
        } else {
            $data = null;
        }
        return $data;
    }

    public function scopeFindBySlug($query, $slug)
    {
        return $query->where('slug',$slug)->first();
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured',1)
                     ->where('status',1)
                     ->get();
    }

    //=============end scope======================


    //=================get attriute ===========
    public function getTypeAttribute($value)
    {
        if($this->attributes['type'] == 1)
            return "gallery";
        elseif($this->attributes['type'] == 2)
            return "video";
    }

    public function setTypeAttribute($values)
    {
        if($values == 'image')
            $this->attributes['type'] = 1;
        else if($values == 'video')
            $this->attributes['type'] = 2;
    }

    public function getContentAttribute()
    {
        if ($this->attributes['type'] == 1) {
            $image = json_decode($this->attributes['content']);
            // dd($image);
            $data = [];
            if (count($image) > 0) {
                $images = ['square800','square500', 'square1502', 'square847', 'square540','square280'];
                foreach ($images as $img) {
                    $thumb = explode('.', $image->file);
                    $thumb[0] = $thumb[0].'_'.$img;
                    
                    $mergeImage = implode('.', $thumb);
                    $image->$img = asset('storage/'.$mergeImage);
                }
                
                /*$thumb = explode('.', $image[0]->file);
                $thumb[0] = $thumb[0].'_square500';
                $thumb = implode('.', $thumb);
                $content['thumb'] = $thumb;*/
            } else {
               $images = ['square800','square500', 'square1502', 'square847', 'square540','square280'];
                foreach ($images as $img) {
                    
                    $image->$img = asset('storage/default.gif');
                }
            }
            $content = $image;

            return $content;
        }
        else
            $content =  json_decode($this->attributes['content']);      
        return $content;
    }
}
