<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Storage;

class FirebaseService
{
    protected $storage;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));

        $this->storage = $factory->createStorage();
    }

    public function uploadFileAvatar(UploadedFile $file, $folder = 'avatars')
    {
        $fileName = $folder . '/' . uniqid() . '.' . $file->getClientOriginalExtension();
        // $fileName = $folder . '/' . '.' . $file->getClientOriginalExtension();
        $bucket = $this->storage->getBucket();
        $object = $bucket->upload(
            fopen($file->getRealPath(), 'r'),
            [
                'name' => $fileName
            ]
        );
        
        $object->update([
            'acl' => [],
            'contentType' => $file->getClientMimeType()
        ], [
            'predefinedAcl' => 'publicRead'
        ]);

        return $object->info()['mediaLink'];
    }

    public function deleteFile($fileUrl)
    {
        $bucket = $this->storage->getBucket();
        $parsed_url = parse_url($fileUrl);
        $path = $parsed_url['path'];
        $fileName = basename($path);
        $decoded_path = urldecode($path);
        $fileName = basename($decoded_path);
        $object = $bucket->object('avatars/'.$fileName);

        if ($object->exists()) {
            if ($fileName != "default-avatar-1.png" && $fileName != "default-avatar-2.png" && $fileName != "default-avatar-null.png") {
                $object->delete();
            }
        } else {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => 'No file was deleted.'
            ], 500);
        }
    }
}

