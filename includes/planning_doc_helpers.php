<?php
// Shared helpers for the engagement planning-doc upload, following the same
// safe-root pattern as backup_helpers.php: files never go anywhere outside
// a fixed storage directory, and the DB only ever stores a filename this
// module generated itself - never a client-supplied path.

function planning_doc_safe_root(): string {
    $root = realpath(__DIR__ . '/..') . '/storage/planning_docs';
    if (!is_dir($root)) {
        mkdir($root, 0755, true);
    }
    return $root;
}

const PLANNING_DOC_ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg'];
const PLANNING_DOC_MAX_BYTES = 20 * 1024 * 1024; // 20MB
