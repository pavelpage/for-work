<?php


namespace App\Services;

use App\Image;
use File;
use Illuminate\Http\UploadedFile;

class ImageService
{
    public function __construct()
    {

    }

    /**
     * @param $filesArr \Illuminate\Http\UploadedFile[]
     */
    public function saveFilesAndRetrieveItems($filesArr)
    {
        $savedFiles = new SavedFiles();
        foreach ($filesArr as $file) {
            try {
                $fileName = $this->saveFileAndGetStoredName($file);
                $imageItem = Image::create([
                    'name' => $fileName,
                    'original_name' => $file->getClientOriginalName(),
                    'file_info' => json_encode([
                        'size' => $file->getSize(),
                    ]),
                ]);
                $savedFiles->pushSavedFile($imageItem);
            } catch (\Exception $e) {
                $savedFiles->pushError([$e->getCode(), $e->getMessage(), $file->getClientOriginalName()]);
            }
        }

        return $savedFiles;
    }

    private function saveFileAndGetStoredName(UploadedFile $file)
    {
        \Storage::disk('api')->put('originals', $file);

        return $file->hashName();
    }
}