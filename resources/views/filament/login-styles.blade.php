<style>
    /* Animated Gradient Background */
    @keyframes gradient {
        0% {
            background-position: 0% 50%;
        }
        50% {
            background-position: 100% 50%;
        }
        100% {
            background-position: 0% 50%;
        }
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }
        100% {
            background-position: 1000px 0;
        }
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.8;
        }
    }

    /* Apply animated gradient to login page background */
    .fi-simple-layout {
        background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe) !important;
        background-size: 400% 400% !important;
        animation: gradient 15s ease infinite !important;
        min-height: 100vh !important;
    }

    /* Glassmorphism effect on login card */
    .fi-simple-main {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(10px) !important;
        -webkit-backdrop-filter: blur(10px) !important;
        border-radius: 24px !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        animation: slideInRight 0.8s ease-out !important;
        overflow: hidden !important;
        position: relative !important;
    }

    /* Shimmer effect on card */
    .fi-simple-main::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: -100% !important;
        width: 100% !important;
        height: 100% !important;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 0.3),
            transparent
        ) !important;
        animation: shimmer 3s infinite !important;
    }

    /* Animate heading */
    .fi-simple-heading {
        animation: fadeInDown 0.8s ease-out !important;
    }

    /* Enhanced input focus effects */
    .fi-input-wrapper input:focus,
    .fi-input-wrapper input:focus-visible {
        transform: translateY(-2px) !important;
        box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.3) !important;
        border-color: #6366f1 !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .fi-input-wrapper {
        transition: all 0.3s ease !important;
    }

    /* Enhanced button hover effect */
    .fi-btn-primary {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        position: relative !important;
        overflow: hidden !important;
    }

    .fi-btn-primary:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 15px 30px -10px rgba(99, 102, 241, 0.5) !important;
    }

    .fi-btn-primary::after {
        content: '' !important;
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        width: 0 !important;
        height: 0 !important;
        border-radius: 50% !important;
        background: rgba(255, 255, 255, 0.3) !important;
        transform: translate(-50%, -50%) !important;
        transition: width 0.6s, height 0.6s !important;
    }

    .fi-btn-primary:active::after {
        width: 300px !important;
        height: 300px !important;
    }

    /* Link underline animation */
    .fi-link {
        position: relative !important;
        transition: color 0.3s ease !important;
    }

    .fi-link::after {
        content: '' !important;
        position: absolute !important;
        bottom: -2px !important;
        left: 0 !important;
        width: 0 !important;
        height: 2px !important;
        background-color: currentColor !important;
        transition: width 0.3s ease !important;
    }

    .fi-link:hover::after {
        width: 100% !important;
    }

    /* Logo pulse animation */
    .fi-simple-header img {
        animation: pulse 2s ease-in-out infinite !important;
    }

    /* Smooth form fade in */
    .fi-form {
        animation: fadeInDown 1s ease-out 0.2s backwards !important;
    }

    /* Error shake animation */
    @keyframes shake {
        0%, 100% {
            transform: translateX(0);
        }
        25% {
            transform: translateX(-10px);
        }
        75% {
            transform: translateX(10px);
        }
    }

    .fi-fo-field-wrp-error-message {
        animation: shake 0.4s ease-in-out !important;
    }

    /* Dark mode adjustments */
    .dark .fi-simple-main {
        background: rgba(17, 24, 39, 0.95) !important;
    }

    /* Mobile responsiveness */
    @media (max-width: 640px) {
        .fi-simple-main {
            border-radius: 16px !important;
            margin: 1rem !important;
        }

        .fi-simple-layout {
            padding: 1rem !important;
        }
    }

    /* Smooth transitions for all interactive elements */
    * {
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
</style>

<script>
    // Add welcome message animation on page load
    document.addEventListener('DOMContentLoaded', function() {
        const heading = document.querySelector('.fi-simple-heading');
        if (heading && !heading.dataset.animated) {
            heading.dataset.animated = 'true';
            heading.style.opacity = '0';
            setTimeout(() => {
                heading.style.transition = 'opacity 0.8s ease-out, transform 0.8s ease-out';
                heading.style.opacity = '1';
            }, 100);
        }
    });
</script>
