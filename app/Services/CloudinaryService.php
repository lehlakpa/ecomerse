<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CloudinaryService
{
    public function upload(UploadedFile $file): array
    {
        $storage = config('cloudinary.storage', 'auto');

        if ($storage === 'local' || ($storage === 'auto' && ! config('cloudinary.cloud_name'))) {
            $path = $file->store('products', 'public');
            if (! $path) {
                throw new RuntimeException('The image could not be stored.');
            }

            return ['url' => '/storage/'.$path, 'public_id' => 'local:'.$path];
        }

        $parameters = ['folder' => 'ecommerce/products', 'timestamp' => time()];
        $result = $this->http()
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post($this->endpoint('upload'), $this->signed($parameters))
            ->throw()->json();

        return ['url' => $result['secure_url'], 'public_id' => $result['public_id']];
    }

    public function delete(string $publicId): void
    {
        if (str_starts_with($publicId, 'local:')) {
            Storage::disk('public')->delete(substr($publicId, 6));

            return;
        }

        $this->http()->asForm()->post($this->endpoint('destroy'), $this->signed([
            'invalidate' => 'true',
            'public_id' => $publicId,
            'timestamp' => time(),
        ]))->throw();
    }

    private function endpoint(string $action): string
    {
        if (! config('cloudinary.cloud_name') || ! config('cloudinary.api_key') || ! config('cloudinary.api_secret')) {
            throw new RuntimeException('Complete the Cloudinary configuration to manage cloud images.');
        }

        return 'https://api.cloudinary.com/v1_1/'.rawurlencode(config('cloudinary.cloud_name')).'/image/'.$action;
    }

    private function http(): PendingRequest
    {
        return Http::connectTimeout(10)->timeout(30);
    }

    private function signed(array $parameters): array
    {
        ksort($parameters);
        $signature = urldecode(http_build_query($parameters)).config('cloudinary.api_secret');

        return [...$parameters, 'api_key' => config('cloudinary.api_key'), 'signature' => sha1($signature)];
    }
}
