<?php if (!defined('ABSPATH')) exit; ?>

<div id="wcwp-chatbot" style="position:fixed;bottom:30px;right:30px;z-index:9999;">
    <div id="wcwp-chat-window" style="display:none;background:#fff;border-radius:10px;width:300px;box-shadow:0 0 10px rgba(0,0,0,0.3);padding:15px;">
        <h4 style="margin-top:0;">Chat with us 🤖</h4>
        <input type="text" id="wcwp-user-input" placeholder="Ask a question..." 
               style="width:100%;margin-top:10px;padding:6px;border:1px solid #ccc;border-radius:5px;" />
        <div id="wcwp-chat-response" style="margin-top:10px;font-size:14px;"></div>
        <a id="wcwp-send-wa" href="#" target="_blank" 
           style="display:none;margin-top:10px;display:inline-block;background:#25D366;color:#fff;padding:8px 10px;border-radius:5px;text-decoration:none;">
            Send via WhatsApp
        </a>
    </div>
    <button id="wcwp-toggle-chat" 
            style="background:#25D366;color:#fff;border:none;padding:10px 15px;border-radius:50px;cursor:pointer;">
        💬
    </button>
</div>
