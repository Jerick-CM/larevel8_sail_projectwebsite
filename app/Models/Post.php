<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tag;

class Post extends Model
{
    use HasFactory, SoftDeletes;


    protected $table = "posts";
    public $timestamps = true;
    protected $fillable = [
        'title', 'content', 'user_id', 'name'
    ];


    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
