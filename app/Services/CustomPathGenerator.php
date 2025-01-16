<?php

namespace App\Services;

use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class CustomPathGenerator implements PathGenerator
{


    public function getPath(\Spatie\MediaLibrary\MediaCollections\Models\Media $media): string
    {
        return md5($media->id . config('app.key') . '/');
    }

    public function getPathForConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media): string
    {
        return md5($media->id . config('app.key') . '/conversions/');
    }

    public function getPathForResponsiveImages(\Spatie\MediaLibrary\MediaCollections\Models\Media $media): string
    {
        return md5($media->id . config('app.key') . '/responsive-images/');
    }
}
