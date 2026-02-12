<div class="mb-6 space-y-4">
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
                <div class="flex flex-col gap-1 min-w-[180px]">
                    <label class="text-sm font-medium">🚀 الإجراء التلقائي</label>
                    <select wire:model.live="selectedAction" class="fi-input block w-full rounded-lg border-none bg-white shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:ring-white/10 dark:focus:ring-primary-500">
                        <option value="view">👁️ عرض فقط</option>
                        <option value="delivered">✅ تسليم</option>
                        <option value="assign_shipper">🚚 إسناد لمندوب</option>
                    </select>
                </div>

                @if($selectedAction === 'assign_shipper')
                <div class="flex flex-col gap-1 min-w-[180px] animate-in fade-in slide-in-from-right-2 duration-300">
                    <label class="text-sm font-medium">👤 المندوب المستهدف</label>
                    <select wire:model.live="targetShipperId" class="fi-input block w-full rounded-lg border-none bg-white shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:ring-white/10 dark:focus:ring-primary-500">
                        <option value="">-- اختر مندوب --</option>
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
