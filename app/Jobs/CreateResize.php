<?php

namespace App\Jobs;

use App\Services\ImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateResize implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $imageId;
    /**
     * @var int
     */
    private $width;
    /**
     * @var int
     */
    private $height;

    /**
     * Create a new job instance.
     *
     * @param $imageId
     * @param int $width
     * @param int $height
     */
    public function __construct($imageId, $width = 100, $height = 100)
    {
        //
        $this->imageId = $imageId;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ImageService $imageService)
    {
        //
        $imageService->createResize($this->imageId, $this->width, $this->height);
    }
}
