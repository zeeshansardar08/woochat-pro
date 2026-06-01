jQuery(document).ready(function ($) {
    // Toggle chatbot visibility
    $('#zignites-chat-toggle-chat').click(function () {
        $('#zignites-chat-chat-window').toggle();
    });

    // Unicode-aware: \p{L}\p{N} keeps non-Latin FAQ content (Arabic, CJK, etc.)
    // from collapsing to empty tokens.
    function zignitesChatTokenize(s) {
        if (!s) return [];
        return String(s)
            .toLowerCase()
            .replace(/[^\p{L}\p{N}\s]/gu, ' ')
            .split(/\s+/)
            .filter(function (t) { return t.length >= 2; });
    }

    function zignitesChatScoreFaq(userSet, faqTokens) {
        if (faqTokens.length === 0) return { score: 0, hits: 0 };
        var hits = 0;
        for (var i = 0; i < faqTokens.length; i++) {
            if (userSet.has(faqTokens[i])) hits++;
        }
        return { score: hits / faqTokens.length, hits: hits };
    }

    function zignitesChatPickAgentPhone() {
        var agents = zignites_chat_chatbot_obj.agents;
        if (!Array.isArray(agents) || agents.length === 0) return '';
        var mode = zignites_chat_chatbot_obj.routing_mode;
        if (mode === 'random' && agents.length > 1) {
            return String(agents[Math.floor(Math.random() * agents.length)].phone || '');
        }
        return String(agents[0].phone || '');
    }

    function zignitesChatRenderChatReply(text) {
        $('#zignites-chat-chat-response').text(text);
        var encoded = encodeURIComponent(text);
        // Routes the click straight to the chosen agent's WhatsApp when one
        // is configured; falls back to the empty wa.me picker otherwise so
        // sites that haven't set up agents keep the legacy behaviour.
        var phone = zignitesChatPickAgentPhone();
        var url = 'https://wa.me/' + (phone || '') + '?text=' + encoded;
        $('#zignites-chat-send-wa').attr('href', url).show();
    }

    // 0.6 keeps obvious matches while rejecting single-token coincidences
    // against long FAQs ("shipping" alone shouldn't pick up "how long does
    // international shipping take").
    var ZIGNITES_CHAT_MATCH_THRESHOLD = 0.6;

    // Handle user input
    $('#zignites-chat-user-input').on('keypress', function (e) {
        if (e.which !== 13) return;

        var question = $(this).val();
        var noAnswer = zignites_chat_chatbot_obj.noAnswerText || "Sorry, I don't have an answer for that.";
        var pairs = Array.isArray(zignites_chat_chatbot_obj.faq_pairs) ? zignites_chat_chatbot_obj.faq_pairs : [];

        var userTokens = zignitesChatTokenize(question);
        var userSet = new Set(userTokens);

        var best = { score: 0, hits: 0, answer: null };
        for (var i = 0; i < pairs.length; i++) {
            var faqTokens = zignitesChatTokenize(pairs[i].question);
            var result = zignitesChatScoreFaq(userSet, faqTokens);
            // Tie-break on hit count so a 2-token FAQ matched fully
            // ("refund policy") outranks a 1-token FAQ ("refund") at score 1.0.
            if (result.score > best.score || (result.score === best.score && result.hits > best.hits)) {
                best = { score: result.score, hits: result.hits, answer: pairs[i].answer };
            }
        }

        if (best.score >= ZIGNITES_CHAT_MATCH_THRESHOLD && best.answer) {
            zignitesChatRenderChatReply(best.answer);
            return;
        }

        zignitesChatRenderChatReply(noAnswer);
    });
});
