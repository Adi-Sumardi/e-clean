<div class="custom-login-header" style="text-align: center; margin-bottom: 2rem;">
    <h2 class="welcome-heading" style="font-size: 1.875rem; font-weight: 700; margin-bottom: 0.5rem; animation: fadeInDown 0.8s ease-out;">
        Selamat Datang 👋
    </h2>
    <p class="welcome-subheading" style="font-size: 0.875rem; animation: fadeInDown 0.8s ease-out 0.2s backwards;">
        E-Clean Management System
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
