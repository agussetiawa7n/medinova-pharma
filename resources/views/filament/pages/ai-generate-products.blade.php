<x-filament-panels::page>
<div class="space-y-6">

    {{-- Steps --}}
    <div class="flex items-center gap-2 p-4 bg-white rounded-xl border">
        @foreach([1=>'Paste Names', 2=>'Generate & Review', 3=>'Save'] as $s => $l)
            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                {{ $currentStep == $s ? 'bg-primary-500 text-white' : ($currentStep > $s ? 'bg-success-500 text-white' : 'bg-gray-200 text-gray-500') }}">
                {{ $currentStep > $s ? '✓' : $s }}
            </span>
            <span class="text-sm font-medium">{{ $l }}</span>
            @if($s < 3) <div class="flex-1 h-px bg-gray-200"></div> @endif
        @endforeach
    </div>

    {{-- STEP 1 --}}
    @if($currentStep === 1)
    <div class="bg-white rounded-xl border p-6 space-y-4">
        <h2 class="text-lg font-semibold">Paste product names (comma or newline separated)</h2>
        <textarea wire:model="rawProductList" rows="6"
            class="w-full rounded-lg border p-3 text-sm font-mono"
            placeholder="Paracetamol 500mg&#10;Ibuprofen 400mg&#10;Omeprazole 20mg"></textarea>
        <div class="flex gap-2">
            <x-filament::button wire:click="parseInput">Parse List</x-filament::button>
            <span class="text-sm text-gray-500 self-center">{{ count($parsedNames) }} products</span>
        </div>
        @if(count($parsedNames))
        <div class="flex flex-wrap gap-2 p-3 border rounded-lg bg-gray-50">
            @foreach($parsedNames as $i => $n)
            <span class="inline-flex items-center gap-1 bg-primary-50 text-primary-700 text-sm px-3 py-1 rounded-full border border-primary-200">
                {{ $n }} <button wire:click="removeFromParsed({{ $i }})" class="text-primary-400 hover:text-primary-600">×</button>
            </span>
            @endforeach
        </div>
        <x-filament::button wire:click="generateAll" color="primary" size="lg" class="w-full">
            ✨ Generate All {{ count($parsedNames) }} Products
        </x-filament::button>
        @endif
    </div>
    @endif

    {{-- STEP 2 --}}
    @if($currentStep === 2)
    @if($isGenerating)
    <div class="bg-white rounded-xl border p-4" wire:poll.2s="pollStatus">
        <div class="flex justify-between text-sm mb-2"><span>Generating...</span><span>{{ $completedCount }}/{{ $totalCount }}</span></div>
        <div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-primary-500 h-2 rounded-full transition-all" style="width:{{ $this->getProgressPercent() }}%"></div></div>
    </div>
    @endif

    @foreach($queueItems as $item)
    <div class="bg-white rounded-xl border overflow-hidden">
        <div class="flex justify-between items-center px-4 py-2 border-b bg-gray-50">
            <div class="flex items-center gap-2">
                <span class="font-semibold">{{ $item['product_name'] }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $item['status']==='completed'?'bg-green-100 text-green-700':($item['status']==='generating'?'bg-amber-100 text-amber-700':($item['status']==='failed'?'bg-red-100 text-red-700':'bg-gray-100 text-gray-500')) }}">{{ ucfirst($item['status']) }}</span>
            </div>
            @if($item['status']==='completed')
            <div class="flex gap-1">
                <button wire:click="regenerateText({{ $item['id'] }})" class="text-xs px-2 py-1 rounded bg-gray-50 hover:bg-gray-100">🔄 Text</button>
                <button wire:click="regenerateImage({{ $item['id'] }})" class="text-xs px-2 py-1 rounded bg-gray-50 hover:bg-gray-100">🖼 Image</button>
                <button wire:click="skipProduct({{ $item['id'] }})" class="text-xs px-2 py-1 rounded bg-gray-50 hover:bg-red-50">Skip</button>
                <button wire:click="approveProduct({{ $item['id'] }})" class="text-xs px-3 py-1 rounded font-medium {{ $item['approved_by']?'bg-green-500 text-white':'bg-gray-100 hover:bg-green-100' }}">{{ $item['approved_by']?'✓ Approved':'Approve' }}</button>
            </div>
            @endif
        </div>
        @if($item['status']==='generating'||$item['status']==='pending')
        <div class="p-8 text-center text-gray-400">⏳ Generating with AI...</div>
        @elseif($item['status']==='failed')
        <div class="p-4 bg-red-50 text-red-700 text-sm">{{ $item['error_message'] }}</div>
        @elseif($item['status']==='completed')
        @php $d = $item['generated_data'] ?? []; @endphp
        <div class="p-4 grid grid-cols-4 gap-4">
            <div>
                @if($item['image_path'])
                <img src="{{ \Illuminate\Support\Facades\Storage::url($item['image_path']) }}" class="w-full aspect-square object-contain rounded-lg border bg-white">
                @else
                <div class="w-full aspect-square bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-xs">No image</div>
                @endif
            </div>
            <div class="col-span-3 grid grid-cols-2 gap-2 text-sm">
                <div><span class="text-xs text-gray-400">Price</span><div class="font-semibold">₹{{ number_format($d['price']??0,2) }} @if(!empty($d['compare_price']))<span class="text-xs text-gray-400 line-through">₹{{ number_format($d['compare_price'],2) }}</span>@endif</div></div>
                <div><span class="text-xs text-gray-400">Category</span><div>{{ $d['category']??'—' }}</div></div>
                <div><span class="text-xs text-gray-400">SKU</span><div class="font-mono text-xs">{{ $d['sku']??'—' }}</div></div>
                <div><span class="text-xs text-gray-400">Unit</span><div>{{ $d['unit']??'—' }}</div></div>
                <div class="col-span-2"><span class="text-xs text-gray-400">Composition</span><div>{{ $d['composition']??'—' }}</div></div>
                <div class="col-span-2"><span class="text-xs text-gray-400">Short description</span><div class="text-xs text-gray-600">{{ $d['short_description']??'—' }}</div></div>
                @if(!empty($d['tags']))
                <div class="col-span-2"><span class="text-xs text-gray-400">Tags</span><div class="flex flex-wrap gap-1">@foreach($d['tags'] as $t)<span class="text-xs bg-gray-100 px-2 py-0.5 rounded-full">{{ $t }}</span>@endforeach</div></div>
                @endif
                <div><span class="text-xs text-gray-400">Rx Required</span><div class="text-xs px-2 py-0.5 rounded-full {{ ($d['requires_prescription']??false)?'bg-amber-100 text-amber-700':'bg-green-100 text-green-700' }}">{{ ($d['requires_prescription']??false)?'Yes':'No' }}</div></div>
                <div><span class="text-xs text-gray-400">Storage</span><div class="text-xs text-gray-600">{{ $d['storage_conditions']??'—' }}</div></div>
            </div>
        </div>
        @endif
    </div>
    @endforeach
    @endif

    {{-- STEP 3 --}}
    @if($currentStep === 2 && !$isGenerating && count($queueItems))
    @php $ac = collect($queueItems)->filter(fn($i)=>$i['approved_by'])->count(); @endphp
    <div class="bg-white rounded-xl border p-6 text-center">
        <h2 class="text-lg font-semibold mb-2">{{ $ac }} products approved</h2>
        <x-filament::button wire:click="saveApproved" color="success" size="lg">💾 Save {{ $ac }} Products to Database</x-filament::button>
    </div>
    @endif

</div>
</x-filament-panels::page>
