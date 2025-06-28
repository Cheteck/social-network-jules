<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Disk
    |--------------------------------------------------------------------------
    |
    | This is the default disk where media files will be stored. You can
    | configure more disks in your config/filesystems.php file.
    | Examples: 'public', 's3'.
    |
    */
    'default_disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Default Directory
    |--------------------------------------------------------------------------
    |
    | The default directory within the chosen disk to store media.
    | You can use placeholders like {model_type}, {model_id}, {collection_name}.
    | Example: 'uploads/{model_type}/{model_id}/{collection_name}'
    |
    */
    'default_directory' => 'uploads',

    /*
    |--------------------------------------------------------------------------
    | Max File Size (in kilobytes)
    |--------------------------------------------------------------------------
    |
    | The maximum file size allowed for uploads.
    |
    */
    'max_file_size' => env('MEDIA_MAX_SIZE_KB', 5 * 1024), // 5MB

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    |
    | A list of allowed MIME types for uploaded files.
    | Example: ['image/jpeg', 'image/png', 'video/mp4']
    | An empty array means all types are allowed (not recommended for security).
    |
    */
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        // 'video/mp4',
        // 'application/pdf',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Optimizations
    |--------------------------------------------------------------------------
    |
    | Configure automatic image optimizations using Intervention Image.
    | - max_width: Resize images exceeding this width (pixels). Null to disable.
    | - max_height: Resize images exceeding this height (pixels). Null to disable.
    | - quality: Image quality for formats like JPEG (0-100).
    | - auto_orient: Automatically orient images based on EXIF data.
    | - convert_to_webp: Convert uploaded images to WebP format if supported.
    |
    */
    'image_optimizations' => [
        'enabled' => true,
        'max_width' => 1920,
        'max_height' => 1080,
        'quality' => 85,
        'auto_orient' => true,
        'convert_to_webp' => false, // Requires server/client support for WebP
        'webp_quality' => 80,
    ],

    /*
    |--------------------------------------------------------------------------
    | Keep Original Image
    |--------------------------------------------------------------------------
    |
    | If image optimizations are enabled, specify if the original uploaded
    | image should also be kept.
    |
    */
    'keep_original_image' => false,


    /*
    |--------------------------------------------------------------------------
    | Model Namespace
    |--------------------------------------------------------------------------
    |
    | The default namespace for models when resolving them from type aliases.
    |
    */
    'model_namespace' => 'App\\Models\\',

    /*
    |--------------------------------------------------------------------------
    | Media Model
    |--------------------------------------------------------------------------
    */
    'media_model' => \Ijideals\MediaUploader\Models\Media::class,

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    */
    'route_prefix' => 'api/v1/media',

    /*
    |--------------------------------------------------------------------------
    | Collections
    |--------------------------------------------------------------------------
    |
    | Define media collections for different model types or purposes.
    | This allows for specific validation rules, disk, or path per collection.
    |
    | Example:
    | 'user' => [
    |     'avatar' => [
    |         'disk' => 'public',
    |         'directory' => 'avatars/{model_id}',
    |         'max_file_size' => 1024, // 1MB
    |         'allowed_mime_types' => ['image/jpeg', 'image/png'],
    |         'single_file' => true, // Only one file allowed in this collection for a model
    |         'image_optimizations' => [ // Override global optimizations
    |             'max_width' => 300,
    |             'max_height' => 300,
    |             'quality' => 90,
    |         ]
    |     ],
    |     'gallery' => [
    |         'disk' => 's3',
    |         'directory' => 'user_galleries/{model_id}',
    |         'max_files' => 10, // Max 10 files in this collection for a model
    |     ]
    | ],
    | 'post' => [
    |      'images' => [
    |          'directory' => 'posts/{model_id}/images',
    |          'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/gif'],
    |      ]
    | ]
    */
    'collections' => [
        // Define your collections here
        'default' => [ // Default collection settings if no specific one is matched
            'disk' => null, // Uses 'default_disk'
            'directory' => null, // Uses 'default_directory'
            'max_file_size' => null, // Uses global 'max_file_size'
            'allowed_mime_types' => [], // Uses global 'allowed_mime_types', empty means all of those
            'single_file' => false,
            'max_files' => null, // No limit by default
            'image_optimizations' => [], // Uses global image_optimizations
        ],
    ],
];
