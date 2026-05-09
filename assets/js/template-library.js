// WooChat Pro – Template Library modal
//
// Wires the "Browse template library" button next to each message
// textarea (Order, Cart Recovery, Follow-up) to a single shared modal
// that filters its template cards to the kind requested by the
// triggering button. Clicking "Use this template" populates the
// originating textarea — admin still has to hit WP's Save Changes to
// persist.

(function () {
    function open(modal) {
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('wcwp-template-library-open');
    }

    function close(modal) {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('wcwp-template-library-open');
        modal.removeAttribute('data-target-id');
    }

    function applyFilter(modal, kind) {
        const cards = modal.querySelectorAll('.wcwp-template-card');
        let visible = 0;
        cards.forEach(function (card) {
            const match = !kind || card.getAttribute('data-kind') === kind;
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        let empty = modal.querySelector('.wcwp-template-library-empty');
        if (!empty) {
            empty = document.createElement('p');
            empty.className = 'wcwp-template-library-empty';
            empty.textContent = '';
            const body = modal.querySelector('.wcwp-template-library-body');
            if (body) body.appendChild(empty);
        }
        empty.style.display = visible === 0 ? '' : 'none';
        if (visible === 0) {
            empty.textContent =
                (window.wcwpTemplateLibraryI18n && wcwpTemplateLibraryI18n.empty) ||
                'No templates available for this section yet.';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('wcwp-template-library-modal');
        if (!modal) return;

        const closeBtn = modal.querySelector('.wcwp-template-library-close');
        const overlay = modal.querySelector('.wcwp-template-library-overlay');

        document.querySelectorAll('.wcwp-browse-templates').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const targetId = btn.getAttribute('data-target') || '';
                const kind = btn.getAttribute('data-kind') || '';
                modal.setAttribute('data-target-id', targetId);
                applyFilter(modal, kind);
                open(modal);
            });
        });

        modal.querySelectorAll('.wcwp-template-use').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const targetId = modal.getAttribute('data-target-id');
                const body = btn.getAttribute('data-body') || '';
                if (!targetId) {
                    close(modal);
                    return;
                }
                const target = document.getElementById(targetId);
                if (target) {
                    target.value = body;
                    target.dispatchEvent(new Event('change', { bubbles: true }));
                    target.focus();
                    if (target.scrollIntoView) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
                close(modal);
            });
        });

        if (closeBtn) closeBtn.addEventListener('click', function () { close(modal); });
        if (overlay) overlay.addEventListener('click', function () { close(modal); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.style.display === 'block') {
                close(modal);
            }
        });
    });
})();
