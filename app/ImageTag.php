<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImageTag extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'image_tags';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the media that have this tag.
     */
    public function media()
    {
        return $this->hasMany(\App\Media::class, 'image_tag_id');
    }
}
