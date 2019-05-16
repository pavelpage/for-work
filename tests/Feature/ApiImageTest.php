<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_store_files()
    {
        $response = $this->postJson(route('api.store-files'), [
            'files' => [
                UploadedFile::fake()->image('avatar.jpg'),
                UploadedFile::fake()->image('avatar2.jpg')
            ]
        ]);

        $response->assertStatus(200);
        $content = $response->getOriginalContent();

        $this->assertCount(2, $content['items']);
        $this->assertCount(0, $content['errors']);
    }
}
