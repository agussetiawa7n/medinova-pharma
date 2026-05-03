<?php

namespace App\Http\Controllers;

use App\Services\PrescriptionService;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PrescriptionController extends Controller
{
    public function __construct(private PrescriptionService $prescriptionService) {}

    public function index()
    {
        /** @var \App\Models\User $user */
        $user          = Auth::user();
        $prescriptions = $user->prescriptions()
            ->with('order')
            ->latest()
            ->paginate(10);

        return view('prescriptions.index', compact('prescriptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'image'    => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'order_id' => 'nullable|integer|exists:orders,id',
            'notes'    => 'nullable|string|max:500',
        ]);

        $this->prescriptionService->upload(
            Auth::id(),
            $request->file('image'),
            $request->order_id,
            $request->notes
        );

        return back()->with('success', 'Prescription uploaded and pending review.');
    }

    public function file(Prescription $prescription)
    {
        Gate::authorize('owns-prescription', $prescription);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('private');

        if (! $disk->exists($prescription->file_path)) {
            abort(404);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
        $mime = in_array($prescription->mime_type, $allowedMimes, true)
            ? $prescription->mime_type
            : $disk->mimeType($prescription->file_path);

        return $disk->response($prescription->file_path, headers: ['Content-Type' => $mime]);
    }
}
