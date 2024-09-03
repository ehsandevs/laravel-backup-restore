<?php

namespace Ehsandevs\LaravelBackupRestore\Actions;

use Ehsandevs\LaravelBackupRestore\PendingRestore;
use Illuminate\Support\Facades\Storage;

class DownloadBackupAction
{
    public function execute(PendingRestore $pendingRestore): void
    {
        consoleOutput()->info('Downloading backup …');

        Storage::disk($pendingRestore->restoreDisk)
            ->writeStream(
                $pendingRestore->getPathToLocalCompressedBackup(),
                Storage::disk($pendingRestore->disk)->readStream($pendingRestore->backup)
            );
    }
}
