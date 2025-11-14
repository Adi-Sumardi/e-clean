<style>
    /* Super aggressive hide for E-Clean API logo */
    body .fi-logo,
    body .fi-simple-header .fi-logo,
    body header .fi-logo,
    body .fi-simple-header-heading,
    body .fi-simple-header-subheading {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        height: 0 !important;
        width: 0 !important;
        max-height: 0 !important;
        max-width: 0 !important;
        position: absolute !important;
        left: -9999px !important;
        overflow: hidden !important;
        pointer-events: none !important;
    }
</style>

<script>
    // JavaScript backup to remove logo if CSS fails
    document.addEventListener('DOMContentLoaded', function() {
        const logos = document.querySelectorAll('.fi-logo, .fi-simple-header-heading, .fi-simple-header-subheading');
        logos.forEach(logo => {
            if (logo) {
                logo.style.display = 'none';
                logo.style.visibility = 'hidden';
                logo.style.opacity = '0';
                logo.style.height = '0';
                logo.style.width = '0';
                logo.remove(); // Actually remove from DOM
            }
        });
    });
</script>
