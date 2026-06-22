<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgressPhotoRequest;
use App\Models\ProgressPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProgressPhotoController extends Controller
{
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public function store(StoreProgressPhotoRequest $request): JsonResponse
    {
        $file      = $request->file('photo');
        $mime      = $file->getMimeType();
        $extension = self::MIME_EXTENSIONS[$mime] ?? $file->extension();
        $path      = 'progress-photos/' . Str::uuid() . '.' . $extension;

        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        $photo = ProgressPhoto::create([
            'user_id'      => $request->user()->id,
            'taken_at'     => $request->taken_at,
            'storage_path' => $path,
            'caption'      => $request->caption,
        ]);

        return response()->json($photo, 201);
    }

    public function index(Request $request): JsonResponse
    {
        $photos = ProgressPhoto::where('user_id', $request->user()->id)->get();

        return response()->json($photos);
    }

    public function destroy(Request $request, ProgressPhoto $photo): JsonResponse
    {
        if ($photo->user_id !== $request->user()->id) {
            abort(403);
        }

        if (Storage::disk('local')->exists($photo->storage_path)) {
            Storage::disk('local')->delete($photo->storage_path);
        }

        $photo->delete();

        return response()->json(null, 204);
    }
}
