<?php


namespace App\Services;

use App\Business\SavedFile;
use App\Image;
use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Storage;

class ImageService
{

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
        Storage::disk('api')->put('originals', $file);

        return $file->hashName();
    }

    /**
     * @param $urlsArr
     * @return SavedFiles
     */
    public function uploadFromUrls($urlsArr)
    {
        $savedFiles = new SavedFiles();

        foreach ($urlsArr as $url) {
            try {
                $savedFile = $this->uploadFromUrl($url);
                $imageItem = Image::create([
                    'name' => $savedFile->getFilename(),
                    'original_name' => $savedFile->getOriginalFileName(),
                    'file_info' => json_encode([
                        'size' => $savedFile->getSize(),
                    ]),
                ]);
                $savedFiles->pushSavedFile($imageItem);
            } catch (\Exception $e) {
                $savedFiles->pushError([$e->getCode(), $e->getMessage(), $url]);
            }
        }

        return $savedFiles;
    }

    /**
     * @param $url
     * @return SavedFile
     * @throws \Exception
     */
    public function uploadFromUrl($url)
    {
        $imageInfo = @getimagesize($url);

        if (!$imageInfo) {
            throw new \Exception('File should be image format!');
        }

        $fileExtension = $this->getExtensionFromMimeInfo($imageInfo);
        if (!in_array($fileExtension, ['jpg','jpeg', 'JPG', 'PNG', 'png', 'gif', 'bmp', 'svg'])) {
            throw new \Exception('File should be image format!');
        }

        $client = new Client();

        $response = $client->get($url)->getBody();

        $fileStream = $response->getContents();
        $originalFileName = $this->getOriginalNameFromUrl($url);
        $fileSize = $response->getSize() / 1024;

        if ($fileSize > config('app.max_file_size_upload')) {
            throw new \Exception('to big image for this request');
        }

        $filename = $this->generateUniqueName($fileExtension);
        Storage::disk('api')->put('originals/'.$filename, $fileStream);


        return new SavedFile($filename, $originalFileName, $response->getSize());
    }

    /**
     * @param $info
     * @return mixed
     */
    private function getExtensionFromMimeInfo($info)
    {
        return explode('/', $info['mime'])[1];
    }

    /**
     * @param $url
     * @return bool|string
     */
    private function getOriginalNameFromUrl($url)
    {
        return substr($url, strrpos($url, '/') + 1);
    }

    /**
     * @param $extension
     * @return string
     */
    public function generateUniqueName($extension)
    {
        $randomString = str_random(40);
        $newName = $randomString . '.' . $extension;
        return $newName;
    }
}