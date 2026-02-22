<div class="mb-6 space-y-4" x-data="{}" 
    x-on:play-scan-sound.window="$refs.scanAudio.play()"
    x-on:play-error-sound.window="$refs.errorAudio.play()"
>
    <audio x-ref="scanAudio" src="/scan.mp3" preload="auto"></audio>
    <audio x-ref="errorAudio" src="/error-scan.mp3" preload="auto"></audio>
    <x-filament::section>
        <div class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1" x-data="{ 
                code: '',
                autoSubmit: @entangle('autoSubmit'),
                timeout: null,
                submit() {
                    if (this.code.trim()) {
                        $wire.processScannedCode(this.code.trim());
                        this.code = '';
                        // Play sound immediately for feedback
                        $dispatch('play-scan-sound');
                    }
                },
                handleInput() {
                    if (!this.autoSubmit) return;
                    clearTimeout(this.timeout);
                    if (this.code.trim().length >= 3) {
                        this.timeout = setTimeout(() => this.submit(), 500);
                    }
                }
            }">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">📷 امسح الباركود</label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            placeholder="امسح هنا (تلقائي)..."
                            x-model="code"
                            x-on:input="handleInput()"
                            x-on:keydown.enter.prevent="submit()"
                            autofocus
                        />
                    </x-filament::input.wrapper>
                    <label class="mt-1 flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="autoSubmit" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                        <span class="text-xs text-gray-500 font-medium">إدخال تلقائي عند المسح</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-4 items-center flex-wrap md:flex-nowrap">
                <div class="flex flex-col gap-1 min-w-[200px]">
                    <label class="text-sm font-bold text-gray-700 dark:text-gray-300">🚀 الإجراء التلقائي</label>
                    <select wire:model.live="selectedAction" class="block w-full rounded-lg border-none bg-slate-900 text-white font-semibold shadow-lg ring-1 ring-slate-700 focus:ring-2 focus:ring-primary-500 cursor-pointer transition-all duration-200 hover:bg-slate-800">
                        <option value="view">👁️ عرض فقط (بدون إجراء)</option>
                        <option value="return_shipper">↩️ مرتجع مندوب</option>
                        <option value="return_client">⏪ مرتجع عميل</option>
                        <option value="delivered">✅ تسليم</option>
                        <option value="assign_shipper">🚚 إسناد لمندوب</option>
                    </select>
                </div>

                @if($selectedAction === 'assign_shipper')
                <div class="flex flex-col gap-1 min-w-[200px] animate-in fade-in slide-in-from-right-2 duration-300">
                    <label class="text-sm font-bold text-gray-700 dark:text-gray-300">👤 كابتن التوصيل</label>
                    <select wire:model.live="targetShipperId" class="block w-full rounded-lg border-none bg-indigo-900 text-white font-semibold shadow-lg ring-1 ring-indigo-700 focus:ring-2 focus:ring-primary-500 cursor-pointer transition-all duration-200 hover:bg-indigo-800">
                        <option value="">-- اختر المندوب --</option>
                        @foreach($this->getShippers() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="flex items-center gap-2 pt-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer mr-2">
                        <input type="checkbox" wire:model.live="autoProcess" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="text-sm font-medium">تنفيذ تلقائي</span>
                    </label>
                    
                    <x-filament::button wire:click="clearAll" color="danger" icon="heroicon-o-trash" variant="outline" size="sm">
                        مسح القائمة
                    </x-filament::button>
                </div>
            </div>
        </div>
    </x-filament::section>
</div>
