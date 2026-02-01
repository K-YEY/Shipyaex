<div id="pwa-install-container" class="mr-4">
    <button 
        id="pwa-install-btn-header"
        type="button"
        onclick="window.pwaManager ? window.pwaManager.handleInstallClick() : alert('جاري تحميل التطبيق...')"
        class="flex items-center gap-2 px-3 py-2 text-sm font-bold text-white transition-all duration-300 rounded-lg shadow-lg bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-500 hover:to-primary-400 focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 hover:scale-105"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
            <line x1="12" y1="18" x2="12.01" y2="18"></line>
            <path d="M12 6v6"></path>
            <path d="M15 9l-3 3-3-3"></path>
        </svg>
        <span class="hidden sm:inline">تحميل التطبيق</span>
    </button>
</div>
