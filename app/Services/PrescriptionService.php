<?php

namespace App\Services;

use App\Enums\PrescriptionStatus;
use App\Models\Prescription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

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

    public function review(Prescription $prescription, PrescriptionStatus $status, ?string $adminNotes = null): void
    {
        $prescription->update([
            'status'      => $status,
            'admin_notes' => $adminNotes,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);
    }

    public function approve(Prescription $prescription, string $adminNotes = ''): void
    {
        $this->review($prescription, PrescriptionStatus::Approved, $adminNotes);
    }

    public function reject(Prescription $prescription, string $adminNotes = ''): void
    {
        $this->review($prescription, PrescriptionStatus::Rejected, $adminNotes);
    }
}
