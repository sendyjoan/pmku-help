<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary(
            Configuration::instance([
                'cloud' => [
                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                    'api_key' => env('CLOUDINARY_API_KEY'),
                    'api_secret' => env('CLOUDINARY_API_SECRET'),
                ],
                'url' => [
                    'secure' => true
                ]
            ])
        );
    }

    public function uploadImage($file, $options = [])
    {
        $defaultOptions = [
            'folder' => 'user_avatars',
            'transformation' => [
                'width' => 200,
                'height' => 200,
                'crop' => 'fill',
                'gravity' => 'face'
            ]
        ];

        $options = array_merge($defaultOptions, $options);

        return $this->cloudinary->uploadApi()->upload(
            $file instanceof \Illuminate\Http\UploadedFile ? $file->getRealPath() : $file,
            $options
        );
    }

    public function getInstance()
    {
        return $this->cloudinary;
    }
}
