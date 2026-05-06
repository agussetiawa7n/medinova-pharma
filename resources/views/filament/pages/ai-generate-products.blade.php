<x-filament-panels::page>
<div class="space-y-6" x-data="{}">

    {{-- ═══ STEP INDICATOR ═══ --}}
    <div class="flex items-center gap-0 p-5 bg-white rounded-xl border border-gray-200">
        @foreach([1 => 'Paste Names', 2 => 'Review & Approve', 3 => 'Save'] as $s => $label)
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0
                    {{ $currentStep == $s ? 'text-white shadow-lg' : ($currentStep > $s ? 'text-white' : 'text-gray-400 bg-gray-100') }}"
                    style="{{ $currentStep == $s ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6)' : ($currentStep > $s ? 'background:#10b981' : '') }}">
                    {{ $currentStep > $s ? '✓' : $s }}
                </div>
                <div>
                    <div class="text-sm font-semibold {{ $currentStep >= $s ? 'text-gray-800' : 'text-gray-400' }}">{{ $label }}</div>
                    <div class="text-xs {{ $currentStep >= $s ? 'text-gray-500' : 'text-gray-300' }}">
                        {{ $s == 1 ? 'Paste or upload product names' : ($s == 2 ? 'Approve, edit or skip each product' : 'Save to database') }}
                    </div>
                </div>
            </div>
            @if($s < 3)
                <div class="flex-1 mx-4 h-0.5 rounded {{ $currentStep > $s ? 'bg-green-400' : 'bg-gray-200' }}"></div>
            @endif
        @endforeach
    </div>

    {{-- ═══ STEP 1: INPUT ═══ --}}
    @if($currentStep === 1)
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <div>
            <h2 class="text-base font-semibold text-gray-800 mb-1">Paste product names</h2>
            <p class="text-sm text-gray-500">One per line or comma-separated. Numbers and formatting are auto-cleaned.</p>
        </div>

        <textarea wire:model="rawProductList" rows="7"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm font-mono"
            placeholder="Paracetamol 500mg&#10;Ibuprofen 400mg&#10;Omeprazole 20mg&#10;Cetirizine 10mg&#10;Amoxicillin 250mg"></textarea>

        <div class="flex items-center gap-4">
            <x-filament::button wire:click="parseInput" color="gray" icon="heroicon-o-magnifying-glass" size="md">
                Parse Names
            </x-filament::button>
            <span class="text-sm text-gray-400">or</span>
            <div class="flex items-center gap-2">
                <input type="file" wire:model="csvFile" accept=".csv,.txt" class="text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                <x-filament::button wire:click="parseInput" color="gray" size="sm">Upload CSV</x-filament::button>
            </div>
        </div>

        @if(count($parsedNames))
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600">{{ count($parsedNames) }} products ready</span>
                <button wire:click="$set('parsedNames', [])" class="text-xs text-gray-400 hover:text-red-500">Clear all</button>
            </div>
            <div class="flex flex-wrap gap-2 p-3 bg-gray-50 rounded-lg border border-gray-100 max-h-48 overflow-y-auto">
                @foreach($parsedNames as $i => $name)
                <span class="inline-flex items-center gap-1.5 bg-white text-gray-700 text-sm px-3 py-1.5 rounded-full border border-gray-200 shadow-sm group hover:border-indigo-300 transition">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 shrink-0"></span>
                    {{ $name }}
                    <button wire:click="removeFromParsed({{ $i }})" class="text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-full w-5 h-5 flex items-center justify-center text-xs transition ml-1">×</button>
                </span>
                @endforeach
            </div>
        </div>

        <x-filament::button wire:click="generateAll" color="primary" size="lg" class="w-full text-base"
            style="background:linear-gradient(135deg,#6366f1,#8b5cf6); border:none; padding:14px;">
            ✨ Generate All {{ count($parsedNames) ?: '' }} Products with AI
        </x-filament::button>
        @endif
    </div>
    @endif

    {{-- ═══ STEP 2: PROGRESS + REVIEW ═══ --}}
    @if($currentStep >= 2)
        {{-- Progress bar --}}
        @if($isGenerating)
        <div class="bg-white rounded-xl border border-gray-200 p-5" wire:poll.2s="pollStatus">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <svg class="animate-spin w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span class="text-sm font-semibold text-gray-700">AI is working...</span>
                </div>
                <span class="text-sm font-mono font-bold {{ $this->getProgressPercent() >= 100 ? 'text-green-600' : 'text-indigo-600' }}">
                    {{ $completedCount }} / {{ $totalCount }} &middot; {{ $this->getProgressPercent() }}%
                </span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                <div class="h-3 rounded-full transition-all duration-700 ease-out"
                    style="width:{{ $this->getProgressPercent() }}%; background:linear-gradient(90deg,#6366f1,#8b5cf6);"></div>
            </div>
        </div>
        @endif

        {{-- Review cards --}}
        @foreach($queueItems as $item)
        @php $d = $item['generated_data'] ?? []; $done = $item['status'] === 'completed'; @endphp
        <div class="bg-white rounded-xl border overflow-hidden {{ $done && $item['approved_by'] ? 'border-green-300 ring-1 ring-green-200' : ($item['status'] === 'failed' ? 'border-red-200' : 'border-gray-200') }}">

            {{-- Card header --}}
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 border-b {{ $done && $item['approved_by'] ? 'bg-green-50/50' : 'bg-gray-50/50' }}">
                <div class="flex items-center gap-3">
                    <span class="font-semibold text-gray-800">{{ $item['product_name'] }}</span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full font-medium
                        {{ match($item['status']){'completed'=>'bg-green-100 text-green-700','generating'=>'bg-amber-100 text-amber-700','failed'=>'bg-red-100 text-red-700','skipped'=>'bg-gray-100 text-gray-500',default=>'bg-blue-100 text-blue-600'} }}">
                        {{ $item['status'] === 'generating' ? '⏳ Working...' : ucfirst($item['status']) }}
                    </span>
                    @if($done) <span class="text-xs text-gray-400">{{ $item['text_model_used'] ?? '' }} &middot; {{ $item['image_model_used'] ?? '' }}</span> @endif
                </div>
                <div class="flex items-center gap-1.5">
                    @if($done)
                    <button wire:click="regenerateText({{ $item['id'] }})" title="Regenerate text only" class="text-xs px-2.5 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition">🔄 Text</button>
                    <button wire:click="regenerateImage({{ $item['id'] }})" title="Regenerate image only" class="text-xs px-2.5 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition">🖼 Image</button>
                    <button wire:click="skipProduct({{ $item['id'] }})" class="text-xs px-2.5 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition">Skip</button>
                    <button wire:click="approveProduct({{ $item['id'] }})" class="text-xs px-4 py-1.5 rounded-lg font-semibold transition
                        {{ $item['approved_by'] ? 'bg-green-500 text-white shadow-sm' : 'bg-white border-2 border-green-400 text-green-600 hover:bg-green-50' }}">
                        {{ $item['approved_by'] ? '✓ Approved' : 'Approve' }}
                    </button>
                    @endif
                </div>
            </div>

            {{-- Card body --}}
            @if(in_array($item['status'], ['generating','pending']))
            <div class="p-10 flex flex-col items-center justify-center text-gray-400 gap-3">
                <svg class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span class="text-sm">Generating product details & image with AI...</span>
            </div>
            @elseif($item['status'] === 'failed')
            <div class="p-6 bg-red-50/50 text-sm text-red-700 border-b border-red-100">
                <strong>Error:</strong> {{ $item['error_message'] ?? 'Unknown error' }}
            </div>
            @elseif($done)
            <div class="p-5 grid grid-cols-4 gap-5">

                {{-- Image column --}}
                <div class="col-span-1 space-y-2">
                    @if($item['image_path'])
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($item['image_path']) }}"
                         class="w-full aspect-square object-contain rounded-lg border bg-white" alt="product image">
                    @else
                    <div class="w-full aspect-square bg-gray-50 rounded-lg border flex items-center justify-center text-gray-300 text-xs text-center p-3">
                        No image generated
                    </div>
                    @endif
                </div>

                {{-- Fields grid --}}
                <div class="col-span-3 grid grid-cols-3 gap-3 text-sm">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <span class="text-xs text-gray-400 uppercase tracking-wider font-medium">Price</span>
                        <div class="font-bold text-lg text-gray-800 mt-0.5">₹{{ number_format($d['price']??0, 2) }}</div>
                        @if(!empty($d['compare_price']))
                        <div class="text-xs text-gray-400 line-through">₹{{ number_format($d['compare_price'], 2) }}</div>
                        @endif
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <span class="text-xs text-gray-400 uppercase tracking-wider font-medium">Category</span>
                        <div class="font-medium text-gray-700 mt-0.5">{{ $d['category'] ?? '—' }}</div>
                        @if(!empty($d['brand']))<div class="text-xs text-gray-400">{{ $d['brand'] }}</div>@endif
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <span class="text-xs text-gray-400 uppercase tracking-wider font-medium">SKU & Unit</span>
                        <div class="font-mono text-xs text-gray-700 mt-0.5">{{ $d['sku'] ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $d['unit'] ?? '—' }}{{ !empty($d['weight'])?' &middot; '.$d['weight'].'g':'' }}</div>
                    </div>
                    <div class="col-span-3 bg-gray-50 rounded-lg p-3">
                        <span class="text-xs text-gray-400 uppercase tracking-wider font-medium">Composition</span>
                        <div class="font-medium text-gray-700 mt-0.5">{{ $d['composition'] ?? '—' }} &middot; <span class="text-gray-500">Manufacturer: {{ $d['manufacturer'] ?? '—' }}</span></div>
                    </div>
                    <div class="col-span-3 bg-gray-50 rounded-lg p-3">
                        <span class="text-xs text-gray-400 uppercase tracking-wider font-medium">Short Description</span>
                        <div class="text-gray-600 text-xs leading-relaxed mt-0.5">{{ $d['short_description'] ?? '—' }}</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 flex items-center gap-3">
                        <span class="text-xs text-gray-400 uppercase tracking-wider font-medium">Rx</span>
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ ($d['requires_prescription']??false) ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">
                            {{ ($d['requires_prescription']??false) ? 'Required' : 'OTC' }}
                        </span>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <span class="text-xs text-gray-400 uppercase tracking-wider font-medium">Storage</span>
                        <div class="text-xs text-gray-600 mt-0.5">{{ $d['storage_conditions'] ?? '—' }}</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <span class="text-xs text-gray-400 uppercase tracking-wider font-medium">Tags</span>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @forelse($d['tags']??[] as $t)
                            <span class="text-xs bg-white border px-2 py-0.5 rounded-full text-gray-600">{{ $t }}</span>
                            @empty
                            <span class="text-xs text-gray-400">—</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endforeach
    @endif

    {{-- ═══ SAVE BAR ═══ --}}
    @if(!$isGenerating && count($queueItems) > 0)
    @php
        $approved = collect($queueItems)->filter(fn($i)=>!empty($i['approved_by']))->count();
        $skipped  = collect($queueItems)->filter(fn($i)=>$i['status']==='skipped')->count();
        $failed   = collect($queueItems)->filter(fn($i)=>$i['status']==='failed')->count();
    @endphp
    <div class="sticky bottom-0 bg-white rounded-xl border border-gray-200 p-5 shadow-lg flex items-center justify-between gap-6">
        <div class="flex items-center gap-6">
            <div class="text-center"><div class="text-xl font-bold text-green-600">{{ $approved }}</div><div class="text-xs text-gray-500">Approved</div></div>
            <div class="text-gray-300">|</div>
            <div class="text-center"><div class="text-xl font-bold text-gray-400">{{ $skipped }}</div><div class="text-xs text-gray-500">Skipped</div></div>
            <div class="text-gray-300">|</div>
            <div class="text-center"><div class="text-xl font-bold {{ $failed > 0 ? 'text-red-500' : 'text-gray-400' }}">{{ $failed }}</div><div class="text-xs text-gray-500">Failed</div></div>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="resetForm" class="text-sm text-gray-500 hover:text-gray-700">Start New Batch</button>
            <x-filament::button wire:click="saveApproved" color="success" size="lg" :disabled="$approved === 0" class="text-base font-semibold">
                💾 Save {{ $approved }} Approved Products
            </x-filament::button>
        </div>
    </div>
    @endif

</div>
</x-filament-panels::page>
