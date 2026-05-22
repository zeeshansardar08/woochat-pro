<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View partial: these variables are scoped to the render function that includes this file, not the global namespace.

$zignites_chat_template_library = zignites_chat_get_template_library();
?>
<div id="zignites-chat-template-library-modal" class="zignites-chat-template-library-modal" style="display:none;" aria-hidden="true">
    <div class="zignites-chat-template-library-overlay"></div>
    <div class="zignites-chat-template-library-dialog" role="dialog" aria-modal="true" aria-labelledby="zignites-chat-template-library-title">
        <header class="zignites-chat-template-library-header">
            <h2 id="zignites-chat-template-library-title"><?php esc_html_e('Template library', 'zignites-chat'); ?></h2>
            <p class="zignites-chat-template-library-subtitle"><?php esc_html_e('Pick a starter and tweak it to taste. Placeholders inside curly braces are filled in automatically when the message is sent.', 'zignites-chat'); ?></p>
            <button type="button" class="zignites-chat-template-library-close" aria-label="<?php esc_attr_e('Close', 'zignites-chat'); ?>">&times;</button>
        </header>
        <div class="zignites-chat-template-library-body">
            <?php
            foreach ($zignites_chat_template_library as $industry_id => $industry) :
                $industry_label = isset($industry['label']) ? (string) $industry['label'] : (string) $industry_id;
                $templates = isset($industry['templates']) && is_array($industry['templates']) ? $industry['templates'] : [];
                foreach ($templates as $t) :
                    $kind = isset($t['kind']) ? (string) $t['kind'] : '';
                    $name = isset($t['name']) ? (string) $t['name'] : '';
                    $body = isset($t['body']) ? (string) $t['body'] : '';
                    if ($kind === '' || $body === '') continue;
            ?>
                <article class="zignites-chat-template-card" data-kind="<?php echo esc_attr($kind); ?>" data-industry="<?php echo esc_attr($industry_id); ?>">
                    <div class="zignites-chat-template-card-meta">
                        <span class="zignites-chat-template-industry"><?php echo esc_html($industry_label); ?></span>
                        <h3 class="zignites-chat-template-name"><?php echo esc_html($name); ?></h3>
                    </div>
                    <pre class="zignites-chat-template-body"><?php echo esc_html($body); ?></pre>
                    <button type="button" class="button button-primary zignites-chat-template-use" data-body="<?php echo esc_attr($body); ?>"><?php esc_html_e('Use this template', 'zignites-chat'); ?></button>
                </article>
            <?php
                endforeach;
            endforeach;
            ?>
        </div>
    </div>
</div>
