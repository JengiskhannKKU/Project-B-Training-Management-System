<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FileUploadController extends Controller
{
    /**
     * Upload an image file for programs.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function image(Request $request)
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        $file = $request->file('image');
        
        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        
        // Store file in public/programs directory
        $path = $file->storeAs('programs', $filename, 'public');
        
        if (!$path) {
            return $this->errorResponse('Failed to upload image', 500);
        }
        
        // Return the public URL
        $url = Storage::disk('public')->url($path);
        
        return $this->successResponse([
            'url' => $url,
            'path' => $path,
            'filename' => $filename,
        ], 'Image uploaded successfully');
    }

    /**
     * Delete an uploaded image.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $path = $request->input('path');
        
        // Security check: only allow deletion from programs directory
        if (!str_starts_with($path, 'programs/')) {
            return $this->errorResponse('Invalid file path', 400);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return $this->successResponse(null, 'Image deleted successfully');
        }

        return $this->errorResponse('Image not found', 404);
    }
}
