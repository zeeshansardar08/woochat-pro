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
    toggle.className = 'button wcwp-dark-toggle';
    toggle.style.float = 'right';
    toggle.style.marginTop = '-48px';
    toggle.innerHTML = '<span class="dashicons dashicons-lightbulb"></span> Dark Mode';
    if (wrapper) wrapper.prepend(toggle);
    applyDarkMode(darkMode);
    toggle.addEventListener('click', function () {
        darkMode = !darkMode;
        localStorage.setItem('wcwp-dark-mode', darkMode);
        applyDarkMode(darkMode);
    });
}); 