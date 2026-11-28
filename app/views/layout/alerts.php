<?php

/**
 * Renders standardized admin success/error flash banners from query parameters.
 */
function render_admin_alerts(array $successMessages, array $errorMessages = []): void
{
    $successKey = (string) ($_GET['success'] ?? '');
    $errorKey = (string) ($_GET['error'] ?? '');

    if ($successKey !== '' && isset($successMessages[$successKey])) {
        echo '<div class="admin-alert-success" role="status">'
            . '<i class="fa-solid fa-circle-check" aria-hidden="true"></i>'
            . '<span>' . e($successMessages[$successKey]) . '</span>'
            . '</div>';
    }

    if ($errorKey !== '' && isset($errorMessages[$errorKey])) {
        echo '<div class="admin-alert-error" role="alert">'
            . '<i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>'
            . '<span>' . e($errorMessages[$errorKey]) . '</span>'
            . '</div>';
    }
}
