<div
    x-data="{
        scannedCode: '',
        isScanning: false,
        lastScan: null,
        focusInput() {
            this.$refs.scanInput.focus();
        }
    }"
    x-init="focusInput()"
    class="relative"
>
    {{-- Scanner Input --}}
    <div class="flex items-center gap-2">
        <div class="relative flex-1">
            <input
                x-ref="scanInput"
                type="text"
                x-model="scannedCode"
                @keydown.enter.prevent="
                    if (scannedCode.trim()) {
                        $wire.processScannedCode(scannedCode.trim());
                        lastScan = scannedCode.trim();
                        scannedCode = '';
                    }
                "
                @blur="setTimeout(() => focusInput(), 100)"
                placeholder="📷 امسح Barcode هنا أو اكتب الكود..."
                class="w-full px-4 py-3 text-lg font-mono border-2 border-primary-500 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-600 bg-white dark:bg-gray-800 dark:text-white transition-all duration-200"
                autocomplete="off"
                autofocus
            />
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                <x-heroicon-o-qr-code class="w-6 h-6 text-primary-500 animate-pulse" />
            </div>
        </div>
        
        <button
            type="button"
            @click="focusInput()"
            class="px-4 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors duration-200"
            title="تفعيل الماسح"
        >
            <x-heroicon-o-viewfinder-circle class="w-6 h-6" />
        </button>
    </div>

    {{-- Last Scanned Code --}}
    <div x-show="lastScan" x-cloak class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        آخر كود تم مسحه: <span class="font-mono font-bold text-primary-600" x-text="lastScan"></span>
    </div>

    {{-- Keyboard Shortcut Hint --}}
    <div class="mt-1 text-xs text-gray-400">
        💡 اضغط <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">F2</kbd> للتركيز على حقل المسح
    </div>

    {{-- Global Keyboard Shortcut --}}
    <div
        x-data
        @keydown.window.f2.prevent="$refs.scanInput.focus()"
    ></div>
</div>
