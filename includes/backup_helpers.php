<?php
// Shared helpers for resolving/validating the local backup directory.
// The System Settings UI accepts a free-text path, which previously went
// straight into a shell command with no bounds on where it could point -
// this constrains it to a fixed safe root so a misconfigured (or malicious)
// path can't write the DB dump somewhere unexpected on the server.

function backup_safe_root(): string {
    $root = realpath(__DIR__ . '/..') . '/storage/backups';
    if (!is_dir($root)) {
        mkdir($root, 0755, true);
    }
    return $root;
}

// Resolves the configured backup directory against the safe root. If the
// configured path escapes the safe root (or isn't set), falls back to the
// safe root itself rather than trusting an arbitrary filesystem path.
function backup_resolve_dir(?string $configuredDir): string {
    $safeRoot = backup_safe_root();
    $configuredDir = rtrim(trim($configuredDir ?? ''), '/');

    if ($configuredDir === '') {
        return $safeRoot;
    }

    if (!is_dir($configuredDir)) {
        @mkdir($configuredDir, 0755, true);
    }

    $resolved = realpath($configuredDir);
    if ($resolved === false || strpos($resolved, $safeRoot) !== 0) {
        return $safeRoot;
    }

    return $resolved;
}
