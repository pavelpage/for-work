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

    private $originalFolderName;
    private $resizeFolderName;
    private $diskFolderName;

    public function __construct()
    {
        $this->originalFolderName = 'originals';
        $this->resizeFolderName = 'resize';
        $this->diskFolderName = config('filesystems.disks.api.folder_name');
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
                $imageItem = Image::addItem($fileName, $file->getClientOriginalName(), $file->getSize());
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
        Storage::disk('api')->put($this->originalFolderName, $file);

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
                $imageItem = Image::addItem($savedFile->getFilename(), $savedFile->getOriginalFileName(), $savedFile->getSize());
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
        Storage::disk('api')->put($this->originalFolderName.'/'.$filename, $fileStream);

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
                $imageItem = Image::addItem($savedFile->getFilename(), $savedFile->getOriginalFileName(), $savedFile->getSize());
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

        Storage::disk('api')->put($this->originalFolderName.'/'.$fileName, $content);

        return new SavedFile($fileName, $fileName, Storage::disk('api')->size($this->originalFolderName.'/'.$fileName));
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
        $image = ImageLib::make(Storage::disk('api')->get($this->originalFolderName.'/'.$imageItem->name));

        $publicDir = public_path($this->diskFolderName.'/'.$this->resizeFolderName);
        if (!is_dir($publicDir)) {
            File::makeDirectory($publicDir, 0755, true);
        }

        $resizeImageName = $this->getResizeImageName($imageItem->name, $width, $height);

        $image->resize($width, $height)->save($publicDir.'/'.$resizeImageName);

        $imageItem->addResize($width, $height);

        return Storage::disk('api')->url($this->resizeFolderName.'/'.$resizeImageName);
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

    /**
     * @param $fileName
     * @return bool|string
     */
    private function getFileExtension($fileName)
    {
        $lastDotPos = mb_strrpos($fileName, '.');
        if ( !$lastDotPos ) return false;
        return mb_substr($fileName, $lastDotPos+1);
    }

    /**
     * @param $imageId
     * @param $width
     * @param $height
     * @return bool
     */
    public function deleteImageResize($imageId, $width, $height)
    {
        $imageItem = Image::find($imageId);

        $resizeImageName = $this->getResizeImageName($imageItem->name, $width, $height);

        $successDelete = Storage::disk('api')->delete($this->resizeFolderName.'/'.$resizeImageName);

        if( $successDelete ) {
            $imageItem->deleteResize($width, $height);
        }

        return $successDelete;
    }

    /**
     * @param $imageId
     * @return bool
     */
    public function deleteAllImageResizes($imageId)
    {
        $imageItem = Image::find($imageId);
        $resizes = $imageItem->resizes;

        foreach ($resizes as $resize) {
            $this->deleteImageResize($imageId, $resize['width'], $resize['height']);
        }

        return true;
    }

    /**
     * @param $imageId
     * @return array
     */
    public function getImageResizes($imageId)
    {
        $imageItem = Image::find($imageId);
        $resizes = $imageItem->resizes;

        $result = [];
        foreach ($resizes as $resize) {

            $resizeImageName = $this->getResizeImageName($imageItem->name, $resize['width'], $resize['height']);

            $result[] = [
                'url' => url(Storage::disk('api')->url($this->resizeFolderName.'/'.$resizeImageName)),
                'width' => $resize['width'],
                'height' => $resize['height'],
            ];
        }

        return $result;
    }

    /**
     * @param $imageName
     * @param $width
     * @param $height
     * @return string
     */
    public function getResizeImageName($imageName, $width, $height)
    {
        return $this->getNameWithoutExtension($imageName) .
            '_[resize_' . $width . 'x' . $height . '].' .
            $this->getFileExtension($imageName);
    }
}