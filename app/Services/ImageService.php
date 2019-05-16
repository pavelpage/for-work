<?php


namespace App\Services;

use App\Business\SavedFile;
use App\Image;
use App\Jobs\CreateResize;
use File;
use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Storage;
use Intervention\Image\Facades\Image as ImageLib;

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
                CreateResize::dispatch($imageItem->id);
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
                CreateResize::dispatch($imageItem->id);
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

    public function saveFilesFromBase64($files)
    {
        $savedFiles = new SavedFiles();

        foreach ($files as $file) {
            try {
                $savedFile = $this->saveFileFromBase64($file);
                $imageItem = Image::create([
                    'name' => $savedFile->getFilename(),
                    'original_name' => $savedFile->getOriginalFileName(),
                    'file_info' => json_encode([
                        'size' => $savedFile->getSize(),
                    ]),
                ]);
                CreateResize::dispatch($imageItem->id);
                $savedFiles->pushSavedFile($imageItem);
            } catch (\Exception $e) {
                $savedFiles->pushError([$e->getCode(), $e->getMessage()]);
            }
        }

        return $savedFiles;
    }

    /**
     * @param $base64String
     * @return SavedFile
     * @throws \Exception
     */
    private function saveFileFromBase64($base64String)
    {
        $data = explode(',', $base64String);
        $content = base64_decode($data[1]);
        $fileExtension = $this->getExtensionFromBase64String($base64String);
        $fileName = $this->generateUniqueName($fileExtension);

        Storage::disk('api')->put('originals/'.$fileName, $content);

        return new SavedFile($fileName, $fileName, Storage::disk('api')->size('originals/'.$fileName));
    }

    /**
     * @param $base64String
     * @return string
     * @throws \Exception
     */
    public function getExtensionFromBase64String($base64String)
    {
        $data = explode(',', $base64String);
        $mimeType = explode(':',$data[0]);
        $mimeType = $mimeType[1];

        $mimeType = substr($mimeType, 0, strpos($mimeType, ';'));

        $extensionsArr = [
            'image/gif' => 'gif',
            'image/jpeg' => 'jpeg',
            'image/pjpeg' => 'jpeg',
            'image/png' => 'png',
        ];

        if (!isset($extensionsArr[$mimeType])) {
            throw new \Exception('Unsupported mime type of base64 image');
        }

        return $extensionsArr[$mimeType];
    }

    public function createResize($imageId, $width = 100, $height = 100)
    {
        $imageItem = Image::find($imageId);
        $image = ImageLib::make(Storage::disk('api')->get('originals/'.$imageItem->name));

        if (!is_dir(public_path('upload/resize'))) {
            File::makeDirectory(public_path('upload/resize'), 0755, true);
        }

        $resizeImageName = $this->getResizeImageName($imageItem->name, $width, $height);

        $image->resize($width, $height)->save(public_path('upload/resize').'/'.$resizeImageName);

        $imageItem->addResize($width, $height);

        return url('upload/resize/'.$resizeImageName);
    }

    /**
     * @param $name
     * @return bool|string
     */
    private function getNameWithoutExtension($name)
    {
        $posLastPoint = mb_strrpos($name, ".");

        if ($posLastPoint !== false) {
            $name = mb_substr($name, 0, $posLastPoint);
            return $name;
        }
        return false;
    }

    private function getFileExtension($fileName)
    {
        $lastDotPos = mb_strrpos($fileName, '.');
        if ( !$lastDotPos ) return false;
        return mb_substr($fileName, $lastDotPos+1);
    }

    public function deleteImageResize($imageId, $width, $height)
    {
        $imageItem = Image::find($imageId);

        $resizeImageName = $this->getResizeImageName($imageItem->name, $width, $height);

        $successDelete = Storage::disk('api')->delete('resize/'.$resizeImageName);

        if( $successDelete ) {
            $imageItem->deleteResize($width, $height);
        }

        return $successDelete;
    }

    public function deleteAllImageResizes($imageId)
    {
        $imageItem = Image::find($imageId);
        $resizes = $imageItem->resizes;

        foreach ($resizes as $resize) {
            $this->deleteImageResize($imageId, $resize['width'], $resize['height']);
        }

        return true;
    }

    public function getImageResizes($imageId)
    {
        $imageItem = Image::find($imageId);
        $resizes = $imageItem->resizes;

        $result = [];
        foreach ($resizes as $resize) {

            $resizeImageName = $this->getResizeImageName($imageItem->name, $resize['width'], $resize['height']);

            $result[] = [
                'url' => url(Storage::disk('api')->url('resize/'.$resizeImageName)),
                'width' => $resize['width'],
                'height' => $resize['height'],
            ];
        }

        return $result;
    }

    public function getResizeImageName($imageName, $width, $height)
    {
        return $this->getNameWithoutExtension($imageName) .
            '_[resize_' . $width . 'x' . $height . '].' .
            $this->getFileExtension($imageName);
    }
}