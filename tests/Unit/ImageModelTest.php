<?php

namespace Tests\Unit;

use App\Image;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImageModelTest extends TestCase
{
    use RefreshDatabase;


    public function test_it_delete_resize()
    {
        /** @var Image $item */
        $item = factory(Image::class)->create([
            'name' => 'test.jpg',
            'resizes' => [
                [
                    'width' => 100,
                    'height' => 100,
                ],
                [
                    'width' => 120,
                    'height' => 120,
                ],
            ],
        ]);

        $item->deleteResize(100, 100);

        $item = $item->fresh();

        $this->assertEquals([
            'width' => 120,
            'height' => 120,
        ],$item->resizes[0]);
        $this->assertCount(1, $item->resizes);
    }

    public function test_it_can_add_resize()
    {
        /** @var Image $item */
        $item = factory(Image::class)->create([
            'resizes' => [
                [
                    'width' => 100,
                    'height' => 100,
                ],
            ],
        ]);

        $item->addResize(120, 120);

        $item = $item->fresh();
        $this->assertEquals([
            'width' => 120,
            'height' => 120,
        ],$item->resizes[1]);
        $this->assertCount(2, $item->resizes);
    }
}
