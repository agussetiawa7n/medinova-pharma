<?php

namespace App\Filament\Pages;

use App\Services\BackupRestoreService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;

class BackupRestore extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';
    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 10;
    protected static ?string $title = 'Backup & Restore';
    protected static ?string $navigationLabel = 'Backup & Restore';

    protected string $view = 'filament.pages.backup-restore';

    // File upload properties
    public $backupFile = null;
    
    // Status properties
    public ?array $importStats = null;
    public ?string $errorMessage = null;
    public bool $isProcessing = false;

    /**
     * Trigger the download of a full Backup ZIP containing JSON data and image assets.
     */
    public function downloadBackup()
    {
        $this->errorMessage = null;
        $this->importStats = null;

        try {
            $service = new BackupRestoreService();
            $zipPath = $service->export();

            Notification::make()
                ->title('Backup generated successfully!')
                ->success()
                ->send();

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error("Backup generation failed: " . $e->getMessage());
            
            Notification::make()
                ->title('Backup failed')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->errorMessage = "Error during export: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Process the uploaded ZIP file to restore products, categories, and brands.
     */
    public function restoreBackup()
    {
        $this->errorMessage = null;
        $this->importStats = null;

        if (!$this->backupFile) {
            Notification::make()
                ->title('No file selected')
                ->body('Please upload a valid backup .zip file first.')
                ->warning()
                ->send();
            return;
        }

        $this->isProcessing = true;

        try {
            // Get temporary file path from Livewire upload
            $filePath = $this->backupFile->getRealPath();

            $service = new BackupRestoreService();
            $stats = $service->import($filePath);

            $this->importStats = $stats;
            $this->backupFile = null; // Clear upload

            Notification::make()
                ->title('Backup restored successfully!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error("Backup restore failed: " . $e->getMessage());
            
            Notification::make()
                ->title('Restore failed')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->errorMessage = "Error during import: " . $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }
}
