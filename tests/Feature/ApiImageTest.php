<?php

namespace Tests\Feature;

use App\Services\ImageService;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiImageTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

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

    public function test_it_should_not_store_files_with_extra_size()
    {
        config(['app.max_file_size_upload' => 1*1024]);

        $response = $this->postJson(route('api.store-files'), [
            'files' => [
                UploadedFile::fake()->image('avatar.jpg')->size(2*1024),
            ]
        ]);

        $response->assertStatus(422);
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

    public function test_it_can_create_resize_for_specific_image()
    {
        $savedFiles = $this->saveFiles();
        $imageId = $savedFiles[0]['id'];
        $imageName = $savedFiles[0]['name'];

        $width = 120;
        $height = 120;

        $this->postJson(route('api.create-resize'), [
            'image_id' => $imageId,
            'width' => $width,
            'height' => $height,
        ]);

        $resizeName = $this->imageService->getResizeImageName($imageName, $width, $height);

        Storage::disk('api')->assertExists('resize/'.$resizeName);
    }

    public function test_it_can_delete_all_resizes()
    {
        $savedFiles = $this->saveFiles();
        $imageId = $savedFiles[0]['id'];
        $imageName = $savedFiles[0]['name'];

        $this->postJson(route('api.create-resize'), [
            'image_id' => $imageId,
            'width' => 120,
            'height' => 120,
        ]);

        $this->deleteJson(route('api.delete-all-resizes'), [
            'image_id' => $imageId,
        ]);

        $resizeName1 = $this->imageService->getResizeImageName($imageName, 100, 100);
        $resizeName2 = $this->imageService->getResizeImageName($imageName, 120, 120);

        Storage::disk('api')->assertMissing('resize/'.$resizeName1);
        Storage::disk('api')->assertMissing('resize/'.$resizeName2);
    }

    public function test_it_delete_default_resize()
    {
        $savedFiles = $this->saveFiles();
        $imageId = $savedFiles[0]['id'];
        $imageName = $savedFiles[0]['name'];

        $this->deleteJson(route('api.delete-resize'), [
            'image_id' => $imageId,
            'width' => 100,
            'height' => 100,
        ]);

        $resizeName = $this->imageService->getResizeImageName($imageName, 100, 100);

        Storage::disk('api')->assertMissing('resize/'.$resizeName);
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
