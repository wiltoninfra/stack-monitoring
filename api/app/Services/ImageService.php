<?php

namespace Promo\Services;

use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * Envia imagem ao S3
     *
     * @param string $base64_image
     * @return string
     */
    public function upload(string $base64_image): string
    {
        $image_content = base64_decode(explode(',', $base64_image)[1]);

        $image_name = 'banners/' . uniqid('banner_') . ".png";

        Storage::disk('s3')->put($image_name, $image_content, 'public');
        $image_url = Storage::disk('s3')->url($image_name);

        return $image_url;
    }

}