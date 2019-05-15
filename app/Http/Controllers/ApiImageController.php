<?php

namespace App\Http\Controllers;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ApiImageController extends Controller
{
    //
    public function storeFile(Request $request, ImageService $imageService)
    {
        $files = $request->file('files');

        $savedFiles = $imageService->saveFilesAndRetrieveItems($files);

        return [
            'items' => $savedFiles->getSavedFiles(),
            'errors' => $savedFiles->getErrors(),
        ];
    }

    public function saveFileFromUrl()
    {
        
    }

    public function saveFileFromBase64()
    {
        
    }

    public function createResize()
    {
        
    }
}
