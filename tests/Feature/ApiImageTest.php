<?php

namespace Tests\Feature;

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiImageTest extends TestCase
{
    use RefreshDatabase;

    /** @var ImageService */
    private $imageService;

    public function setUp()
    {
        parent::setUp();
        $this->imageService = resolve(ImageService::class);
    }

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

    public function test_it_can_store_base64_strings_as_files()
    {
        $response = $this->postJson(route('api.store-from-base64'), [
            'files' => [
                $this->getBase64ImageString(),
            ]
        ]);

        $response->assertStatus(200);
        $content = $response->getOriginalContent();

        $this->assertCount(1, $content['items']);
        $this->assertCount(0, $content['errors']);
    }

    public function test_it_should_not_store_incorrect_base64_strings()
    {
        $response = $this->postJson(route('api.store-from-base64'), [
            'files' => [
                'wrong base 64 string',
            ]
        ]);

        $content = $response->getOriginalContent();

        $this->assertCount(0, $content['items']);
        $this->assertCount(1, $content['errors']);
    }

    public function test_it_can_get_list_of_resizes()
    {
        $savedFiles = $this->saveFiles();
        $imageId = $savedFiles[0]['id'];
        $imageName = $savedFiles[0]['name'];
        $resizeName = $this->imageService->getResizeImageName($imageName, 100, 100);

        $response = $this->getJson(route('api.get-image-resizes', ['image_id' => $imageId]));
        $this->assertEquals(url('upload/resize/'.$resizeName), $response->getOriginalContent()[0]['url']);
    }

    private function saveFiles()
    {
        $response = $this->postJson(route('api.store-files'), [
            'files' => [
                UploadedFile::fake()->image('avatar.jpg'),
                UploadedFile::fake()->image('avatar2.jpg')
            ]
        ]);

        return $response->getOriginalContent()['items'];
    }

    private function getBase64ImageString()
    {
        return file_get_contents(resource_path('test/base64_example.txt'));
    }
}
