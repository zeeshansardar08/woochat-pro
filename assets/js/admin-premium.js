// WooChat Pro – Premium Admin UI Tab Switcher

document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.wcwp-tab');
    const tabContents = document.querySelectorAll('.wcwp-tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            // Remove active from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            // Hide all tab contents
            tabContents.forEach(tc => tc.style.display = 'none');
            // Activate clicked tab
            tab.classList.add('active');
            // Show corresponding content
            const tabName = tab.getAttribute('data-tab');
            const content = document.getElementById('wcwp-tab-content-' + tabName);
            if (content) content.style.display = 'block';
        });
    });
});

// Chatbot Customizer Live Preview

document.addEventListener('DOMContentLoaded', function () {
    const bgColor = document.getElementById('wcwp-chatbot-bg');
    const textColor = document.getElementById('wcwp-chatbot-color');
    const iconColor = document.getElementById('wcwp-chatbot-icon');
    const iconOptions = document.querySelectorAll('.wcwp-icon-option');
    const previewBubble = document.querySelector('.wcwp-chatbot-preview-bubble');
    const previewIcon = document.querySelector('.wcwp-chatbot-preview-icon');
    const welcomeInput = document.getElementById('wcwp-chatbot-welcome');
    const previewWelcome = document.getElementById('wcwp-chatbot-preview-welcome');

    function updatePreview() {
        if (previewBubble && bgColor && textColor) {
            previewBubble.style.setProperty('--wcwp-chatbot-bg', bgColor.value);
            previewBubble.style.setProperty('--wcwp-chatbot-color', textColor.value);
        }
        if (previewIcon && iconColor) {
            previewIcon.style.setProperty('--wcwp-chatbot-icon', iconColor.value);
        }
        if (welcomeInput && previewWelcome) {
            previewWelcome.textContent = welcomeInput.value;
        }
    }
    if (bgColor) bgColor.addEventListener('input', updatePreview);
    if (textColor) textColor.addEventListener('input', updatePreview);
    if (iconColor) iconColor.addEventListener('input', updatePreview);
    if (welcomeInput) welcomeInput.addEventListener('input', updatePreview);
    iconOptions.forEach(opt => {
        opt.addEventListener('click', function () {
            iconOptions.forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            if (previewIcon) previewIcon.innerHTML = opt.innerHTML;
        });
    });
});

// Upgrade Modal Logic

document.addEventListener('DOMContentLoaded', function () {
    const upgradeModal = document.getElementById('wcwp-upgrade-modal');
    const openUpgradeBtns = document.querySelectorAll('.wcwp-open-upgrade-modal');
    const closeUpgradeBtn = document.querySelector('.wcwp-upgrade-modal-close');
    openUpgradeBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            if (upgradeModal) upgradeModal.style.display = 'flex';
        });
    });
    if (closeUpgradeBtn) {
        closeUpgradeBtn.addEventListener('click', function () {
            if (upgradeModal) upgradeModal.style.display = 'none';
        });
    }
    // Close modal on background click
    if (upgradeModal) {
        upgradeModal.addEventListener('click', function (e) {
            if (e.target === upgradeModal) upgradeModal.style.display = 'none';
        });
    }
});

// Resend cart recovery attempts from admin table
document.addEventListener('DOMContentLoaded', function () {
    const resendButtons = document.querySelectorAll('.wcwp-resend-cart');
    if (!resendButtons.length) return;

    resendButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const attempt = btn.getAttribute('data-attempt');
            if (!attempt) return;
            btn.disabled = true;
            btn.textContent = 'Resending...';

            const formData = new FormData();
            formData.append('action', 'wcwp_resend_cart_recovery');
            formData.append('attempt_id', attempt);
            formData.append('nonce', (window.wcwpAdminData && wcwpAdminData.resendNonce) ? wcwpAdminData.resendNonce : '');

            fetch((window.wcwpAdminData && wcwpAdminData.ajaxUrl) ? wcwpAdminData.ajaxUrl : '', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            }).then(res => res.json())
              .then(data => {
                btn.textContent = data && data.success ? 'Resent' : 'Failed';
                if (!data || !data.success) {
                    setTimeout(() => { btn.textContent = 'Resend'; btn.disabled = false; }, 1200);
                }
              })
              .catch(() => {
                btn.textContent = 'Failed';
                setTimeout(() => { btn.textContent = 'Resend'; btn.disabled = false; }, 1200);
              });
        });
    });
}); 

// Dark Mode Toggle

document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.querySelector('.wcwp-admin-premium-wrap');
    let darkMode = localStorage.getItem('wcwp-dark-mode') === 'true';
    function applyDarkMode(on) {
        if (on) {
            document.body.classList.add('wcwp-dark-mode');
            if (wrapper) wrapper.classList.add('wcwp-dark-mode');
        } else {
            document.body.classList.remove('wcwp-dark-mode');
            if (wrapper) wrapper.classList.remove('wcwp-dark-mode');
        }
    }
    // Add toggle button
    let toggle = document.createElement('button');
    toggle.className = 'wcwp-dark-toggle';
    toggle.setAttribute('type', 'button');
    toggle.setAttribute('aria-label', 'Toggle dark mode');
    toggle.innerHTML = '<span class="wcwp-dark-icon">' + (darkMode ? '🌙' : '☀️') + '</span>';
    if (wrapper) wrapper.prepend(toggle);
    applyDarkMode(darkMode);
    toggle.addEventListener('click', function () {
        darkMode = !darkMode;
        localStorage.setItem('wcwp-dark-mode', darkMode);
        applyDarkMode(darkMode);
        // Animate icon and swap sun/moon
        const icon = toggle.querySelector('.wcwp-dark-icon');
        if (icon) icon.textContent = darkMode ? '🌙' : '☀️';
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const apiProvider = document.getElementById('wcwp_api_provider');
    const cloudFields = document.querySelectorAll('.wcwp-cloud-fields');
    function toggleCloudFields() {
        if (!apiProvider) return;
        const show = apiProvider.value === 'cloud';
        cloudFields.forEach(f => f.style.display = show ? '' : 'none');
    }
    if (apiProvider) {
        apiProvider.addEventListener('change', toggleCloudFields);
        toggleCloudFields();
    }
}); 