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