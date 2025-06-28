<?php

return [
    // Controller messages
    'model_not_found' => 'Modèle non trouvé ou ne supporte pas les médias.',
    'unauthorized_action' => 'Vous n\'êtes pas autorisé à effectuer cette action sur les médias de ce modèle.',
    'upload_success' => 'Média téléversé avec succès.',
    'upload_failed' => 'Échec du téléversement du média.',
    'media_not_found' => 'Média non trouvé.',
    'delete_success' => 'Média supprimé avec succès.',
    'delete_failed' => 'Échec de la suppression du média.',
    'delete_error' => 'Impossible de supprimer le média : :error',
    'reorder_success' => 'Médias réorganisés avec succès.',

    // Service validation messages
    'file_or_model_not_set' => 'Fichier ou modèle non défini pour le téléversement de média.',
    'file_too_large' => 'Le fichier est trop volumineux. Taille maximale : :maxSizeKB Ko.',
    'invalid_mime_type' => 'Type de fichier invalide : :mimeType. Types autorisés : :allowedTypes.',
    'processing_error' => 'Échec du traitement de l\'image :originalName. Erreur : :error',
];
