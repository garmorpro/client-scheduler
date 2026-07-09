<?php
// Deterministic "avatar" color/initials for any display name, used for
// client/engagement tiles across the admin pages. Not brand-tied on purpose —
// each name should read as visually distinct at a glance in a dense table.
$GLOBALS['AVATAR_PALETTE'] = ['#4f8ef7', '#9b6bd6', '#4fbf9f', '#e0994c', '#5fb85f', '#5aa8d6', '#d67aa8', '#7a8fd6'];

function avatar_color($name) {
    $palette = $GLOBALS['AVATAR_PALETTE'];
    $hash = crc32($name);
    return $palette[$hash % count($palette)];
}

function avatar_initials($name) {
    $words = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        if ($w !== '') $initials .= strtoupper($w[0]);
    }
    return $initials !== '' ? $initials : '?';
}
