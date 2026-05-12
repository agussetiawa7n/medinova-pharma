<x-filament-panels::page>
<div style="padding-bottom:96px;">

    {{-- Template Selector Tabs --}}
    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:24px;">
        @foreach($this->getTemplates() as $key => $label)
            <button
                wire:click="loadTemplate('{{ $key }}')"
                style="padding:10px 20px; border-radius:8px; font-size:14px; font-weight:500; transition:all 0.15s; border:none; cursor:pointer;
                    {{ $activeKey === $key
                        ? 'background:#e61f7f; color:#fff; box-shadow:0 2px 8px rgba(230,31,127,0.25);'
                        : 'background:#fff; color:#6b7280; border:1px solid #e5e7eb;' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Editor Card --}}
    <div style="background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.08); border:1px solid #e5e7eb; overflow:hidden; margin-bottom:24px;">

        {{-- Card Header --}}
        <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 24px; border-bottom:1px solid #e5e7eb; background:#f9fafb;">
            <div>
                <h3 style="font-size:16px; font-weight:600; color:#1f2937; margin:0;">
                    {{ $this->getTemplateLabel($activeKey) }}
                </h3>
                @if(!empty($availableVariables))
                    <p style="font-size:12px; color:#9ca3af; margin:4px 0 0 0;">
                        Available variables:
                        @foreach($availableVariables as $var)
                            <code style="background:#f3f4f6; padding:1px 6px; border-radius:4px; color:#e61f7f; font-size:12px;">{{ $var }}</code>
                        @endforeach
                    </p>
                @endif
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <label style="display:flex; align-items:center; gap:8px; font-size:14px; color:#6b7280; cursor:pointer;">
                    <input type="checkbox" wire:model="is_active" style="accent-color:#e61f7f; width:16px; height:16px; border-radius:4px; border:1px solid #d1d5db;">
                    Active
                </label>
            </div>
        </div>

        {{-- Form Body --}}
        <div style="padding:24px; display:flex; flex-direction:column; gap:16px;">

            {{-- Subject --}}
            <div>
                <label style="display:block; font-size:14px; font-weight:500; color:#374151; margin-bottom:4px;">Email Subject</label>
                <input
                    type="text"
                    wire:model="subject"
                    style="width:100%; border:1px solid #d1d5db; border-radius:8px; background:#fff; color:#1f2937; padding:10px 16px; font-size:14px; box-sizing:border-box;"
                    placeholder="Email subject..."
                >
            </div>

            {{-- Body --}}
            <div>
                <label style="display:block; font-size:14px; font-weight:500; color:#374151; margin-bottom:4px;">
                    Email Body <span style="color:#9ca3af; font-weight:400;">(HTML supported)</span>
                </label>
                <textarea
                    wire:model="body"
                    rows="14"
                    style="width:100%; border:1px solid #d1d5db; border-radius:8px; background:#fff; color:#1f2937; padding:10px 16px; font-size:14px; font-family:monospace; box-sizing:border-box; resize:vertical;"
                    placeholder="<h2>Hello @&#123;{customer_name}},</h2>..."
                ></textarea>
                <p style="font-size:12px; color:#9ca3af; margin:4px 0 0 0;">Use HTML for formatting. Variables like <code style="background:#f3f4f6; padding:1px 6px; border-radius:4px; color:#e61f7f; font-size:12px;">&#123;&#123;customer_name&#125;&#125;</code> will be replaced automatically.</p>
            </div>

            {{-- Actions --}}
            <div style="display:flex; align-items:center; gap:12px; padding-top:8px;">
                <button
                    wire:click="save"
                    wire:loading.attr="disabled"
                    style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; background:#e61f7f; border:none; border-radius:8px; color:#fff; font-size:14px; font-weight:600; cursor:pointer; box-shadow:0 2px 8px rgba(230,31,127,0.25);"
                >
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Save Template
                </button>

                <button
                    wire:click="previewTemplate"
                    style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; background:#2563eb; border:none; border-radius:8px; color:#fff; font-size:14px; font-weight:600; cursor:pointer; box-shadow:0 2px 8px rgba(37,99,235,0.25);"
                >
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Preview
                </button>

                <button
                    wire:click="resetToDefault"
                    wire:confirm="Reset to default template? Your edits will be lost."
                    style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:8px; color:#6b7280; font-size:14px; font-weight:500; cursor:pointer;"
                >
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Reset to Default
                </button>
            </div>
        </div>
    </div>

    {{-- Live Preview --}}
    @if($preview)
        <div style="background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.08); border:1px solid #e5e7eb; overflow:hidden;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 24px; border-bottom:1px solid #e5e7eb; background:#f9fafb;">
                <h3 style="font-size:14px; font-weight:600; color:#6b7280; margin:0;">Email Preview (Sample Data)</h3>
                <span style="font-size:11px; color:#9ca3af; background:#f3f4f6; padding:3px 10px; border-radius:6px;">Desktop / Mobile</span>
            </div>
            <iframe srcdoc="{{ $preview }}" style="width:100%; height:600px; border:none; border-radius:0 0 12px 12px;"></iframe>
        </div>
    @endif

</div>
</x-filament-panels::page>