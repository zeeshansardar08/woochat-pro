jQuery(document).ready(function ($) {
    // Toggle chatbot visibility
    $('#wcwp-toggle-chat').click(function () {
        $('#wcwp-chat-window').toggle();
    });

    // Handle user input
    $('#wcwp-user-input').on('keypress', function (e) {
        if (e.which === 13) {
            const question = $(this).val().toLowerCase();
            let reply = "Sorry, I don't have an answer for that.";
            let pairs = wcwp_chatbot_obj.faq_pairs;

            for (let i = 0; i < pairs.length; i++) {
                if (question.includes(pairs[i].question.toLowerCase())) {
                    reply = pairs[i].answer;
                    break;
                }
            }

            $('#wcwp-chat-response').text(reply);

            const encoded = encodeURIComponent(reply);
            $('#wcwp-send-wa').attr('href', `https://wa.me/?text=${encoded}`).show();
        }
    });
});
