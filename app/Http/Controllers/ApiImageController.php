<?php

namespace App\Http\Controllers;

use App\Services\ImageService;
use Illuminate\Http\Request;

class ApiImageController extends Controller
{
    //
    /**
     * @var ImageService
     */
    private $imageService;

    /**
     * ApiImageController constructor.
     * @param ImageService $imageService
     */
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function storeFile(Request $request)
    {
        $this->validate($request, [
            'files.*' => 'image|required|max:'.config('app.max_file_size_upload'),
        ]);

        $files = $request->file('files');

        $savedFiles = $this->imageService->saveFilesAndRetrieveItems($files);

        return [
            'items' => $savedFiles->getSavedFiles(),
            'errors' => $savedFiles->getErrors(),
        ];
    }

    public function saveFileFromUrl(Request $request)
    {
        $data = $this->validate($request, [
            'urls.*' => 'required|url|string',
        ]);

        $savedFiles = $this->imageService->uploadFromUrls($data['urls']);

        return [
            'items' => $savedFiles->getSavedFiles(),
            'errors' => $savedFiles->getErrors(),
        ];
    }

    public function saveFileFromBase64(Request $request)
    {
        $data = $this->validate($request, [
            'files.*' => 'required|string',
        ]);

        $savedFiles = $this->imageService->saveFilesFromBase64($data['files']);

        return [
            'items' => $savedFiles->getSavedFiles(),
            'errors' => $savedFiles->getErrors(),
        ];
    }

    public function createResize(Request $request)
    {
        $resizeUrl = $this->imageService->createResize(
            $request->input('image_id'), $request->input('width', 100), $request->input('height', 100)
        );

        return [
            'url' => $resizeUrl,
        ];
    }
}
