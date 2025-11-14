<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageService
{
    /**
     * Compress and convert image to WebP format
     *
     * @param UploadedFile|string $image - Either UploadedFile or path to existing file
     * @param string $directory - Directory to store the image
     * @param int $quality - WebP quality (1-100), default 80
     * @param int|null $maxWidth - Maximum width in pixels, null for no resize
     * @param int|null $maxHeight - Maximum height in pixels, null for no resize
     * @return string - Returns the stored file path
     */
    public function compressAndStore(
        UploadedFile|string $image,
        string $directory = 'images',
        int $quality = 80,
        ?int $maxWidth = 1920,
        ?int $maxHeight = 1920
    ): string {
        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.webp';
        $path = $directory . '/' . $filename;

        // Load image
        if ($image instanceof UploadedFile) {
            $img = Image::read($image->getRealPath());
        } else {
            $img = Image::read($image);
        }

        // Resize if max dimensions are specified
        if ($maxWidth || $maxHeight) {
            $img->scale(width: $maxWidth, height: $maxHeight);
        }

        // Encode to WebP format with quality
        $encodedImage = $img->toWebp($quality);

        // Store to public disk
        Storage::disk('public')->put($path, $encodedImage);

        return $path;
    }

    /**
     * Compress and store multiple images
     *
     * @param array $images - Array of UploadedFile
     * @param string $directory - Directory to store images
     * @param int $quality - WebP quality
     * @param int|null $maxWidth - Maximum width
     * @param int|null $maxHeight - Maximum height
     * @return array - Returns array of stored file paths
     */
    public function compressAndStoreMultiple(
        array $images,
        string $directory = 'images',
        int $quality = 80,
        ?int $maxWidth = 1920,
        ?int $maxHeight = 1920
    ): array {
        $paths = [];

        foreach ($images as $image) {
            $paths[] = $this->compressAndStore(
                $image,
                $directory,
                $quality,
                $maxWidth,
                $maxHeight
            );
        }

        return $paths;
    }

    /**
     * Delete image from storage
     *
     * @param string|array $path - Path or paths to delete
     * @return bool
     */
    public function delete(string|array $path): bool
    {
        return Storage::disk('public')->delete($path);
    }

    /**
     * Get file size in KB
     *
     * @param string $path - Path to file
     * @return float - Size in KB
     */
    public function getFileSize(string $path): float
    {
        return Storage::disk('public')->size($path) / 1024;
    }

    /**
     * Get compression ratio (original vs compressed)
     *
     * @param UploadedFile $originalImage
     * @param string $compressedPath
     * @return array - ['original_kb', 'compressed_kb', 'saved_kb', 'saved_percentage']
     */
    public function getCompressionStats(UploadedFile $originalImage, string $compressedPath): array
    {
        $originalSize = $originalImage->getSize() / 1024; // KB
        $compressedSize = $this->getFileSize($compressedPath);
        $savedSize = $originalSize - $compressedSize;
        $savedPercentage = ($savedSize / $originalSize) * 100;

        return [
            'original_kb' => round($originalSize, 2),
            'compressed_kb' => round($compressedSize, 2),
            'saved_kb' => round($savedSize, 2),
            'saved_percentage' => round($savedPercentage, 2),
        ];
    }
}
