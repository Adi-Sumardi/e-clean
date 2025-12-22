<div class="custom-login-header" style="text-align: center; margin-bottom: 2rem;">
    <img src="/pwa/icon-192x192.png" alt="CSS Logo" style="width: 100px; height: 100px; margin: 0 auto 1rem; display: block; animation: fadeInDown 0.8s ease-out;">
    <h2 class="welcome-heading" style="font-size: 1.875rem; font-weight: 700; margin-bottom: 0.5rem; animation: fadeInDown 0.8s ease-out 0.1s backwards;">
        Selamat Datang
    </h2>
    <p class="welcome-subheading" style="font-size: 0.875rem; animation: fadeInDown 0.8s ease-out 0.2s backwards;">
        Clean Service System
    </p>
</div>

<style>
    /* Hide default Filament heading, subheading, and logo text */
    .fi-simple-header-heading,
    .fi-simple-header-subheading,
    .fi-simple-heading,
    .fi-logo,
    .fi-simple-header .fi-logo {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        height: 0 !important;
        width: 0 !important;
        position: absolute !important;
        overflow: hidden !important;
    }

    /* Also hide the header container if it only contains logo */
    .fi-simple-header:has(> .fi-logo:only-child) {
        display: none !important;
    }

    .welcome-heading {
        color: #1f2937;
    }

    .welcome-subheading {
        color: #6b7280;
    }

    .dark .welcome-heading {
        color: #f9fafb;
    }

    .dark .welcome-subheading {
        color: #d1d5db;
    }
</style>
