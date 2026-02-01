<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PWA Test - Shipping Manager</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0066cc">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ShipManager">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="/icons/icon-192x192.png">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 32px;
        }

        .subtitle {
            color: #718096;
            margin-bottom: 30px;
            font-size: 16px;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .status-item {
            padding: 20px;
            background: #f7fafc;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .status-label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .status-value {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
        }

        .status-value.success {
            color: #48bb78;
        }

        .status-value.error {
            color: #f56565;
        }

        .status-value.warning {
            color: #ed8936;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            margin: 5px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
            box-shadow: none;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .log {
            background: #2d3748;
            color: #a0aec0;
            padding: 20px;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 20px;
        }

        .log-entry {
            margin-bottom: 8px;
            padding: 4px 0;
            border-bottom: 1px solid #4a5568;
        }

        .log-time {
            color: #667eea;
            margin-left: 10px;
        }

        .feature-list {
            list-style: none;
            margin-top: 20px;
        }

        .feature-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
        }

        .feature-list li:before {
            content: "✓";
            color: #48bb78;
            font-weight: bold;
            margin-left: 10px;
            font-size: 18px;
        }

        .link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🚀 PWA Test Dashboard</h1>
            <p class="subtitle">اختبار وظائف Progressive Web App</p>

            <div class="status-grid">
                <div class="status-item">
                    <div class="status-label">Service Worker</div>
                    <div class="status-value" id="sw-status">جاري الفحص...</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Installation</div>
                    <div class="status-value" id="install-status">جاري الفحص...</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Notifications</div>
                    <div class="status-value" id="notif-status">جاري الفحص...</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Online Status</div>
                    <div class="status-value success" id="online-status">متصل</div>
                </div>
            </div>

            <h2 style="margin-bottom: 15px;">الإجراءات</h2>
            <div class="actions">
                <button class="btn" id="install-btn" onclick="installApp()">تثبيت التطبيق</button>
                <button class="btn" id="notif-btn" onclick="requestNotifications()">تفعيل الإشعارات</button>
                <button class="btn" id="test-notif-btn" onclick="testNotification()">اختبار إشعار</button>
                <button class="btn btn-secondary" onclick="clearCache()">مسح Cache</button>
                <button class="btn btn-secondary" onclick="unregisterSW()">إلغاء Service Worker</button>
                <a href="/admin" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">لوحة التحكم</a>
            </div>

            <div class="log" id="log">
                <div class="log-entry">
                    <span>📋 سجل الأحداث</span>
                    <span class="log-time" id="current-time"></span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 15px;">المميزات المتاحة</h2>
            <ul class="feature-list">
                <li>تثبيت التطبيق على الجهاز</li>
                <li>العمل بدون اتصال بالإنترنت</li>
                <li>إشعارات فورية (Push Notifications)</li>
                <li>تحديثات تلقائية في الخلفية</li>
                <li>سرعة تحميل فائقة</li>
                <li>اختصارات سريعة للصفحات</li>
            </ul>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                <h3 style="margin-bottom: 10px;">المستندات</h3>
                <p>
                    <a href="/PWA_README.md" class="link" target="_blank">📚 دليل PWA الكامل (English)</a><br>
                    <a href="/PWA_GUIDE_AR.md" class="link" target="_blank">📚 دليل PWA السريع (العربية)</a>
                </p>
            </div>
        </div>
    </div>

    <script src="/js/pwa.js"></script>
    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('ar-EG');
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Log function
        function log(message, type = 'info') {
            const logDiv = document.getElementById('log');
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            const icon = type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ';
            entry.innerHTML = `<span>${icon} ${message}</span><span class="log-time">${new Date().toLocaleTimeString('ar-EG')}</span>`;
            logDiv.appendChild(entry);
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        // Check Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistration().then(reg => {
                if (reg) {
                    document.getElementById('sw-status').textContent = 'مفعّل ✓';
                    document.getElementById('sw-status').className = 'status-value success';
                    log('Service Worker مسجل ويعمل', 'success');
                } else {
                    document.getElementById('sw-status').textContent = 'غير مفعّل';
                    document.getElementById('sw-status').className = 'status-value error';
                    log('Service Worker غير مسجل', 'error');
                }
            });
        } else {
            document.getElementById('sw-status').textContent = 'غير مدعوم';
            document.getElementById('sw-status').className = 'status-value error';
        }

        // Check installation status
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches 
            || window.navigator.standalone 
            || document.referrer.includes('android-app://');
        
        if (isStandalone) {
            document.getElementById('install-status').textContent = 'مثبت ✓';
            document.getElementById('install-status').className = 'status-value success';
            document.getElementById('install-btn').disabled = true;
            log('التطبيق مثبت على الجهاز', 'success');
        } else {
            document.getElementById('install-status').textContent = 'غير مثبت';
            document.getElementById('install-status').className = 'status-value warning';
        }

        // Check notification permission
        if ('Notification' in window) {
            const permission = Notification.permission;
            if (permission === 'granted') {
                document.getElementById('notif-status').textContent = 'مفعّل ✓';
                document.getElementById('notif-status').className = 'status-value success';
                document.getElementById('notif-btn').disabled = true;
            } else if (permission === 'denied') {
                document.getElementById('notif-status').textContent = 'محظور';
                document.getElementById('notif-status').className = 'status-value error';
                document.getElementById('notif-btn').disabled = true;
            } else {
                document.getElementById('notif-status').textContent = 'غير مفعّل';
                document.getElementById('notif-status').className = 'status-value warning';
            }
        }

        // Online/Offline status
        window.addEventListener('online', () => {
            document.getElementById('online-status').textContent = 'متصل ✓';
            document.getElementById('online-status').className = 'status-value success';
            log('تم الاتصال بالإنترنت', 'success');
        });

        window.addEventListener('offline', () => {
            document.getElementById('online-status').textContent = 'غير متصل';
            document.getElementById('online-status').className = 'status-value error';
            log('انقطع الاتصال بالإنترنت', 'error');
        });

        // Install app
        function installApp() {
            if (window.pwaManager && window.pwaManager.deferredPrompt) {
                window.pwaManager.promptInstall();
                log('تم طلب تثبيت التطبيق');
            } else {
                log('التثبيت غير متاح حالياً', 'error');
                alert('التطبيق مثبت بالفعل أو المتصفح لا يدعم التثبيت');
            }
        }

        // Request notifications
        async function requestNotifications() {
            if (window.pwaManager) {
                const granted = await window.pwaManager.requestNotificationPermission();
                if (granted) {
                    document.getElementById('notif-status').textContent = 'مفعّل ✓';
                    document.getElementById('notif-status').className = 'status-value success';
                    document.getElementById('notif-btn').disabled = true;
                    log('تم تفعيل الإشعارات', 'success');
                } else {
                    log('تم رفض الإشعارات', 'error');
                }
            }
        }

        // Test notification
        function testNotification() {
            if (Notification.permission === 'granted') {
                new Notification('اختبار الإشعارات', {
                    body: 'الإشعارات تعمل بنجاح! 🎉',
                    icon: '/icons/icon-192x192.png',
                    badge: '/icons/icon-192x192.png',
                    vibrate: [200, 100, 200]
                });
                log('تم إرسال إشعار تجريبي', 'success');
            } else {
                alert('يجب تفعيل الإشعارات أولاً');
                log('الإشعارات غير مفعلة', 'error');
            }
        }

        // Clear cache
        async function clearCache() {
            if ('caches' in window) {
                const names = await caches.keys();
                await Promise.all(names.map(name => caches.delete(name)));
                log('تم مسح جميع الـ Cache', 'success');
                alert('تم مسح Cache بنجاح. سيتم تحديث الصفحة.');
                location.reload();
            }
        }

        // Unregister service worker
        async function unregisterSW() {
            if ('serviceWorker' in navigator) {
                const reg = await navigator.serviceWorker.getRegistration();
                if (reg) {
                    await reg.unregister();
                    log('تم إلغاء Service Worker', 'success');
                    alert('تم إلغاء Service Worker. سيتم تحديث الصفحة.');
                    location.reload();
                }
            }
        }

        log('صفحة اختبار PWA جاهزة', 'success');
    </script>
</body>
</html>
