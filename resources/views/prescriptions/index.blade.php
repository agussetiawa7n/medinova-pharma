@extends('layouts.app')

@section('title', 'My Prescriptions — MediNova Pharma')

@section('content')

<div class="container">
    <div class="cs_height_45 cs_height_lg_45"></div>
    <ol class="breadcrumb cs_fs_18 mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">My Account</a></li>
        <li class="breadcrumb-item active">Prescriptions</li>
    </ol>
    <div class="cs_height_30 cs_height_lg_30"></div>
</div>

<div class="container">
    <div class="cs_account_wrap">

        @include('partials.account-sidebar', ['current' => 'prescriptions'])

        <div class="cs_account_content">

            @if(session('success'))
                <div class="alert alert-success" style="border-radius:8px;">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger" style="border-radius:8px;">
                    @foreach($errors->all() as $err)
                        <div style="font-size:14px;">{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            {{-- Upload form --}}
            <div class="cs_account_card cs_radius_10">
                <div class="cs_account_card_head">
                    <h3 class="cs_fs_18 mb-0">Upload New Prescription</h3>
                    <span class="cs_light" style="font-size:13px;">JPG, PNG, PDF · Max 5 MB</span>
                </div>
                <div class="cs_plr_25" style="padding-top:20px; padding-bottom:24px;">
                    <form action="{{ route('prescriptions.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row cs_gap_y_20">
                            <div class="col-lg-6">
                                <label class="cs_semibold">Prescription File<span>*</span></label>
                                <input type="file" name="image" accept=".jpg,.jpeg,.png,.pdf" required class="cs_form_field" style="padding:10px 16px;">
                            </div>
                            <div class="col-lg-6">
                                <label class="cs_semibold">Notes (optional)</label>
                                <input type="text" name="notes" class="cs_form_field" placeholder="Any specific instructions…" maxlength="500">
                            </div>
                            <div class="col-lg-12 text-end">
                                <button type="submit" class="cs_btn cs_style_1 cs_fs_16 cs_medium">
                                    <span>Upload Prescription</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="cs_height_30 cs_height_lg_30"></div>

            {{-- Prescription list --}}
            <div class="cs_account_card cs_radius_10">
                <div class="cs_account_card_head cs_type_1">
                    <h3 class="cs_fs_18 mb-0">Your Prescriptions</h3>
                    <span class="cs_light" style="font-size:13px;">{{ $prescriptions->total() }} total</span>
                </div>

                <div class="cs_plr_25" style="padding-bottom:20px;">
                    @if($prescriptions->isEmpty())
                        <div class="text-center py-5">
                            <div style="width:96px; height:96px; border-radius:50%; background:#faeff2; display:flex; align-items:center; justify-content:center; color:#e61f7f; font-size:38px; margin:0 auto 18px;">
                                <i class="fa-solid fa-prescription"></i>
                            </div>
                            <h4 class="cs_fs_24 cs_semibold mb-2">No prescriptions yet</h4>
                            <p class="cs_light mb-0" style="font-size:15px;">Upload your first prescription using the form above.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="cs_table_1 m-0">
                                <thead>
                                    <tr>
                                        <th>File</th>
                                        <th>Uploaded</th>
                                        <th>Status</th>
                                        <th>Order</th>
                                        <th>Notes</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($prescriptions as $rx)
                                        @php
                                            $statusColor = match($rx->status->value ?? 'pending') {
                                                'approved'  => 'cs_primary_color',
                                                'rejected'  => 'cs_ternary_color',
                                                default     => 'cs_accent_color',
                                            };
                                            $isPdf = str_ends_with(strtolower($rx->file_path ?? ''), '.pdf');
                                        @endphp
                                        <tr>
                                            <td>
                                                <a href="{{ route('prescriptions.file', $rx->id) }}" target="_blank" class="d-flex align-items-center gap-2" style="text-decoration:none;">
                                                    <span style="width:38px; height:38px; border-radius:8px; background:{{ $isPdf ? '#fee2e2' : '#faeff2' }}; display:inline-flex; align-items:center; justify-content:center; color:{{ $isPdf ? '#dc2626' : '#e61f7f' }}; font-size:16px;">
                                                        <i class="fa-solid fa-{{ $isPdf ? 'file-pdf' : 'image' }}"></i>
                                                    </span>
                                                    <span class="cs_primary_color cs_medium">Rx #{{ $rx->id }}</span>
                                                </a>
                                            </td>
                                            <td>{{ $rx->created_at->format('d/m/Y') }}</td>
                                            <td class="{{ $statusColor }} cs_semibold">{{ $rx->status->label() ?? ucfirst($rx->status ?? 'Pending') }}</td>
                                            <td>
                                                @if($rx->order)
                                                    <a href="{{ route('orders.show', $rx->order) }}" class="cs_accent_color">#{{ $rx->order->order_number }}</a>
                                                @else
                                                    <span class="cs_light">—</span>
                                                @endif
                                            </td>
                                            <td style="max-width:220px;">
                                                @if($rx->admin_notes)
                                                    <small class="cs_primary_color cs_semibold d-block">Admin:</small>
                                                    <small class="cs_light">{{ $rx->admin_notes }}</small>
                                                @elseif($rx->patient_notes)
                                                    <small class="cs_light">{{ $rx->patient_notes }}</small>
                                                @else
                                                    <span class="cs_light">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('prescriptions.file', $rx->id) }}" target="_blank" class="cs_text_btn">
                                                    <span>View</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($prescriptions->hasPages())
                            <div class="cs_table_1_footer mt-3">
                                {{ $prescriptions->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="cs_height_120 cs_height_lg_70"></div>
@endsection
