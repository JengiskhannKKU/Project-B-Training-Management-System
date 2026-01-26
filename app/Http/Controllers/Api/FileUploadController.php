<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FileUploadController extends Controller
{
    /**
     * Upload an image file for courses.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function image(Request $request)
    {
        if (!$request->user()->isRole('admin')) {
            return $this->forbiddenResponse('Only admin can upload course images.');
        }

        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        $file = $request->file('image');

        // SECURITY FIX: Validate actual file content, not just MIME type
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            throw ValidationException::withMessages([
                'image' => ['The uploaded file is not a valid image.']
            ]);
        }

        // Verify the image type is one we support
        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        if (!in_array($imageInfo[2], $allowedTypes)) {
            throw ValidationException::withMessages([
                'image' => ['The image type is not supported. Only JPEG, PNG, GIF, and WebP are allowed.']
            ]);
        }

        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        
        // Store file in public/courses directory
        $path = $file->storeAs('courses', $filename, 'public');
        
        if (!$path) {
            return $this->errorResponse('Failed to upload image', 500);
        }
        
        // Return relative URL path (works regardless of APP_URL/port)
        $url = '/storage/' . $path;

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
        
        // Security check: only allow deletion from courses directory
        if (!str_starts_with($path, 'courses/')) {
            return $this->errorResponse('Invalid file path', 400);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return $this->successResponse(null, 'Image deleted successfully');
        }

        return $this->errorResponse('Image not found', 404);
    }
}
