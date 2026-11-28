<?php

/**
 * Renders a table thumbnail for a logo with an icon fallback when missing or broken.
 */
function render_logo_thumbnail(?string $filename, string $alt, string $fallbackIcon): void
{
    $logo = (string) ($filename ?? '');
    echo '<div class="admin-logo-box">';
    if ($logo !== '') {
        echo '<img src="' . e(asset_url($logo, 'logo')) . '"'
            . ' alt="' . e($alt) . '"'
            . ' class="admin-logo-thumb"'
            . ' loading="lazy"'
            . ' onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'inline-flex\';">'
            . '<span class="admin-logo-placeholder" style="display:none">'
            . '<i class="fa-solid ' . e($fallbackIcon) . '" aria-hidden="true"></i>'
            . '</span>';
    } else {
        echo '<span class="admin-logo-placeholder">'
            . '<i class="fa-solid ' . e($fallbackIcon) . '" aria-hidden="true"></i>'
            . '</span>';
    }
    echo '</div>';
}

/**
 * Renders an optional logo replacement field with current image preview and live FileReader preview.
 */
function render_logo_edit_field(?string $filename, string $alt, string $fallbackIcon): void
{
    $logo = (string) ($filename ?? '');
    ?>
    <div class="mb-3">
        <label class="form-label">Logo Saat Ini</label>
        <div class="form-logo-preview mb-2">
            <?php if ($logo !== ''): ?>
            <img src="<?= e(asset_url($logo, 'logo')) ?>"
                alt="<?= e($alt) ?>"
                id="previewLogo"
                onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
            <span class="admin-logo-placeholder" id="previewPlaceholder" style="display:none">
                <i class="fa-solid <?= e($fallbackIcon) ?>" aria-hidden="true"></i>
            </span>
            <?php else: ?>
            <img src="" alt="" id="previewLogo" style="display:none">
            <span class="admin-logo-placeholder" id="previewPlaceholder">
                <i class="fa-solid <?= e($fallbackIcon) ?>" aria-hidden="true"></i>
            </span>
            <?php endif; ?>
        </div>
        <label for="logo" class="form-label">Ganti Logo (opsional)</label>
        <input type="file"
            id="logo"
            name="logo"
            class="form-control"
            accept="image/jpeg,image/png,image/webp"
            onchange="previewLogoImage(event)">
    </div>
    <script>
    function previewLogoImage(event) {
        const file = event.target.files && event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function() {
            const img = document.getElementById('previewLogo');
            const placeholder = document.getElementById('previewPlaceholder');
            if (img) {
                img.src = reader.result;
                img.style.display = 'block';
            }
            if (placeholder) {
                placeholder.style.display = 'none';
            }
        };
        reader.readAsDataURL(file);
    }
    </script>
    <?php
}
