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
        var replyEl = document.getElementById('zignites-chat-inbox-reply');
        var sendEl = document.getElementById('zignites-chat-inbox-send');
        var composerNote = document.getElementById('zignites-chat-inbox-composer-note');
        var filterEl = document.getElementById('zignites-chat-inbox-filter');
        var assigneeEl = document.getElementById('zignites-chat-inbox-assignee');
        var claimEl = document.getElementById('zignites-chat-inbox-claim');
        var assignSelect = document.getElementById('zignites-chat-inbox-assign-select');

        var agents = config.agents || [];
        var currentUser = parseInt(config.currentUser, 10) || 0;

        var activeId = 0;
        var activeAgentId = 0;
        var lastMessageId = 0;
        var windowOpen = false;
        var searchTimer = null;

        function ajaxGet(action, params) {
            var url = ajaxUrl + '?action=' + encodeURIComponent(action) + '&nonce=' + encodeURIComponent(nonce);
            Object.keys(params || {}).forEach(function (k) {
                url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
            });
            return fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); });
        }

        function ajaxPost(action, data) {
            var body = 'action=' + encodeURIComponent(action) + '&nonce=' + encodeURIComponent(nonce);
            Object.keys(data || {}).forEach(function (k) {
                body += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
            });
            return fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            }).then(function (r) { return r.json(); });
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

                if (t.agentName) {
                    var who = document.createElement('span');
                    who.className = 'zignites-chat-inbox-thread-agent';
                    who.textContent = '@ ' + t.agentName;
                    li.appendChild(who);
                }

                li.addEventListener('click', function () { openThread(t.id); });
                listEl.appendChild(li);
            });
        }

        function loadThreads() {
            var params = {};
            if (searchEl && searchEl.value) params.search = searchEl.value;
            if (filterEl && filterEl.value) params.scope = filterEl.value;
            return ajaxGet('zignites_chat_inbox_threads', params).then(function (res) {
                if (res && res.success) {
                    renderThreads(res.data.threads);
                }
            }).catch(function () { /* transient network error — next poll retries */ });
        }

        // --- Assignment ------------------------------------------------------

        function renderAssignment(agentId, agentName) {
            activeAgentId = parseInt(agentId, 10) || 0;
            if (assigneeEl) {
                assigneeEl.textContent = (i18n.assignedTo || '') + ': ' + (agentName || (i18n.unassigned || ''));
            }
            if (claimEl) {
                // Hide Claim when the current user already owns the thread.
                claimEl.textContent = i18n.claim || '';
                claimEl.style.display = (currentUser && activeAgentId === currentUser) ? 'none' : '';
            }
            if (assignSelect) assignSelect.value = String(activeAgentId);
        }

        function buildAssignSelect() {
            if (!assignSelect) return;
            assignSelect.innerHTML = '';
            var unassigned = document.createElement('option');
            unassigned.value = '0';
            unassigned.textContent = i18n.unassigned || '';
            assignSelect.appendChild(unassigned);
            agents.forEach(function (a) {
                var opt = document.createElement('option');
                opt.value = String(a.id);
                opt.textContent = a.name;
                assignSelect.appendChild(opt);
            });
        }

        function assign(agentId) {
            if (!activeId) return;
            ajaxPost('zignites_chat_inbox_assign', { conversation_id: activeId, agent_id: agentId }).then(function (res) {
                if (res && res.success) {
                    renderAssignment(res.data.agent_id, res.data.agentName);
                    loadThreads();
                }
            }).catch(function () { /* ignore */ });
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
            windowOpen = !!open;
            windowEl.className = 'zignites-chat-inbox-window ' + (open ? 'is-open' : 'is-closed');
            windowEl.textContent = open ? (i18n.windowOpen || '') : (i18n.windowClosed || '');
            // Disable the composer when the 24h window has closed.
            if (replyEl) replyEl.disabled = !open;
            if (sendEl) sendEl.disabled = !open;
            if (composerNote) composerNote.textContent = open ? '' : (i18n.windowClosedNote || '');
        }

        function sendReply() {
            if (!activeId || !windowOpen) return;
            var text = replyEl ? replyEl.value.trim() : '';
            if (!text) {
                if (composerNote) composerNote.textContent = i18n.replyEmpty || '';
                return;
            }
            sendEl.disabled = true;
            sendEl.textContent = i18n.sending || '';
            if (composerNote) composerNote.textContent = '';

            ajaxPost('zignites_chat_inbox_reply', { conversation_id: activeId, body: text }).then(function (res) {
                sendEl.textContent = i18n.send || '';
                if (!res || !res.success) {
                    var msg = (res && res.data && res.data.message) ? res.data.message : (i18n.replyError || '');
                    if (composerNote) composerNote.textContent = msg;
                    // A closed window comes back as windowOpen:false — reflect it.
                    if (res && res.data && res.data.windowOpen === false) {
                        renderWindow(false);
                    } else {
                        sendEl.disabled = false;
                    }
                    return;
                }
                replyEl.value = '';
                sendEl.disabled = false;
                if (res.data.message && res.data.message.id) {
                    var ph = messagesEl.querySelector('.zignites-chat-inbox-empty');
                    if (ph) ph.remove();
                    appendMessages([res.data.message]);
                }
                loadThreads();
            }).catch(function () {
                sendEl.textContent = i18n.send || '';
                sendEl.disabled = false;
                if (composerNote) composerNote.textContent = i18n.replyError || '';
            });
        }

        function openThread(id) {
            activeId = id;
            lastMessageId = 0;
            panelEmpty.style.display = 'none';
            threadView.style.display = '';
            if (replyEl) replyEl.value = '';
            if (composerNote) composerNote.textContent = '';
            messagesEl.innerHTML = '<p class="zignites-chat-inbox-loading">' + (i18n.loading || '') + '</p>';

            ajaxGet('zignites_chat_inbox_thread', { conversation_id: id }).then(function (res) {
                if (!res || !res.success) {
                    messagesEl.innerHTML = '<p class="zignites-chat-inbox-error">' + (i18n.loadError || '') + '</p>';
                    return;
                }
                var t = res.data.thread;
                titleEl.textContent = t.name || t.phone || (i18n.unknown || '');
                phoneEl.textContent = t.phone || '';
                renderAssignment(t.agent_id, t.agentName);
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
        if (filterEl) {
            filterEl.addEventListener('change', loadThreads);
        }

        buildAssignSelect();
        if (claimEl) {
            claimEl.addEventListener('click', function () { assign(currentUser); });
        }
        if (assignSelect) {
            assignSelect.addEventListener('change', function () { assign(parseInt(assignSelect.value, 10) || 0); });
        }

        if (sendEl) {
            sendEl.addEventListener('click', sendReply);
        }
        if (replyEl) {
            // Ctrl/Cmd+Enter sends.
            replyEl.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    sendReply();
                }
            });
        }

        // Bind initial server-rendered rows so the page works before the first poll.
        Array.prototype.forEach.call(listEl.querySelectorAll('.zignites-chat-inbox-thread'), function (li) {
            li.addEventListener('click', function () { openThread(parseInt(li.getAttribute('data-id'), 10)); });
        });

        // Refresh once on load so JS-rendered rows include assignee names.
        loadThreads();

        window.setInterval(function () {
            loadThreads();
            pollActiveThread();
        }, pollInterval);
    });
})();
