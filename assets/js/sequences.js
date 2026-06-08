// Zignites Chat – Drip & automation sequences editor.
// Adds/removes sequence cards and step rows client-side. The whole option is
// re-sanitized server-side on save, so removed cards (not submitted) are dropped.

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var list = document.getElementById('zignites-chat-sequences-list');
        if (!list) return;

        var cfg = (typeof zignitesChatSequences !== 'undefined') ? zignitesChatSequences : {};
        var i18n = cfg.i18n || {};
        var cardTpl = document.getElementById('zignites-chat-seq-card-tpl');
        var stepTpl = document.getElementById('zignites-chat-seq-step-tpl');
        var newSeqCounter = 1000; // high, collision-free indices for added cards

        // Reflect the selected trigger's placeholders under the dropdown.
        function refreshPlaceholders(card) {
            var select = card.querySelector('.zignites-chat-seq-trigger');
            var hint = card.querySelector('.zignites-chat-seq-placeholders');
            if (!select || !hint) return;
            var opt = select.options[select.selectedIndex];
            var ph = opt ? (opt.getAttribute('data-placeholders') || '') : '';
            hint.textContent = ph ? ('Placeholders: ' + ph) : '';
        }

        function addStep(card) {
            if (!stepTpl) return;
            var si = card.getAttribute('data-seq-index');
            var nextStep = parseInt(card.getAttribute('data-next-step') || '0', 10);
            var html = stepTpl.innerHTML
                .split('__SI__').join(si)
                .split('__STI__').join(String(nextStep));
            card.setAttribute('data-next-step', String(nextStep + 1));
            var steps = card.querySelector('.zignites-chat-seq-steps');
            var wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            while (wrap.firstChild) steps.appendChild(wrap.firstChild);
        }

        function wireCard(card) {
            refreshPlaceholders(card);
            var trigger = card.querySelector('.zignites-chat-seq-trigger');
            if (trigger) trigger.addEventListener('change', function () { refreshPlaceholders(card); });
        }

        // Event delegation for the dynamic buttons.
        document.addEventListener('click', function (e) {
            var t = e.target;
            if (!(t instanceof Element)) return;

            if (t.classList.contains('zignites-chat-seq-add-step')) {
                e.preventDefault();
                addStep(t.closest('.zignites-chat-seq-card'));
            } else if (t.classList.contains('zignites-chat-seq-remove-step')) {
                e.preventDefault();
                var step = t.closest('.zignites-chat-seq-step');
                if (step) step.parentNode.removeChild(step);
            } else if (t.classList.contains('zignites-chat-seq-remove')) {
                e.preventDefault();
                if (window.confirm(i18n.removeSequence || 'Remove this sequence?')) {
                    var card = t.closest('.zignites-chat-seq-card');
                    if (card) card.parentNode.removeChild(card);
                }
            }
        });

        var addBtn = document.getElementById('zignites-chat-add-sequence');
        if (addBtn && cardTpl) {
            addBtn.addEventListener('click', function () {
                var si = 'new_' + (newSeqCounter++);
                var html = cardTpl.innerHTML.split('__SI__').join(si);
                var wrap = document.createElement('div');
                wrap.innerHTML = html.trim();
                var card = wrap.querySelector('.zignites-chat-seq-card');
                if (card) {
                    list.appendChild(card);
                    wireCard(card);
                }
            });
        }

        // Wire the server-rendered cards.
        var cards = list.querySelectorAll('.zignites-chat-seq-card');
        for (var i = 0; i < cards.length; i++) wireCard(cards[i]);
    });
})();
