<?php

namespace Ehsandevs\LaravelBackupRestore\Actions;

use Illuminate\Support\Facades\Storage;
use Ehsandevs\LaravelBackupRestore\PendingRestore;

class CleanupLocalBackupAction
{
    public function execute(PendingRestore $pendingRestore): void
    {
        Storage::disk($pendingRestore->restoreDisk)
            ->delete($pendingRestore->getPathToLocalCompressedBackup());

        Storage::disk($pendingRestore->restoreDisk)
            ->deleteDirectory($pendingRestore->getPathToLocalDecompressedBackup());
    }
}
