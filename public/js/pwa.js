// PWA Installation and Service Worker Registration
class PWAManager {
    constructor() {
        this.deferredPrompt = null;
        this.init();
    }

    init() {
        // Register service worker
        if ('serviceWorker' in navigator) {
            this.registerServiceWorker();
        }

        // Handle install prompt
        this.setupInstallPrompt();

        // Check if already installed
        this.checkIfInstalled();
    }

    async registerServiceWorker() {
        try {
            const registration = await navigator.serviceWorker.register('/sw.js', {
                scope: '/'
            });

            console.log('[PWA] Service Worker registered successfully:', registration.scope);

            // Check for updates
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                console.log('[PWA] New service worker found, installing...');

                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        // New service worker available
                        this.showUpdateNotification();
                    }
                });
            });

            // Check for updates every hour
            setInterval(() => {
                registration.update();
            }, 60 * 60 * 1000);

        } catch (error) {
            console.error('[PWA] Service Worker registration failed:', error);
        }
    }

    setupInstallPrompt() {
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent the default prompt
            e.preventDefault();

            // Store the event for later use
            this.deferredPrompt = e;

            // Ensure button is visible
            this.showInstallButton();

            console.log('[PWA] Install prompt ready');
        });

        // Handle successful installation
        window.addEventListener('appinstalled', () => {
            console.log('[PWA] App installed successfully');
            this.deferredPrompt = null;
            this.hideInstallButton();
            // You can show a "Thank you" notification here
        });
    }

    handleInstallClick() {
        if (this.deferredPrompt) {
            // Show the native prompt
            this.deferredPrompt.prompt();

            // Wait for user response
            this.deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('[PWA] User accepted the install prompt');
                } else {
                    console.log('[PWA] User dismissed the install prompt');
                }
                this.deferredPrompt = null;
            });
        } else {
            // Fallback for when the prompt is not ready or not supported
            // e.g. iOS or Desktop Chrome non-HTTPS
            this.showManualInstallInstructions();
        }
    }

    showManualInstallInstructions() {
        const userAgent = window.navigator.userAgent.toLowerCase();
        let message = 'لتثبيت التطبيق:\n';

        if (userAgent.includes('iphone') || userAgent.includes('ipad')) {
            message += '1. اضغط على زر المشاركة (Share) أسفل الشاشة.\n2. اختر "إضافة إلى الصفحة الرئيسية" (Add to Home Screen).';
        } else {
            message += '1. اضغط على قائمة المتصفح (⋮ أو ...).\n2. اختر "تثبيت التطبيق" (Install App) أو "Add to Home Screen".';
        }

        alert(message);
    }

    showInstallButton() {
        const headerContainer = document.getElementById('pwa-install-container');
        if (headerContainer) {
            headerContainer.classList.remove('hidden');
            headerContainer.style.display = 'block'; // Ensure visibility
        }
    }

    hideInstallButton() {
        const headerContainer = document.getElementById('pwa-install-container');
        if (headerContainer) {
            headerContainer.style.display = 'none';
        }
    }

    checkIfInstalled() {
        // Check if running as PWA
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone
            || document.referrer.includes('android-app://');

        if (isStandalone) {
            console.log('[PWA] App is running in standalone mode');
            this.hideInstallButton();
        } else {
            // Not installed, ensure button is visible 
            // (It is visible by default in Blade, but logic here confirms intent)
        }
    }

    showUpdateNotification() {
        // ... (Same as before)
        const notification = document.createElement('div');
        notification.id = 'pwa-update-notification';
        notification.innerHTML = `
            <div style="
                position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
                background: white; padding: 16px 24px; border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); z-index: 10000;
                display: flex; align-items: center; gap: 16px;
                animation: slideInDown 0.5s ease-out;
            ">
                <span style="font-size: 14px; color: #2d3748;">تحديث جديد متاح!</span>
                <button onclick="location.reload()" style="
                    background: #dc2626; color: white; border: none; padding: 8px 16px;
                    border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;
                ">تحديث</button>
                <button onclick="this.closest('div').parentElement.remove()" style="
                    background: transparent; color: #718096; border: none; padding: 8px;
                    cursor: pointer; font-size: 20px; line-height: 1;
                ">&times;</button>
            </div>
        `;
        document.body.appendChild(notification);
    }
}

// Initialize PWA Manager when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.pwaManager = new PWAManager();
    });
} else {
    window.pwaManager = new PWAManager();
}
