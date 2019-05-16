<?php

namespace App\Http\Controllers;

use App\Services\ImageService;
use Illuminate\Http\Request;

class ApiImageController extends Controller
{
    //
    public function storeFile(Request $request, ImageService $imageService)
    {
        $this->validate($request, [
            'files.*' => 'image|required|max:'.config('app.max_file_size_upload'),
        ]);

        $files = $request->file('files');

        $savedFiles = $imageService->saveFilesAndRetrieveItems($files);

        return [
            'items' => $savedFiles->getSavedFiles(),
            'errors' => $savedFiles->getErrors(),
        ];
    }

    public function saveFileFromUrl(Request $request, ImageService $imageService)
    {
        $data = $this->validate($request, [
            'urls.*' => 'required|url|string',
        ]);

        $savedFiles = $imageService->uploadFromUrls($data['urls']);

        return [
            'items' => $savedFiles->getSavedFiles(),
            'errors' => $savedFiles->getErrors(),
        ];
    }

    public function saveFileFromBase64()
    {
        
    }

    public function createResize()
    {
        
    }
}
