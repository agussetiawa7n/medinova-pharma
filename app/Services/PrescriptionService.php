<?php

namespace App\Services;

use App\Enums\PrescriptionStatus;
use App\Models\Prescription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PrescriptionService
{
    public function upload(int $userId, UploadedFile $file, ?int $orderId = null, string $notes = ''): Prescription
    {
        $path = $file->store("prescriptions/{$userId}", 'private');

        return Prescription::create([
            'user_id'       => $userId,
            'order_id'      => $orderId,
            'file_path'     => $path,
            'file_name'     => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'status'        => PrescriptionStatus::Pending,
            'patient_notes' => $notes,
        ]);
    }

    public function approve(Prescription $prescription, string $adminNotes = ''): void
    {
        $prescription->update([
            'status'      => PrescriptionStatus::Approved,
            'admin_notes' => $adminNotes,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);
    }

    public function reject(Prescription $prescription, string $adminNotes = ''): void
    {
        $prescription->update([
            'status'      => PrescriptionStatus::Rejected,
            'admin_notes' => $adminNotes,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);
    }

    public function getSecureUrl(Prescription $prescription): string
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('private');
        return $disk->temporaryUrl($prescription->file_path, now()->addMinutes(30));
    }
}
