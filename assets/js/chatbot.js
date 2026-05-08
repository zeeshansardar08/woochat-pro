jQuery(document).ready(function ($) {
    // Toggle chatbot visibility
    $('#wcwp-toggle-chat').click(function () {
        $('#wcwp-chat-window').toggle();
    });

    // Unicode-aware: \p{L}\p{N} keeps non-Latin FAQ content (Arabic, CJK, etc.)
    // from collapsing to empty tokens.
    function wcwpTokenize(s) {
        if (!s) return [];
        return String(s)
            .toLowerCase()
            .replace(/[^\p{L}\p{N}\s]/gu, ' ')
            .split(/\s+/)
            .filter(function (t) { return t.length >= 2; });
    }

    function wcwpScoreFaq(userSet, faqTokens) {
        if (faqTokens.length === 0) return { score: 0, hits: 0 };
        var hits = 0;
        for (var i = 0; i < faqTokens.length; i++) {
            if (userSet.has(faqTokens[i])) hits++;
        }
        return { score: hits / faqTokens.length, hits: hits };
    }

    function wcwpRenderChatReply(text) {
        $('#wcwp-chat-response').text(text);
        var encoded = encodeURIComponent(text);
        $('#wcwp-send-wa').attr('href', 'https://wa.me/?text=' + encoded).show();
    }

    // 0.6 keeps obvious matches while rejecting single-token coincidences
    // against long FAQs ("shipping" alone shouldn't pick up "how long does
    // international shipping take").
    var WCWP_MATCH_THRESHOLD = 0.6;

    // Handle user input
    $('#wcwp-user-input').on('keypress', function (e) {
        if (e.which !== 13) return;

        var question = $(this).val();
        var noAnswer = wcwp_chatbot_obj.noAnswerText || "Sorry, I don't have an answer for that.";
        var pairs = Array.isArray(wcwp_chatbot_obj.faq_pairs) ? wcwp_chatbot_obj.faq_pairs : [];

        var userTokens = wcwpTokenize(question);
        var userSet = new Set(userTokens);

        var best = { score: 0, hits: 0, answer: null };
        for (var i = 0; i < pairs.length; i++) {
            var faqTokens = wcwpTokenize(pairs[i].question);
            var result = wcwpScoreFaq(userSet, faqTokens);
            // Tie-break on hit count so a 2-token FAQ matched fully
            // ("refund policy") outranks a 1-token FAQ ("refund") at score 1.0.
            if (result.score > best.score || (result.score === best.score && result.hits > best.hits)) {
                best = { score: result.score, hits: result.hits, answer: pairs[i].answer };
            }
        }

        if (best.score >= WCWP_MATCH_THRESHOLD && best.answer) {
            wcwpRenderChatReply(best.answer);
            return;
        }

        var gpt = wcwp_chatbot_obj.gpt;
        if (gpt && gpt.enabled && question) {
            // Show transient placeholder while GPT call is in flight; the
            // WhatsApp send button is hidden so users don't share the
            // placeholder text.
            $('#wcwp-chat-response').text(gpt.thinking || 'Thinking…');
            $('#wcwp-send-wa').hide();

            $.ajax({
                url: gpt.url,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: gpt.action,
                    nonce: gpt.nonce,
                    question: question
                }
            }).done(function (res) {
                var reply = (res && res.success && res.data && res.data.reply) ? res.data.reply : noAnswer;
                wcwpRenderChatReply(reply);
            }).fail(function () {
                wcwpRenderChatReply(noAnswer);
            });
            return;
        }

        wcwpRenderChatReply(noAnswer);
    });
});
