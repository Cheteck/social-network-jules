<?php

return [
    // Controller messages
    'model_not_found' => 'Model not found or does not support media.',
    'unauthorized_action' => 'You are not authorized to perform this action on this model\'s media.',
    'upload_success' => 'Media uploaded successfully.',
    'upload_failed' => 'Failed to upload media.',
    'media_not_found' => 'Media not found.',
    'delete_success' => 'Media deleted successfully.',
    'delete_failed' => 'Failed to delete media.',
    'delete_error' => 'Could not delete media: :error',
    'reorder_success' => 'Media reordered successfully.',

    // Service validation messages
    'file_or_model_not_set' => 'File or model not set for media uploader.',
    'file_too_large' => 'File is too large. Max size: :maxSizeKB KB.',
    'invalid_mime_type' => 'Invalid file type: :mimeType. Allowed types: :allowedTypes.',
    'processing_error' => 'Image processing failed for :originalName. Error: :error',
];
