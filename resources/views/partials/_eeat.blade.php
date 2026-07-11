{{-- EEAT trust block: reviewer byline + last-updated + sources note.
     Expects: $updated (Carbon|null) --}}
<div class="mn-eeat mt-4 pt-3 border-top">
    <div class="d-flex flex-wrap align-items-center gap-3" style="font-size:13px; color:#6B7280;">
        <span>
            <i class="fa-solid fa-user-doctor me-1" style="color:#16a34a;"></i>
            Reviewed by <strong style="color:#303030;">Medinova Medical Team</strong>
        </span>
        @if(!empty($updated))
            <span><i class="fa-regular fa-clock me-1"></i> Last updated: {{ $updated->format('d M Y') }}</span>
        @endif
    </div>
    <p class="mt-2 mb-0" style="font-size:12px; color:#9CA3AF; line-height:1.6;">
        <i class="fa-solid fa-book-medical me-1"></i>
        Information is compiled from standard pharmacology references and reviewed for general accuracy.
        It is intended for educational purposes only and is not a substitute for professional medical advice.
    </p>
</div>
