{{-- Service Worker Registration --}}
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js')
                .then(function(registration) {
                    console.log('ServiceWorker registration successful:', registration.scope);
                })
                .catch(function(err) {
                    console.log('ServiceWorker registration failed:', err);
                });
        });
    }

    // PWA Install Prompt
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent the mini-infobar from appearing on mobile
        e.preventDefault();
        // Stash the event so it can be triggered later
        deferredPrompt = e;
        
        // Show custom install button/banner (optional)
        console.log('PWA install prompt ready');
        
        // You can show a custom UI here to prompt the user
        // For example, a Filament notification:
        if (typeof window.filament !== 'undefined') {
            window.dispatchEvent(new CustomEvent('banner', {
                detail: {
                    type: 'info',
                    message: 'Install E-Clean app untuk akses lebih cepat!',
                    action: {
                        label: 'Install',
                        onClick: () => {
                            if (deferredPrompt) {
                                deferredPrompt.prompt();
                                deferredPrompt.userChoice.then((choiceResult) => {
                                    if (choiceResult.outcome === 'accepted') {
                                        console.log('User accepted the install prompt');
                                    }
                                    deferredPrompt = null;
                                });
                            }
                        }
                    }
                }
            }));
        }
    });

    // Detect when PWA is installed
    window.addEventListener('appinstalled', () => {
        console.log('PWA was installed');
        deferredPrompt = null;
    });

    // Check if app is running as PWA
    function isPWA() {
        return window.matchMedia('(display-mode: standalone)').matches || 
               window.navigator.standalone === true;
    }

    if (isPWA()) {
        console.log('Running as PWA');
        // You can add PWA-specific features here
    }
</script>
