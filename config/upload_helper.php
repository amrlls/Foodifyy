<?php
require_once __DIR__ . '/cloudinary.php';

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;

//user recipe
function uploadToCloudinary($filePath, $folder = 'foodify/recipes') {
    try {
        $upload = (new UploadApi())->upload($filePath, [
            'folder' => $folder,
            'transformation' => [
                'width'        => 800,
                'height'       => 600,
                'crop'         => 'fill',
                'quality'      => 'auto',
                'fetch_format' => 'auto'
            ]
        ]);
        return $upload['secure_url'];
    } catch (Exception $e) {
        error_log('Cloudinary upload error: ' . $e->getMessage());
        return null;
    }
}
// admin recipe
function uploadRecipeImageToCloudinary($filePath) {
    try {
        $upload = (new UploadApi())->upload($filePath, [
            'folder'  => 'foodify/admin_recipes',
            'quality' => 'auto',
        ]);
        return $upload['secure_url'];
    } catch (Exception $e) {
        error_log('Cloudinary recipe image error: ' . $e->getMessage());
        return null;
    }
}
//admin recipe
function uploadVideoToCloudinary($filePath, $folder = 'foodify/recipe_video') {
    try {
        $upload = (new UploadApi())->upload($filePath, [
            'folder'        => $folder,
            'resource_type' => 'video',
            'quality'       => 'auto',
        ]);
        return $upload['secure_url'];
    } catch (Exception $e) {
        error_log('Cloudinary video upload error: ' . $e->getMessage());
        return null;
    }
}
//admin upload items image
function uploadItemImageToCloudinary($filePath) {
    try {
        $upload = (new UploadApi())->upload($filePath, [
            'folder'  => 'foodify/items',
            'quality' => 'auto',
        ]);
        return $upload['secure_url'];
    } catch (Exception $e) {
        error_log('Cloudinary recipe image error: ' . $e->getMessage());
        return null;
    }
}
function deleteFromCloudinary($imageUrl) {
    if (empty($imageUrl) || !str_starts_with($imageUrl, 'http')) return;

    try {
        // Extract public_id dari URL
        // Format: https://res.cloudinary.com/cloud_name/image/upload/v123/foodify/user_recipes/filename.jpg
        $parts = explode('/upload/', $imageUrl);
        if (count($parts) < 2) return;

        $publicIdWithVersion = $parts[1];
        // Buang version number (v123/)
        $publicId = preg_replace('/^v\d+\//', '', $publicIdWithVersion);
        // Buang extension (.jpg, .png, etc)
        $publicId = preg_replace('/\.[^.]+$/', '', $publicId);

        (new AdminApi())->deleteAssets([$publicId]);
    } catch (Exception $e) {
        error_log('Cloudinary delete error: ' . $e->getMessage());
    }
}

function getImageSrc($image, $basePath = '') {
    if (empty($image)) return null;
    if (str_starts_with($image, 'http')) return $image;
    return $basePath . $image;
}