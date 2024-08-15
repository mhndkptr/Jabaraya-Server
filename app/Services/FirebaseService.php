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

    public function deleteFile($fileUrl)
    {
        $bucket = $this->storage->getBucket();
        $parsed_url = parse_url($fileUrl);
        $path = $parsed_url['path'];
        $fileName = basename($path);
        $decodedFileName = urldecode($fileName);
        $segments = explode('/', $decodedFileName);
        $folder = $segments[0] ?? 'etc';
        $fileName = $segments[1] ?? 'undefined';
        $object = $bucket->object($folder.'/'.$fileName);

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

    public function uploadFile(UploadedFile $file, $folder = 'etc')
    {
        $fileName = $folder . '/' . uniqid() . '.' . $file->getClientOriginalExtension();
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
}

