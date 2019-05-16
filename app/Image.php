<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
        'resizes' => 'array',
    ];

    public function addResize($width, $height)
    {
        $resizes = $this->resizes;
        $resizes[] = ['width' => $width, 'height' => $height];

        $this->resizes = $resizes;
        $this->save();
    }

    public function deleteResize($width, $height)
    {
        $resizes = collect($this->resizes);

        $resizes = $resizes->filter(function($item, $key) use ($width, $height){
            return !($item['width'] == $width && $item['height'] == $height);
        });

        $this->resizes = $resizes->values()->toArray();
        $this->save();
    }
}
