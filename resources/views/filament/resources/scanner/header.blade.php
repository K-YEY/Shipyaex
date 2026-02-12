<div class="mb-6 space-y-4">
    <x-filament::section>
        <div class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1" x-data="{ 
                code: '',
                submit() {
                    if (this.code.trim()) {
                        $wire.processScannedCode(this.code.trim());
                        this.code = '';
                    }
                }
            }">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        placeholder="امسح الباركود هنا..."
                        x-model="code"
                        x-on:keydown.enter.prevent="submit()"
                        autofocus
                    />
                </x-filament::input.wrapper>
            </div>

            <div class="flex gap-4 items-center">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">الإجراء التلقائي</label>
                    <select wire:model.live="selectedAction" class="fi-input block w-full rounded-lg border-none bg-white shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:ring-white/10 dark:focus:ring-primary-500">
                        <option value="view">عرض فقط</option>
                        <option value="delivered">تسليم</option>
                        <option value="assign_shipper">إسناد لمندوب</option>
                    </select>
                </div>

                @if($selectedAction === 'assign_shipper')
                <div class="flex flex-col gap-1 animate-in fade-in duration-300">
                    <label class="text-sm font-medium">اختر المندوب</label>
                    <select wire:model.live="targetShipperId" class="fi-input block w-full rounded-lg border-none bg-white shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:ring-white/10 dark:focus:ring-primary-500">
                        <option value="">-- اختر --</option>
                        @foreach($this->getShippers() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="flex items-center gap-2 pt-6">
                    <x-filament::button wire:click="clearAll" color="danger" icon="heroicon-o-trash">
                        مسح الكل
                    </x-filament::button>
                </div>
            </div>
        </div>
    </x-filament::section>
</div>
