<?php

namespace App\Modules\Post\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    
    protected $table = 'comments';
    use SoftDeletes;

    protected $hidden = ['created_at', 'updated_at','deleted_at', 'user_id', 'admin_id', 'post_id'];
    // protected $appends = ['full_name'];
    
   public function post()
   {
       return $this->belongsTo('App\Modules\Post\Model\Post','post_id');
   }

   public function user()
   {
       return $this->belongsTo('App\Modules\User\Model\User','user_id');
   }

   public function admin()
   {
       return $this->belongsTo('App\Modules\User\Model\User','admin_id');
   }   

   //========scope query ============

   public function scopeSearch($query, $q, $result_type)
   {
      if ($q != null) {
        if ($result_type == 'word') {
            $data = $query->where(DB::Raw('lower(comment)'),'LIKE','%'.$q.'%');
        } else if ($result_type == 'post') {
            $data = $query->where('id',$q);
        } else if ($result_type == 'username') {
            $data = $query->whereHas('user',function($result) {
                $result->where('username',$q);
            });
        } else if ($result_type == 'first_name') {
            $data = $query->whereHas('user',function($result) {
                $result->where('first_name',$q);
            });
        } else if ($result_type == 'email') {
            $data = $query->whereHas('user',function($result) {
                $result->where('email',$q);
            });
        } else {
          $data = $query->where(DB::Raw('lower(comment)'),'LIKE','%'.$q.'%');
        }
      } else {
        $data = null;
      }
      return $data;
   }

   public function scopeFindByPost($query,$post_id)
   {
      $query->where('post_id',$post_id);
   }

   //=============end scope query


}
