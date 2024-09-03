<?php

namespace Ehsandevs\LaravelBackupRestore\Actions;

use Ehsandevs\LaravelBackupRestore\PendingRestore;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Ehsandevs\LaravelBackupRestore\Exceptions\DecompressionFailed;

class DecompressBackupAction
{
    /**
     * @throws DecompressionFailed
     */
    public function execute(PendingRestore $pendingRestore): void
    {
        $extractTo = $pendingRestore->getAbsolutePathToLocalDecompressedBackup();

        $pathToFileToDecompress = Storage::disk($pendingRestore->restoreDisk)
            ->path($pendingRestore->getPathToLocalCompressedBackup());

        consoleOutput()->info('Extracting database dump from backup …');

        $zip = new ZipArchive;
        $result = $zip->open($pathToFileToDecompress);

        if ($result === true) {
            if ($pendingRestore->backupPassword) {
                $zip->setPassword($pendingRestore->backupPassword);
            }

            $extractionResult = $zip->extractTo($extractTo);
            $zip->close();

            if ($extractionResult === false) {
                throw DecompressionFailed::create($extractionResult, $pathToFileToDecompress);
            }
        } else {
            throw DecompressionFailed::create($result, $pathToFileToDecompress);
        }
    }
}
