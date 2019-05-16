<?php

use App\Image;
use Faker\Generator as Faker;

$factory->define(Image::class, function (Faker $faker) {
    return [
        //
        'name' => 'test.jpg',
        'original_name' => $faker->word.'.jpg',
        'file_info' => json_encode([
            'size' => 1024,
        ]),
        'resizes' => json_encode([
            [
                'width' => 100,
                'height' => 100,
            ]
        ]),
    ];
});
