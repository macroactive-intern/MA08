<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgressPhotoRequest;
use App\Http\Resources\ProgressPhotoResource;
use App\Models\ProgressPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProgressPhotoController extends Controller
{
    public function store(StoreProgressPhotoRequest $request): JsonResponse
    {
        $file       = $request->file('photo');
        $mime       = $file->getMimeType();
        $extensions = config('progress_photos.mime_extensions');
        $extension  = $extensions[$mime] ?? $file->extension();
        $directory  = config('progress_photos.storage_directory');
        $path       = $directory . '/' . Str::uuid() . '.' . $extension;

        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        try {
            $photo = ProgressPhoto::create([
                'user_id'      => $request->user()->id,
                'taken_at'     => $request->taken_at,
                'storage_path' => $path,
                'caption'      => $request->caption,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        Log::info('photo.uploaded', [
            'photo_id' => $photo->id,
            'user_id'  => $request->user()->id,
            'path'     => $path,
        ]);

        return ProgressPhotoResource::make($photo)->response()->setStatusCode(201);
    }

    public function index(Request $request): JsonResponse
    {
        $photos = ProgressPhoto::where('user_id', $request->user()->id)->orderBy('taken_at', 'desc')->get();

        return ProgressPhotoResource::collection($photos)->response();
    }

    public function destroy(Request $request, ProgressPhoto $photo): JsonResponse
    {
        $this->authorize('delete', $photo);

        $path = $photo->storage_path;

        $photo->delete();

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        Log::info('photo.deleted', [
            'photo_id' => $photo->id,
            'user_id'  => $request->user()->id,
        ]);

        return response()->json(null, 204);
    }
}
