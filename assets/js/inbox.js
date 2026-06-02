// Zignites Chat – Two-way team Inbox (I3: list + thread view + polling).

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('zignites-chat-inbox');
        if (!root) return;

        var config = (typeof zignitesChatInbox !== 'undefined') ? zignitesChatInbox : {};
        var i18n = config.i18n || {};
        var ajaxUrl = config.ajaxUrl || '';
        var nonce = config.nonce || '';
        var pollInterval = config.pollInterval || 15000;

        var listEl = document.getElementById('zignites-chat-inbox-threads');
        var searchEl = document.getElementById('zignites-chat-inbox-search');
        var panelEmpty = document.getElementById('zignites-chat-inbox-panel-empty');
        var threadView = document.getElementById('zignites-chat-inbox-thread-view');
        var titleEl = document.getElementById('zignites-chat-inbox-thread-title');
        var phoneEl = document.getElementById('zignites-chat-inbox-thread-phone');
        var windowEl = document.getElementById('zignites-chat-inbox-window');
        var messagesEl = document.getElementById('zignites-chat-inbox-messages');

        var activeId = 0;
        var lastMessageId = 0;
        var searchTimer = null;

        function ajaxGet(action, params) {
            var url = ajaxUrl + '?action=' + encodeURIComponent(action) + '&nonce=' + encodeURIComponent(nonce);
            Object.keys(params || {}).forEach(function (k) {
                url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
            });
            return fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); });
        }

        // --- Thread list -----------------------------------------------------

        function renderThreads(threads) {
            listEl.innerHTML = '';
            if (!threads || !threads.length) {
                var empty = document.createElement('li');
                empty.className = 'zignites-chat-inbox-empty';
                empty.textContent = i18n.noThreads || '';
                listEl.appendChild(empty);
                return;
            }
            threads.forEach(function (t) {
                var li = document.createElement('li');
                li.className = 'zignites-chat-inbox-thread' + (t.unread > 0 ? ' is-unread' : '') + (t.id === activeId ? ' is-active' : '');
                li.setAttribute('data-id', String(t.id));

                var name = document.createElement('span');
                name.className = 'zignites-chat-inbox-thread-name';
                name.textContent = t.name || t.phone || (i18n.unknown || '');
                li.appendChild(name);

                if (t.unread > 0) {
                    var badge = document.createElement('span');
                    badge.className = 'zignites-chat-inbox-badge';
                    badge.textContent = String(t.unread);
                    li.appendChild(badge);
                }

                var excerpt = document.createElement('span');
                excerpt.className = 'zignites-chat-inbox-thread-excerpt';
                excerpt.textContent = t.excerpt || '';
                li.appendChild(excerpt);

                var time = document.createElement('span');
                time.className = 'zignites-chat-inbox-thread-time';
                time.textContent = t.last_message_at || '';
                li.appendChild(time);

                li.addEventListener('click', function () { openThread(t.id); });
                listEl.appendChild(li);
            });
        }

        function loadThreads() {
            var params = {};
            if (searchEl && searchEl.value) params.search = searchEl.value;
            return ajaxGet('zignites_chat_inbox_threads', params).then(function (res) {
                if (res && res.success) {
                    renderThreads(res.data.threads);
                }
            }).catch(function () { /* transient network error — next poll retries */ });
        }

        // --- Thread view -----------------------------------------------------

        function messageEl(m) {
            var wrap = document.createElement('div');
            wrap.className = 'zignites-chat-inbox-message is-' + (m.direction === 'in' ? 'in' : 'out');

            var body = document.createElement('div');
            body.className = 'zignites-chat-inbox-message-body';
            body.textContent = m.body || '';
            wrap.appendChild(body);

            var meta = document.createElement('div');
            meta.className = 'zignites-chat-inbox-message-meta';
            var who = (m.direction === 'in') ? (i18n.customer || '') : (i18n.you || '');
            meta.textContent = who + ' · ' + (m.created_at || '');
            wrap.appendChild(meta);
            return wrap;
        }

        function appendMessages(messages) {
            messages.forEach(function (m) {
                messagesEl.appendChild(messageEl(m));
                if (m.id > lastMessageId) lastMessageId = m.id;
            });
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function renderWindow(open) {
            windowEl.className = 'zignites-chat-inbox-window ' + (open ? 'is-open' : 'is-closed');
            windowEl.textContent = open ? (i18n.windowOpen || '') : (i18n.windowClosed || '');
        }

        function openThread(id) {
            activeId = id;
            lastMessageId = 0;
            panelEmpty.style.display = 'none';
            threadView.style.display = '';
            messagesEl.innerHTML = '<p class="zignites-chat-inbox-loading">' + (i18n.loading || '') + '</p>';

            ajaxGet('zignites_chat_inbox_thread', { conversation_id: id }).then(function (res) {
                if (!res || !res.success) {
                    messagesEl.innerHTML = '<p class="zignites-chat-inbox-error">' + (i18n.loadError || '') + '</p>';
                    return;
                }
                var t = res.data.thread;
                titleEl.textContent = t.name || t.phone || (i18n.unknown || '');
                phoneEl.textContent = t.phone || '';
                renderWindow(!!t.windowOpen);

                messagesEl.innerHTML = '';
                if (!res.data.messages.length) {
                    messagesEl.innerHTML = '<p class="zignites-chat-inbox-empty">' + (i18n.noMessages || '') + '</p>';
                } else {
                    appendMessages(res.data.messages);
                }
                // Opening clears the unread badge server-side; refresh the list.
                loadThreads();
            }).catch(function () {
                messagesEl.innerHTML = '<p class="zignites-chat-inbox-error">' + (i18n.loadError || '') + '</p>';
            });
        }

        function pollActiveThread() {
            if (!activeId) return;
            ajaxGet('zignites_chat_inbox_thread', { conversation_id: activeId, after_id: lastMessageId }).then(function (res) {
                if (res && res.success && res.data.messages.length) {
                    // Drop any "no messages" placeholder before appending.
                    var ph = messagesEl.querySelector('.zignites-chat-inbox-empty');
                    if (ph) ph.remove();
                    appendMessages(res.data.messages);
                    renderWindow(!!res.data.thread.windowOpen);
                }
            }).catch(function () { /* ignore — next tick retries */ });
        }

        // --- Wiring ----------------------------------------------------------

        if (searchEl) {
            searchEl.addEventListener('input', function () {
                if (searchTimer) clearTimeout(searchTimer);
                searchTimer = setTimeout(loadThreads, 300);
            });
        }

        // Bind initial server-rendered rows so the page works before the first poll.
        Array.prototype.forEach.call(listEl.querySelectorAll('.zignites-chat-inbox-thread'), function (li) {
            li.addEventListener('click', function () { openThread(parseInt(li.getAttribute('data-id'), 10)); });
        });

        window.setInterval(function () {
            loadThreads();
            pollActiveThread();
        }, pollInterval);
    });
})();
