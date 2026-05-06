<x-filament-panels::page>
<div class="space-y-8">

    {{-- ═══════ HEADER ═══════ --}}
    <div class="relative overflow-hidden rounded-2xl p-8 text-white" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 30%, #4338ca 60%, #6366f1 100%);">
        <div class="absolute top-0 right-0 w-64 h-64 opacity-10" style="background: radial-gradient(circle, #a5b4fc, transparent 70%); border-radius: 50%;"></div>
        <div class="absolute bottom-0 left-1/2 w-96 h-48 opacity-5" style="background: radial-gradient(ellipse, #818cf8, transparent 70%); border-radius: 50%;"></div>
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(8px);">✨</div>
                    <h1 class="text-2xl font-bold tracking-tight">AI Product Generator</h1>
                </div>
                <p class="text-indigo-200 text-sm max-w-lg">Paste product names, upload a file, or type them in — AI handles the rest. Description, pricing, categories, SKUs, and product images all generated automatically.</p>
            </div>
            <div class="hidden lg:flex items-center gap-1 text-xs font-mono text-indigo-300/70">
                <span class="px-2 py-1 rounded-md bg-white/10">GPT-5 Mini</span>
                <span>+</span>
                <span class="px-2 py-1 rounded-md bg-white/10">GPT-5 Image</span>
                <span class="mx-1">via</span>
                <span class="px-2 py-1 rounded-md bg-white/10">OpenRouter</span>
            </div>
        </div>
    </div>

    {{-- ═══════ STEP INDICATOR ═══════ --}}
    <div class="flex items-center gap-0 px-2">
        @foreach([1 => ['name' => 'Input', 'icon' => '📋', 'desc' => 'Paste or upload'], 2 => ['name' => 'Review', 'icon' => '👁', 'desc' => 'Approve & edit'], 3 => ['name' => 'Save', 'icon' => '💾', 'desc' => 'To database']] as $s => $step)
            <div class="flex items-center gap-3 {{ $s > $currentStep ? 'opacity-40' : '' }}">
                <div class="relative">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-lg font-bold shadow-lg transition-all duration-500 {{ $currentStep == $s ? 'scale-110' : '' }}"
                        style="{{ $currentStep > $s ? 'background:#10b981; color:#fff;' : ($currentStep == $s ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff;' : 'background:#eef2ff; color:#6366f1;') }}">
                        {{ $currentStep > $s ? '✓' : $step['icon'] }}
                    </div>
                    @if($currentStep == $s)<div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-indigo-400 rounded-full animate-pulse"></div>@endif
                </div>
                <div>
                    <div class="text-sm font-semibold {{ $currentStep >= $s ? 'text-gray-800' : 'text-gray-400' }}">{{ $step['name'] }}</div>
                    <div class="text-xs text-gray-400">{{ $step['desc'] }}</div>
                </div>
            </div>
            @if($s < 3)
                <div class="flex-1 mx-3 h-0.5 rounded-full transition-all duration-700 {{ $currentStep > $s ? 'bg-green-400' : ($currentStep == $s ? 'bg-indigo-200' : 'bg-gray-100') }}"></div>
            @endif
        @endforeach
    </div>

    {{-- ═══════ STEP 1: INPUT ═══════ --}}
    @if($currentStep === 1)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-1" style="background:linear-gradient(90deg,#6366f1,#a78bfa,#6366f1);"></div>
        <div class="p-8 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Textarea --}}
                <div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-3">
                        <span class="w-6 h-6 rounded-lg bg-indigo-50 flex items-center justify-center text-xs">✏️</span>
                        Paste product names
                    </label>
                    <textarea wire:model="rawProductList" rows="8"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 text-sm font-mono resize-none transition"
                        placeholder="Paracetamol 500mg&#10;Ibuprofen 400mg&#10;Omeprazole 20mg&#10;Cetirizine 10mg&#10;Amoxicillin 250mg"></textarea>
                </div>
                {{-- File uploads --}}
                <div class="space-y-4">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <span class="w-6 h-6 rounded-lg bg-indigo-50 flex items-center justify-center text-xs">📁</span>
                        Or upload a file
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-gray-200 hover:border-green-300 hover:bg-green-50/30 cursor-pointer transition group">
                            <div class="w-12 h-12 rounded-xl bg-green-50 group-hover:bg-green-100 flex items-center justify-center text-2xl transition">📊</div>
                            <span class="text-xs font-semibold text-gray-600 group-hover:text-green-700">Excel</span>
                            <span class="text-[10px] text-gray-400">.xlsx .xls</span>
                            <input type="file" wire:model="excelFile" accept=".xlsx,.xls" class="hidden">
                        </label>
                        <label class="flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-gray-200 hover:border-blue-300 hover:bg-blue-50/30 cursor-pointer transition group">
                            <div class="w-12 h-12 rounded-xl bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center text-2xl transition">📄</div>
                            <span class="text-xs font-semibold text-gray-600 group-hover:text-blue-700">CSV</span>
                            <span class="text-[10px] text-gray-400">.csv</span>
                            <input type="file" wire:model="csvFile" accept=".csv" class="hidden">
                        </label>
                        <label class="flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-gray-200 hover:border-amber-300 hover:bg-amber-50/30 cursor-pointer transition group">
                            <div class="w-12 h-12 rounded-xl bg-amber-50 group-hover:bg-amber-100 flex items-center justify-center text-2xl transition">📝</div>
                            <span class="text-xs font-semibold text-gray-600 group-hover:text-amber-700">Notepad</span>
                            <span class="text-[10px] text-gray-400">.txt</span>
                            <input type="file" wire:model="txtFile" accept=".txt" class="hidden">
                        </label>
                    </div>
                    <div class="flex items-center gap-2 p-3 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="text-xs text-gray-500">Selected:</span>
                        <span class="text-xs font-mono {{ $csvFile ? 'text-blue-600' : 'text-gray-300' }}">CSV: {{ $csvFile ? $csvFile->getClientOriginalName() : 'none' }}</span>
                        <span class="text-xs font-mono {{ $txtFile ? 'text-amber-600' : 'text-gray-300' }}">TXT: {{ $txtFile ? $txtFile->getClientOriginalName() : 'none' }}</span>
                        <span class="text-xs font-mono {{ $excelFile ? 'text-green-600' : 'text-gray-300' }}">Excel: {{ $excelFile ? $excelFile->getClientOriginalName() : 'none' }}</span>
                    </div>
                </div>
            </div>

            {{-- Parse button --}}
            <div class="flex items-center gap-4">
                <x-filament::button wire:click="parseInput" size="lg" class="text-base font-semibold tracking-wide"
                    style="background:linear-gradient(135deg,#6366f1,#8b5cf6); border:none; box-shadow:0 4px 14px rgba(99,102,241,0.35);">
                    🔍 Parse Product Names
                </x-filament::button>
                <span class="text-sm text-gray-400">Auto-detects formatting, removes numbers and special chars</span>
            </div>

            {{-- Parsed names --}}
            @if(count($parsedNames))
            <div class="rounded-xl border border-indigo-100 bg-indigo-50/30 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold text-indigo-700 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-indigo-100 flex items-center justify-center text-xs">{{ count($parsedNames) }}</span>
                        products detected
                    </span>
                    <button wire:click="$set('parsedNames', [])" class="text-xs text-indigo-400 hover:text-red-500 underline-offset-2 hover:underline transition">Clear all</button>
                </div>
                <div class="flex flex-wrap gap-1.5 max-h-40 overflow-y-auto">
                    @foreach($parsedNames as $i => $name)
                    <span class="inline-flex items-center gap-1.5 bg-white text-gray-700 text-xs px-3 py-1.5 rounded-lg border border-indigo-100 shadow-sm hover:shadow-md hover:border-indigo-300 transition cursor-default group">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 shrink-0"></span>
                        {{ $name }}
                        <button wire:click="removeFromParsed({{ $i }})" class="text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-full w-4 h-4 inline-flex items-center justify-center text-[10px] transition opacity-0 group-hover:opacity-100">×</button>
                    </span>
                    @endforeach
                </div>
            </div>

            <x-filament::button wire:click="generateAll" size="lg" class="w-full text-base font-bold tracking-wide"
                style="background:linear-gradient(135deg,#4f46e5,#7c3aed); border:none; padding:16px; box-shadow:0 8px 24px rgba(79,70,229,0.3);">
                ✨ Generate All {{ count($parsedNames) ?: '' }} Products with AI
            </x-filament::button>
            @endif
        </div>
    </div>
    @endif

    {{-- ═══════ STEP 2: PROGRESS + REVIEW ═══════ --}}
    @if($currentStep >= 2)
        {{-- Progress --}}
        @if($isGenerating)
        <div class="relative overflow-hidden rounded-2xl p-6 text-white" style="background:linear-gradient(135deg,#1e1b4b,#312e81);">
            <div class="absolute top-0 right-0 w-32 h-32 opacity-10" style="background:radial-gradient(circle,#a5b4fc,transparent 70%);border-radius:50%;"></div>
            <div class="relative z-10 flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center bg-white/10 backdrop-blur">
                    <svg class="animate-spin w-6 h-6 text-indigo-300" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </div>
                <div class="flex-1">
                    <div class="flex justify-between mb-2"><span class="font-bold">AI Generating Products</span><span class="font-mono text-indigo-200">{{ $completedCount }}/{{ $totalCount }} &middot; {{ $this->getProgressPercent() }}%</span></div>
                    <div class="w-full h-2.5 rounded-full bg-white/10 overflow-hidden"><div class="h-full rounded-full transition-all duration-700 ease-out" style="width:{{ $this->getProgressPercent() }}%;background:linear-gradient(90deg,#818cf8,#c084fc);box-shadow:0 0 12px rgba(129,140,248,0.5);" wire:poll.2s="pollStatus"></div></div>
                </div>
            </div>
        </div>
        @endif

        {{-- Review cards --}}
        @foreach($queueItems as $item)
        @php $d = $item['generated_data'] ?? []; $done = $item['status'] === 'completed'; $approved = !empty($item['approved_by']); @endphp
        <div class="relative overflow-hidden rounded-2xl border bg-white shadow-sm transition-all duration-300
            {{ $approved ? 'border-green-300 ring-1 ring-green-200' : ($done ? 'border-indigo-100 hover:shadow-md' : ($item['status']==='failed'?'border-red-200':'border-gray-100')) }}">

            {{-- Top accent strip --}}
            <div class="h-1" style="background:linear-gradient(90deg,
                {{ $approved ? '#10b981, #34d399' : ($done ? '#6366f1, #a78bfa' : ($item['status']==='failed'?'#ef4444,#f87171':'#e2e8f0,#cbd5e1')) }});"></div>

            {{-- Header --}}
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm
                        {{ $approved ? 'bg-green-50 text-green-600' : ($done ? 'bg-indigo-50 text-indigo-600' : 'bg-gray-50 text-gray-500') }}">
                        {{ $item['product_name'] ? strtoupper(substr($item['product_name'], 0, 2)) : '?' }}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-800">{{ $item['product_name'] }}</div>
                        <div class="flex items-center gap-2 text-xs text-gray-400">
                            @if($done)
                                <span>{{ $item['text_model_used'] ?? 'AI' }}</span>
                                <span>&middot;</span>
                                <span>{{ $item['image_model_used'] ?? 'Image AI' }}</span>
                            @endif
                        </div>
                    </div>
                    <span class="text-xs px-2.5 py-0.5 rounded-full font-medium {{ match($item['status']){'completed'=>'bg-green-50 text-green-600 ring-1 ring-green-200','generating'=>'bg-amber-50 text-amber-600 ring-1 ring-amber-200','failed'=>'bg-red-50 text-red-600 ring-1 ring-red-200','skipped'=>'bg-gray-50 text-gray-400',default=>'bg-blue-50 text-blue-600'} }}">
                        {{ $item['status'] === 'generating' ? '⏳ Working' : ucfirst($item['status']) }}
                    </span>
                </div>

                @if($done)
                <div class="flex items-center gap-1.5">
                    <button wire:click="regenerateText({{ $item['id'] }})" class="text-xs px-3 py-2 rounded-xl bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition flex items-center gap-1.5" title="Regenerate product details">
                        <span>🔄</span> Text
                    </button>
                    <button wire:click="regenerateImage({{ $item['id'] }})" class="text-xs px-3 py-2 rounded-xl bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition flex items-center gap-1.5" title="Regenerate image only">
                        <span>🖼</span> Image
                    </button>
                    <button wire:click="skipProduct({{ $item['id'] }})" class="text-xs px-3 py-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition">Skip</button>
                    <button wire:click="approveProduct({{ $item['id'] }})" class="text-xs px-5 py-2 rounded-xl font-bold transition flex items-center gap-1.5
                        {{ $approved ? 'bg-green-500 text-white shadow-sm shadow-green-200' : 'bg-white border-2 border-green-400 text-green-600 hover:bg-green-50 hover:shadow-sm' }}">
                        {{ $approved ? '✓ Approved' : '✓ Approve' }}
                    </button>
                </div>
                @endif
            </div>

            {{-- Body --}}
            @if(in_array($item['status'], ['generating','pending']))
            <div class="px-6 py-12 flex flex-col items-center justify-center text-gray-400 gap-3">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center">
                    <svg class="animate-spin w-7 h-7 text-indigo-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </div>
                <div class="text-sm font-medium">AI is crafting product details & generating image...</div>
                <div class="text-xs text-gray-300">Usually takes 5-15 seconds</div>
            </div>
            @elseif($item['status'] === 'failed')
            <div class="mx-6 mb-6 p-4 bg-red-50 rounded-xl text-sm text-red-600 border border-red-100 flex items-start gap-3">
                <span class="text-lg shrink-0">⚠️</span>
                <div><strong>Generation failed</strong><br><span class="text-red-400 text-xs">{{ $item['error_message'] ?? 'Unknown error' }}</span></div>
            </div>
            @elseif($done)
            <div class="p-6 grid grid-cols-5 gap-5 border-t border-gray-50">
                {{-- Image --}}
                <div class="col-span-1 space-y-2">
                    @if($item['image_path'])
                    <div class="relative group">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($item['image_path']) }}" class="w-full aspect-square object-contain rounded-xl border bg-white shadow-sm" alt="product">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 rounded-xl transition flex items-center justify-center opacity-0 group-hover:opacity-100">
                            <span class="text-[10px] bg-black/60 text-white px-2 py-0.5 rounded-full">Click to view</span>
                        </div>
                    </div>
                    @else
                    <div class="w-full aspect-square bg-gray-50 rounded-xl border flex items-center justify-center text-gray-300 text-xs p-3 text-center">No image</div>
                    @endif
                </div>

                {{-- Details grid --}}
                <div class="col-span-4 grid grid-cols-3 gap-3">
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-100">
                        <div class="text-[10px] text-green-500 uppercase tracking-widest font-bold mb-1">Price</div>
                        <div class="text-xl font-black text-green-700">₹{{ number_format($d['price']??0, 2) }}</div>
                        @if(!empty($d['compare_price']))<div class="text-xs text-green-400 line-through mt-0.5">MRP ₹{{ number_format($d['compare_price'], 2) }}</div>@endif
                    </div>
                    <div class="bg-gradient-to-br from-blue-50 to-sky-50 rounded-xl p-4 border border-blue-100">
                        <div class="text-[10px] text-blue-500 uppercase tracking-widest font-bold mb-1">Category & Brand</div>
                        <div class="font-semibold text-blue-900">{{ $d['category'] ?? '—' }}</div>
                        <div class="text-xs text-blue-500 mt-0.5">{{ $d['brand'] ?? '—' }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-violet-50 rounded-xl p-4 border border-purple-100">
                        <div class="text-[10px] text-purple-500 uppercase tracking-widest font-bold mb-1">SKU & Unit</div>
                        <div class="font-mono text-sm font-bold text-purple-900">{{ $d['sku'] ?? '—' }}</div>
                        <div class="text-xs text-purple-500 mt-0.5">{{ $d['unit'] ?? '—' }}{{ !empty($d['weight'])?' &middot; '.$d['weight'].'g':'' }}</div>
                    </div>
                    <div class="col-span-3 bg-gradient-to-br from-gray-50 to-slate-50 rounded-xl p-4 border border-gray-100">
                        <div class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-1">Composition & Manufacturer</div>
                        <div class="font-medium text-gray-700">{{ $d['composition'] ?? '—' }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">by {{ $d['manufacturer'] ?? '—' }}</div>
                    </div>
                    <div class="col-span-3 bg-gradient-to-br from-gray-50 to-slate-50 rounded-xl p-4 border border-gray-100">
                        <div class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-1">Short Description</div>
                        <div class="text-gray-600 text-sm leading-relaxed">{{ $d['short_description'] ?? '—' }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl p-4 border border-amber-100 flex items-center gap-3">
                        <div>
                            <div class="text-[10px] text-amber-500 uppercase tracking-widest font-bold mb-1">Prescription</div>
                            <span class="text-sm font-bold px-2.5 py-1 rounded-lg {{ ($d['requires_prescription']??false) ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">{{ ($d['requires_prescription']??false) ? 'Rx Required' : 'OTC' }}</span>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-teal-50 to-cyan-50 rounded-xl p-4 border border-teal-100">
                        <div class="text-[10px] text-teal-500 uppercase tracking-widest font-bold mb-1">Storage</div>
                        <div class="text-sm text-teal-900">{{ $d['storage_conditions'] ?? '—' }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-rose-50 to-pink-50 rounded-xl p-4 border border-rose-100">
                        <div class="text-[10px] text-rose-400 uppercase tracking-widest font-bold mb-1">Tags</div>
                        <div class="flex flex-wrap gap-1">
                            @forelse($d['tags']??[] as $t)
                            <span class="text-xs bg-white border border-rose-100 px-2.5 py-0.5 rounded-full text-rose-600 font-medium">{{ $t }}</span>
                            @empty
                            <span class="text-xs text-rose-300">—</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endforeach
    @endif

    {{-- ═══════ SAVE BAR ═══════ --}}
    @if(!$isGenerating && count($queueItems) > 0)
    @php
        $approved = collect($queueItems)->filter(fn($i)=>!empty($i['approved_by']))->count();
        $skipped  = collect($queueItems)->filter(fn($i)=>$i['status']==='skipped')->count();
        $failed   = collect($queueItems)->filter(fn($i)=>$i['status']==='failed')->count();
    @endphp
    <div class="sticky bottom-0 z-50 rounded-2xl p-5 flex items-center justify-between gap-6 shadow-2xl border border-gray-100"
         style="background:rgba(255,255,255,0.95); backdrop-filter:blur(12px);">
        <div class="flex items-center gap-8">
            <div class="text-center"><div class="text-2xl font-black text-green-500">{{ $approved }}</div><div class="text-[10px] text-green-400 uppercase tracking-widest font-bold">Approved</div></div>
            <div class="w-px h-10 bg-gray-200"></div>
            <div class="text-center"><div class="text-2xl font-black text-gray-300">{{ $skipped }}</div><div class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Skipped</div></div>
            <div class="w-px h-10 bg-gray-200"></div>
            <div class="text-center"><div class="text-2xl font-black {{ $failed > 0 ? 'text-red-400' : 'text-gray-300' }}">{{ $failed }}</div><div class="text-[10px] {{ $failed > 0 ? 'text-red-400' : 'text-gray-400' }} uppercase tracking-widest font-bold">Failed</div></div>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="resetForm" class="text-sm text-gray-400 hover:text-gray-600 font-medium transition">↻ New Batch</button>
            <x-filament::button wire:click="saveApproved" color="success" size="lg" :disabled="$approved === 0"
                class="text-base font-black tracking-wide shadow-lg shadow-green-200"
                style="padding:14px 32px; border-radius:14px;">
                💾 Save {{ $approved }} Products
            </x-filament::button>
        </div>
    </div>
    @endif

</div>
</x-filament-panels::page>
