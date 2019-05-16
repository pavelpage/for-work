<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    //
    protected $guarded = [];

    public function addResize($width, $height)
    {
        $resizes = $this->resizes;
        if (empty($resizes)) {
            $resizes = [
                [$width, $height]
            ];
        }
        else {
            $resizes = json_decode($resizes);
            $resizes[] = [$width, $height];
        }

        $this->update([
            'resizes' => json_encode($resizes),
        ]);
    }
}
