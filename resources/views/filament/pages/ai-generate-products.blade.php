<x-filament-panels::page>
<div style="padding-bottom:100px;">

    {{-- ═══ HEADER ═══ --}}
    <div style="background:linear-gradient(135deg,#e61f7f 0%,#b81964 50%,#583fa8 100%); border-radius:16px; padding:24px; margin-bottom:24px; position:relative; overflow:hidden; box-shadow:0 4px 20px rgba(230,31,127,0.3);">
        <div style="position:absolute; top:-24px; right:-24px; width:160px; height:160px; border-radius:50%; background:rgba(255,255,255,0.1); pointer-events:none;"></div>
        <div style="position:absolute; bottom:-32px; left:33%; width:224px; height:112px; border-radius:50%; background:rgba(255,255,255,0.1); pointer-events:none;"></div>
        <div style="position:relative; z-index:1;">
            <h1 style="color:#fff; font-size:20px; font-weight:700; margin:0 0 4px 0;">✨ AI Product Generator</h1>
            <p style="color:rgba(255,255,255,0.7); font-size:14px; margin:0;">Paste names or upload Excel / CSV / TXT — AI fills everything else.</p>
        </div>
    </div>

    {{-- ═══ STEPS ═══ --}}
    <div style="display:flex; align-items:center; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:24px;">
        @foreach([1 => 'Paste Names', 2 => 'Review & Approve', 3 => 'Save to Database'] as $s => $label)
            <div style="display:flex; align-items:center; gap:8px; {{ $s > $currentStep ? 'opacity:0.4;' : '' }}">
                <div style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; flex-shrink:0;
                    @if($currentStep > $s) background:#10b981; color:#fff;
                    @elseif($currentStep == $s) background:linear-gradient(135deg,#e61f7f,#b81964); color:#fff;
                    @else background:#f3f4f6; color:#9ca3af; @endif">
                    {{ $currentStep > $s ? '✓' : $s }}
                </div>
                <span style="font-size:14px; font-weight:500; white-space:nowrap; {{ $currentStep >= $s ? 'color:#1f2937;' : 'color:#9ca3af;' }}">{{ $label }}</span>
            </div>
            @if($s < 3)
                <div style="flex:1; margin:0 12px; height:2px; border-radius:2px; min-width:12px; {{ $currentStep > $s ? 'background:#10b981;' : 'background:#e5e7eb;' }}"></div>
            @endif
        @endforeach
    </div>

    {{-- ═══ GENERATION TYPE TABS ═══ --}}
    <div style="display:flex; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:6px; margin-bottom:24px; gap:8px;">
        <button wire:click="$set('generationType', 'product')" type="button"
            style="flex:1; padding:10px 16px; font-size:14px; font-weight:700; border:none; border-radius:8px; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; justify-content:center; gap:8px;
            {{ $generationType === 'product' ? 'background:linear-gradient(135deg,#e61f7f,#b81964); color:#fff; box-shadow:0 2px 8px rgba(230,31,127,0.2);' : 'background:transparent; color:#6b7280;' }}">
            📦 Generate Products
        </button>
        <button wire:click="$set('generationType', 'category')" type="button"
            style="flex:1; padding:10px 16px; font-size:14px; font-weight:700; border:none; border-radius:8px; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; justify-content:center; gap:8px;
            {{ $generationType === 'category' ? 'background:linear-gradient(135deg,#e61f7f,#b81964); color:#fff; box-shadow:0 2px 8px rgba(230,31,127,0.2);' : 'background:transparent; color:#6b7280;' }}">
            🏷️ Generate Categories (YMYL & EEAT)
        </button>
    </div>

    {{-- ═══ STEP 1: INPUT ═══ --}}
    @if($currentStep == 1)
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:24px; margin-bottom:24px;">
        <h3 style="font-size:16px; font-weight:600; color:#1f2937; margin:0 0 4px 0;">
            {{ $generationType === 'category' ? 'Paste category names' : 'Paste product names' }}
        </h3>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
            <p style="font-size:14px; color:#9ca3af; margin:0;">One per line, comma-separated, or upload a file below.</p>
            @if($generationType === 'category')
                <button wire:click="loadExistingCategories" type="button"
                    style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:10px; border:1px solid #e61f7f; background:#fff; color:#e61f7f; font-size:12px; font-weight:700; cursor:pointer; transition:all 0.2s;"
                    onmouseenter="this.style.background='#fdf2f8'" onmouseleave="this.style.background='#fff'">
                    📂 Load Existing Categories
                </button>
            @endif
        </div>

        <textarea wire:model="rawProductList" rows="6"
            placeholder="{{ $generationType === 'category' ? 'Diabetes Care&#10;Erectile Dysfunction&#10;Cardiovascular Health' : 'Paracetamol 500mg&#10;Ibuprofen 400mg&#10;Omeprazole 20mg' }}"
            style="width:100%; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb; padding:12px 16px; font-size:14px; font-family:monospace; resize:none; margin-bottom:16px; box-sizing:border-box;"></textarea>

        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-bottom:16px;">
            <button wire:click="parseInput" type="button"
                x-data="{ clicked: false }"
                @click="clicked = true"
                :disabled="clicked"
                style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:12px; border:none; font-size:14px; font-weight:600; color:#fff; cursor:pointer; background:linear-gradient(135deg,#e61f7f,#b81964); box-shadow:0 2px 8px rgba(230,31,127,0.25);">
                <span x-show="!clicked">🔍 Parse Names</span>
                <span x-show="clicked" x-cloak style="display:inline-flex; align-items:center; gap:6px;">
                    <span style="width:14px; height:14px; border:2px solid rgba(255,255,255,0.4); border-top-color:#fff; border-radius:50%; animation:spin 0.8s linear infinite;"></span>
                    Parsing...
                </span>
            </button>
            <span style="font-size:14px; color:#9ca3af;">or upload</span>
            <div style="display:flex; gap:8px;">
                <label style="cursor:pointer; padding:8px 12px; border:2px dashed #e5e7eb; border-radius:12px; font-size:14px; color:#9ca3af; display:flex; align-items:center; gap:4px;">
                    📊 Excel <input type="file" wire:model="excelFile" wire:change="parseInput" accept=".xlsx,.xls" style="display:none;">
                </label>
                <label style="cursor:pointer; padding:8px 12px; border:2px dashed #e5e7eb; border-radius:12px; font-size:14px; color:#9ca3af; display:flex; align-items:center; gap:4px;">
                    📄 CSV <input type="file" wire:model="csvFile" wire:change="parseInput" accept=".csv" style="display:none;">
                </label>
                <label style="cursor:pointer; padding:8px 12px; border:2px dashed #e5e7eb; border-radius:12px; font-size:14px; color:#9ca3af; display:flex; align-items:center; gap:4px;">
                    📝 TXT <input type="file" wire:model="txtFile" wire:change="parseInput" accept=".txt" style="display:none;">
                </label>
            </div>
        </div>

        @if($csvFile || $txtFile || $excelFile)
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px;">
            @if($csvFile)<span style="display:inline-flex; align-items:center; gap:4px; padding:4px 12px; background:#eff6ff; color:#2563eb; font-size:12px; border-radius:9999px; border:1px solid #bfdbfe;">📄 {{ $csvFile->getClientOriginalName() }}</span>@endif
            @if($txtFile)<span style="display:inline-flex; align-items:center; gap:4px; padding:4px 12px; background:#fffbeb; color:#d97706; font-size:12px; border-radius:9999px; border:1px solid #fde68a;">📝 {{ $txtFile->getClientOriginalName() }}</span>@endif
            @if($excelFile)<span style="display:inline-flex; align-items:center; gap:4px; padding:4px 12px; background:#f0fdf4; color:#16a34a; font-size:12px; border-radius:9999px; border:1px solid #bbf7d0;">📊 {{ $excelFile->getClientOriginalName() }}</span>@endif
        </div>
        @endif

        @if(count($parsedNames))
        <div style="border:1px solid #fbcfe8; border-radius:12px; background:rgba(253,242,248,0.5); padding:16px; margin-bottom:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <span style="font-size:14px; font-weight:600; color:#db2777;">{{ count($parsedNames) }} products detected</span>
                <button wire:click="$set('parsedNames', [])" type="button" style="font-size:12px; color:#9ca3af; border:none; background:none; cursor:pointer;">Clear all</button>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:6px; max-height:176px; overflow-y:auto;">
                @foreach($parsedNames as $i => $name)
                <span style="display:inline-flex; align-items:center; gap:6px; background:#fff; color:#374151; font-size:12px; padding:6px 12px; border-radius:8px; border:1px solid #e5e7eb; transition:all 0.15s;"
                      onmouseenter="this.querySelector('button').style.opacity='1'" onmouseleave="this.querySelector('button').style.opacity='0'">
                    <span style="width:6px; height:6px; border-radius:50%; background:#ec4899;"></span>
                    {{ $name }}
                    <button wire:click="removeFromParsed({{ $i }})" type="button"
                        style="color:#ef4444; border:none; background:none; cursor:pointer; padding:0; margin-left:2px; font-size:16px; font-weight:700; line-height:1; opacity:0; transition:opacity 0.15s;">&times;</button>
                </span>
                @endforeach
            </div>
        </div>

        {{-- Generate All button --}}
        <button wire:click="generateAll" type="button"
            wire:loading.attr="disabled"
            wire:target="generateAll"
            wire:loading.class="opacity-60 cursor-not-allowed"
            style="width:100%; display:flex; align-items:center; justify-content:center; gap:8px; padding:14px; border-radius:12px; border:none; font-size:16px; font-weight:700; color:#fff; cursor:pointer; background:linear-gradient(135deg,#e61f7f,#b81964,#583fa8); box-shadow:0 8px 24px rgba(230,31,127,0.3);">
            <span wire:loading.remove wire:target="generateAll">✨ Generate All {{ count($parsedNames) }} {{ $generationType === 'category' ? 'Categories' : 'Products' }}</span>
            <span wire:loading wire:target="generateAll" style="display:none; align-items:center; gap:8px;">
                <span style="width:16px; height:16px; border:2px solid rgba(255,255,255,0.4); border-top-color:#fff; border-radius:50%; animation:spin 0.8s linear infinite; display:inline-block;"></span>
                Generating {{ count($parsedNames) }} {{ $generationType === 'category' ? 'categories' : 'products' }}...
            </span>
        </button>

        @endif
    </div>
    @endif

    {{-- ═══ PROGRESS BAR + WORKER CONTROLS (shown during generation) ═══ --}}
    @if($isGenerating)
    {{-- 10s, not 5s: each poll now performs a generation step that can run for
         minutes, and shared hosting has a small pool of PHP entry processes.
         Polling too fast just queues up requests that block on the DB lock. --}}
    <div wire:poll.10000ms="pollStatus" wire:key="progress-bar" style="margin-bottom:24px;">

        {{-- Worker Status Panel --}}
        <div style="border-radius:12px; overflow:hidden; border:1px solid #e5e7eb; margin-bottom:12px;">
            <div style="padding:16px 20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;
                @if($workerStatus === 'running') background:linear-gradient(135deg,#065f46,#047857);
                @elseif($workerStatus === 'stale') background:linear-gradient(135deg,#92400e,#b45309);
                @elseif($workerStatus === 'stopped') background:linear-gradient(135deg,#7f1d1d,#b91c1c);
                @else background:linear-gradient(135deg,#374151,#4b5563);
                @endif">

                <div style="display:flex; align-items:center; gap:12px;">
                    {{-- Status indicator --}}
                    <div style="position:relative; width:12px; height:12px;">
                        <div style="width:12px; height:12px; border-radius:50%;
                            @if($workerStatus === 'running') background:#34d399; box-shadow:0 0 8px #34d399;
                            @elseif($workerStatus === 'stale') background:#fbbf24; box-shadow:0 0 8px #fbbf24;
                            @elseif($workerStatus === 'stopped') background:#f87171; box-shadow:0 0 8px #f87171;
                            @else background:#9ca3af; @endif">
                        </div>
                        @if($workerStatus === 'running')
                        <div style="position:absolute; top:0; left:0; width:12px; height:12px; border-radius:50%; background:#34d399; animation:pulse-dot 1.5s infinite;"></div>
                        @endif
                    </div>
                    <div>
                        <div style="font-size:14px; font-weight:700; color:#fff;">
                            Queue Worker:
                            @if($workerStatus === 'running') ✅ Running
                            @elseif($workerStatus === 'stale') ⚠️ Possibly Crashed
                            @elseif($workerStatus === 'stopped') ❌ Stopped
                            @else ⏸️ Idle
                            @endif
                        </div>
                        <div style="font-size:11px; color:rgba(255,255,255,0.6); margin-top:2px;">
                            Text: {{ str_replace(['openai/', 'google/'], '', $textModel) }} · Image: {{ str_replace(['openai/', 'google/'], '', $imageModel) }}
                        </div>
                    </div>
                </div>

                {{-- Worker control buttons --}}
                <div style="display:flex; gap:6px;">
                    @if($workerStatus !== 'running')
                    <button wire:click="startQueueWorker" type="button" style="padding:6px 14px; border-radius:8px; border:none; font-size:12px; font-weight:700; cursor:pointer; background:#34d399; color:#065f46;">
                        ▶ Start Worker
                    </button>
                    @endif
                    <button wire:click="restartQueueWorker" type="button" style="padding:6px 14px; border-radius:8px; border:none; font-size:12px; font-weight:600; cursor:pointer; background:rgba(255,255,255,0.15); color:#fff; backdrop-filter:blur(4px);">
                        🔄 Restart
                    </button>
                    <button wire:click="forceStopJobs" type="button"
                        onclick="return confirm('Are you sure? This will cancel ALL pending jobs.')"
                        style="padding:6px 14px; border-radius:8px; border:none; font-size:12px; font-weight:600; cursor:pointer; background:rgba(239,68,68,0.2); color:#fca5a5;">
                        ⛔ Force Stop
                    </button>
                </div>
            </div>

            {{-- Current task + progress --}}
            <div style="padding:16px 20px; background:#fff;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        @if($workerStatus === 'running')
                        <div style="width:16px; height:16px; border:2px solid #e5e7eb; border-top-color:#e61f7f; border-radius:50%; animation:spin 0.8s linear infinite;"></div>
                        @endif
                        <span style="font-size:14px; font-weight:600; color:#374151;">
                            @if(!empty($currentTask))
                                {{ $currentTask }}
                            @else
                                Waiting for next task...
                            @endif
                        </span>
                        @if(!empty($elapsedTime))
                        <span style="font-size:12px; color:#9ca3af; font-family:monospace; background:#f3f4f6; padding:2px 8px; border-radius:6px;">⏱ {{ $elapsedTime }}</span>
                        @endif
                    </div>
                    <span style="font-size:13px; color:#6b7280; font-family:monospace; font-weight:700;">{{ $completedCount }}/{{ $totalCount }} · {{ $this->getProgressPercent() }}%</span>
                </div>
                <div style="width:100%; height:8px; border-radius:4px; background:#f3f4f6; overflow:hidden;">
                    <div style="height:100%; border-radius:4px; background:linear-gradient(90deg,#e61f7f,#7c3aed); box-shadow:0 0 8px rgba(230,31,127,0.4); transition:width 0.4s linear; width:{{ $this->getProgressPercent() }}%;"></div>
                </div>

                {{-- Per-item mini status --}}
                <div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:10px;">
                    @foreach(array_slice($queueItems, 0, 20) as $qi)
                    <span title="{{ $qi['product_name'] }}" style="display:inline-flex; align-items:center; gap:3px; font-size:10px; padding:2px 8px; border-radius:6px;
                        @if($qi['status'] === 'completed') background:#f0fdf4; color:#16a34a;
                        @elseif($qi['status'] === 'generating') background:#fffbeb; color:#d97706;
                        @elseif($qi['status'] === 'text_generated') background:#f5f3ff; color:#7c3aed;
                        @elseif($qi['status'] === 'failed') background:#fef2f2; color:#dc2626;
                        @else background:#f3f4f6; color:#9ca3af;
                        @endif">
                        @if($qi['status'] === 'completed') ✅
                        @elseif($qi['status'] === 'generating') 📝
                        @elseif($qi['status'] === 'text_generated') 🖼️
                        @elseif($qi['status'] === 'failed') ❌
                        @else ⏳
                        @endif
                        {{ Str::limit($qi['product_name'], 20) }}
                    </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ REVIEW CARDS (shown during generation or review step) ═══ --}}
    @if($isGenerating || $currentStep >= 2)
    <div x-data x-init="$nextTick(() => { const el = document.getElementById('review-section'); if(el) el.scrollIntoView({behavior:'smooth',block:'start'}); })"></div>
    <div id="review-section">
    @foreach(array_slice($queueItems, 0, 50) as $item)
    @php $d=$item['generated_data']??[]; $done=in_array($item['status'],['completed','text_generated','saved','skipped']); $approved=!empty($item['approved_by']); @endphp
    <div wire:key="item-{{ $item['id'] }}" style="background:#fff; border:1px solid {{ $approved?'#86efac':($item['status']==='failed'?'#fecaca':'#e5e7eb') }}; border-radius:12px; overflow:hidden; margin-bottom:16px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <div style="height:4px; background:{{ $approved?'#22c55e':($done?'#e61f7f':($item['status']==='failed'?'#ef4444':'#e5e7eb')) }};"></div>
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:12px; padding:12px 20px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; {{ $approved?'background:#f0fdf4; color:#16a34a;':($done?'background:#fdf2f8; color:#db2777;':'background:#f3f4f6; color:#9ca3af;') }}">
                    {{ strtoupper(substr($item['product_name'],0,2)) }}
                </div>
                <span style="font-weight:600; color:#1f2937; font-size:14px;">{{ $item['product_name'] }}</span>
                <span style="font-size:12px; padding:2px 10px; border-radius:9999px; font-weight:600;
                    @if($item['status']==='completed') background:#f0fdf4; color:#16a34a;
                    @elseif($item['status']==='text_generated') background:#f5f3ff; color:#7c3aed;
                    @elseif($item['status']==='generating') background:#fffbeb; color:#d97706;
                    @elseif($item['status']==='failed') background:#fef2f2; color:#dc2626;
                    @elseif($item['status']==='skipped') background:#f3f4f6; color:#9ca3af;
                    @else background:#eff6ff; color:#2563eb; @endif">
                    @php $statusLabels = ['pending'=>'📋 Queued','generating'=>'⏳ Processing','text_generated'=>'📝 Text Ready','completed'=>'✅ Done','failed'=>'❌ Failed','skipped'=>'↩️ Skipped','saved'=>'💾 Saved']; @endphp
                    {{ $statusLabels[$item['status']] ?? ucfirst($item['status']) }}
                </span>
            </div>
            @if($done)
            <div style="display:flex; gap:6px;">
                <button wire:click="regenerateText({{ $item['id'] }})" type="button" style="padding:6px 12px; border-radius:8px; border:1px solid #e5e7eb; background:#fff; color:#6b7280; font-size:12px; cursor:pointer;">🔄 Text</button>
                @if($generationType === 'product')
                    <button wire:click="regenerateImage({{ $item['id'] }})" type="button" style="padding:6px 12px; border-radius:8px; border:1px solid #e5e7eb; background:#fff; color:#6b7280; font-size:12px; cursor:pointer;">🖼 Image</button>
                @endif
                <button wire:click="skipProduct({{ $item['id'] }})" type="button" style="padding:6px 12px; border-radius:8px; border:1px solid #e5e7eb; background:#fff; color:#9ca3af; font-size:12px; cursor:pointer;">Skip</button>
                <button wire:click="approveProduct({{ $item['id'] }})" type="button" style="padding:6px 16px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; {{ $approved?'background:#22c55e; color:#fff; border:none;':'background:#fff; color:#16a34a; border:2px solid #4ade80;' }}">
                    {{ $approved?'✓ Approved':'Approve' }}
                </button>
            </div>
            @endif
        </div>
        @if(in_array($item['status'],['generating','pending']))
        <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:40px 20px; color:#9ca3af; gap:12px;">
            <div style="width:24px; height:24px; border:2px solid #e5e7eb; border-top-color:#ec4899; border-radius:50%; animation:spin 0.8s linear infinite;"></div>
            <span style="font-size:14px;">Generating text with AI...</span>
        </div>
        @elseif($item['status']==='failed')
        <div style="margin:0 20px 20px; padding:16px; background:#fef2f2; border-radius:12px; font-size:13px; color:#dc2626; word-break:break-word; max-height:120px; overflow:auto;">{{ $item['error_message']??'Unknown error' }}</div>
        @elseif($done)
        @if($generationType === 'category')
        <div style="display:flex; gap:16px; padding:20px; border-top:1px solid #f3f4f6;">
            <div style="flex:1; display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div style="background:#f5f3ff; border:1px solid #ddd6fe; border-radius:12px; padding:16px; grid-column:span 2;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                        <span style="font-size:12px; font-weight:700; color:#7c3aed; text-transform:uppercase;">Suggested Icon:</span>
                        <span style="font-family:monospace; font-weight:700; font-size:14px; background:#ddd6fe; color:#5b21b6; padding:2px 8px; border-radius:6px;">
                            {{ $d['icon'] ?? 'heroicon-o-tag' }}
                        </span>
                    </div>
                    <div style="font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; margin-bottom:6px;">Generated Description (YMYL & EEAT Compliant)</div>
                    <div class="category-description-preview" style="font-size:14px; color:#374151; max-height:250px; overflow-y:auto; padding:16px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; line-height:1.6; box-shadow:inset 0 1px 2px rgba(0,0,0,0.05);">
                        {!! $d['description'] ?? '—' !!}
                    </div>
                </div>
                <div style="background:#eef2ff; border:1px solid #c7d2fe; border-radius:12px; padding:16px;">
                    <div style="font-size:11px; font-weight:700; color:#4f46e5; text-transform:uppercase; margin-bottom:4px;">Meta Title</div>
                    <div style="font-weight:600; color:#3730a3; font-size:14px;">{{ $d['meta_title'] ?? '—' }}</div>
                </div>
                <div style="background:#f0fdfa; border:1px solid #99f6e4; border-radius:12px; padding:16px;">
                    <div style="font-size:11px; font-weight:700; color:#0d9488; text-transform:uppercase; margin-bottom:4px;">Meta Description</div>
                    <div style="font-size:13px; color:#115e59; line-height:1.5;">{{ $d['meta_description'] ?? '—' }}</div>
                </div>
            </div>
        </div>
        @else
        <div style="display:flex; gap:16px; padding:20px; border-top:1px solid #f3f4f6;">
            <div style="width:112px; flex-shrink:0;">
                @if($item['image_path'])
                <img src="{{ Storage::url($item['image_path']) }}" loading="lazy" decoding="async" style="width:112px; height:112px; object-fit:contain; border-radius:12px; border:1px solid #e5e7eb; background:#fff;" alt="product">
                @else
                <div style="width:112px; height:112px; background:#f9fafb; border-radius:12px; border:1px solid #e5e7eb; display:flex; align-items:center; justify-content:center; font-size:11px; color:#d1d5db;">No image</div>
                @endif
            </div>
            <div style="flex:1; display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:12px;">
                    <div style="font-size:11px; font-weight:700; color:#16a34a; text-transform:uppercase; margin-bottom:4px;">Price</div>
                    <div style="font-size:18px; font-weight:900; color:#15803d;">${{ number_format($d['price']??0,2) }}</div>
                    @if(!empty($d['compare_price']))<div style="font-size:12px; color:#4ade80; text-decoration:line-through;">${{ number_format($d['compare_price'],2) }}</div>@endif
                </div>
                <div style="background:#f5f3ff; border:1px solid #ddd6fe; border-radius:12px; padding:12px;">
                    <div style="font-size:11px; font-weight:700; color:#7c3aed; text-transform:uppercase; margin-bottom:4px;">Category</div>
                    <div style="font-weight:600; color:#5b21b6; font-size:14px;">{{ $d['category']??'—' }}</div>
                    <div style="font-size:12px; color:#a78bfa;">{{ $d['brand']??'—' }}</div>
                </div>
                <div style="background:#eef2ff; border:1px solid #c7d2fe; border-radius:12px; padding:12px;">
                    <div style="font-size:11px; font-weight:700; color:#4f46e5; text-transform:uppercase; margin-bottom:4px;">SKU &amp; Unit</div>
                    <div style="font-family:monospace; font-size:14px; font-weight:700; color:#3730a3;">{{ $d['sku']??'—' }}</div>
                    <div style="font-size:12px; color:#818cf8;">{{ $d['unit']??'—' }}{{ !empty($d['weight'])?' · '.$d['weight'].'g':'' }}</div>
                </div>
                <div style="grid-column:1/-1; background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:12px;">
                    <div style="font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; margin-bottom:4px;">Composition &amp; Description</div>
                    <div style="font-weight:500; color:#374151; font-size:14px;">{{ $d['composition']??'—' }} · by {{ $d['manufacturer']??'—' }}</div>
                    <div style="font-size:12px; color:#9ca3af; margin-top:4px;">{{ $d['short_description']??'—' }}</div>
                </div>
                <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:12px;">
                    <div style="font-size:11px; font-weight:700; color:#d97706; text-transform:uppercase; margin-bottom:4px;">Rx</div>
                    <span style="font-size:12px; font-weight:700; padding:2px 8px; border-radius:6px; {{ ($d['requires_prescription']??false)?'background:#fef3c7; color:#b45309;':'background:#f0fdf4; color:#16a34a;' }}">{{ ($d['requires_prescription']??false)?'Required':'OTC' }}</span>
                </div>
                <div style="background:#f0fdfa; border:1px solid #99f6e4; border-radius:12px; padding:12px;">
                    <div style="font-size:11px; font-weight:700; color:#0d9488; text-transform:uppercase; margin-bottom:4px;">Storage</div>
                    <div style="font-size:14px; color:#115e59;">{{ $d['storage_conditions']??'—' }}</div>
                </div>
                <div style="background:#fff1f2; border:1px solid #fecdd3; border-radius:12px; padding:12px;">
                    <div style="font-size:11px; font-weight:700; color:#e11d48; text-transform:uppercase; margin-bottom:4px;">Tags</div>
                    <div style="display:flex; flex-wrap:wrap; gap:4px;">
                        @forelse($d['tags']??[] as $t)
                        <span style="font-size:11px; background:#fff; border:1px solid #fecdd3; padding:2px 8px; border-radius:9999px; color:#be123c;">{{ $t }}</span>
                        @empty <span style="font-size:12px; color:#fda4af;">—</span> @endforelse
                    </div>
                </div>
            </div>
        </div>
        @if($item['status']==='text_generated')
        <div style="margin:0 20px 16px; padding:10px 14px; background:#f5f3ff; border:1px solid #ddd6fe; border-radius:10px; display:flex; align-items:center; gap:10px;">
            <div style="width:14px; height:14px; border:2px solid #c4b5fd; border-top-color:#7c3aed; border-radius:50%; animation:spin 0.8s linear infinite;"></div>
            <span style="font-size:12px; color:#6d28d9;">Generating product image in background...</span>
        </div>
        @endif
        @endif
        @endif
    </div>
    @endforeach
    </div>
    @endif

    {{-- ═══ SAVE BAR ═══ --}}
    @if(!$isGenerating && count($queueItems) > 0)
    @php
        $approvedCount = collect($queueItems)->filter(fn($i)=>!empty($i['approved_by']))->count();
        $skippedCount  = collect($queueItems)->filter(fn($i)=>$i['status']==='skipped')->count();
        $failedCount   = collect($queueItems)->filter(fn($i)=>$i['status']==='failed')->count();
    @endphp
    <div style="position:fixed; bottom:0; left:0; right:0; z-index:60; border-top:1px solid #e5e7eb; padding:16px 24px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; background:rgba(255,255,255,0.97); backdrop-filter:blur(12px); box-shadow:0 -4px 20px rgba(0,0,0,0.08);">
        <div style="display:flex; align-items:center; gap:20px;">
            <div style="text-align:center;"><div style="font-size:24px; font-weight:900; color:#22c55e;">{{ $approvedCount }}</div><div style="font-size:10px; font-weight:700; color:#4ade80; text-transform:uppercase;">Approved</div></div>
            <div style="width:1px; height:32px; background:#e5e7eb;"></div>
            <div style="text-align:center;"><div style="font-size:24px; font-weight:900; color:#d1d5db;">{{ $skippedCount }}</div><div style="font-size:10px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Skipped</div></div>
            <div style="width:1px; height:32px; background:#e5e7eb;"></div>
            <div style="text-align:center;"><div style="font-size:24px; font-weight:900; {{ $failedCount>0?'color:#f87171;':'color:#d1d5db;' }}">{{ $failedCount }}</div><div style="font-size:10px; font-weight:700; {{ $failedCount>0?'color:#f87171;':'color:#9ca3af;' }} text-transform:uppercase;">Failed</div></div>
        </div>
        <div style="display:flex; align-items:center; gap:12px;">
            <button wire:click="resetForm" type="button" style="padding:8px 16px; border-radius:12px; border:1px solid #e5e7eb; background:#f9fafb; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">↻ New Batch</button>
            <button wire:click="saveApproved" type="button" wire:loading.attr="disabled" wire:target="saveApproved" {{ $approvedCount===0?'disabled':'' }}
                style="padding:10px 24px; border-radius:12px; border:none; font-size:14px; font-weight:700; color:#fff; cursor:pointer; {{ $approvedCount===0?'opacity:0.4; cursor:not-allowed;':'' }} background:linear-gradient(135deg,#e61f7f,#b81964); box-shadow:0 6px 20px rgba(230,31,127,0.3);">
                <span wire:loading.remove wire:target="saveApproved">💾 Save {{ $approvedCount }} {{ $generationType === 'category' ? 'Categories' : 'Products' }}</span>
                <span wire:loading wire:target="saveApproved">Saving...</span>
            </button>
        </div>
    </div>
    @endif

</div>

<style>
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
@keyframes pulse-dot{0%{opacity:1;transform:scale(1)}50%{opacity:0;transform:scale(2)}100%{opacity:0;transform:scale(2.5)}}
[x-cloak]{display:none !important}
</style>
</x-filament-panels::page>