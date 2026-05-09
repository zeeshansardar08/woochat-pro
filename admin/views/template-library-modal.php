<?php
if (!defined('ABSPATH')) exit;

$wcwp_template_library = wcwp_get_template_library();
?>
<div id="wcwp-template-library-modal" class="wcwp-template-library-modal" style="display:none;" aria-hidden="true">
    <div class="wcwp-template-library-overlay"></div>
    <div class="wcwp-template-library-dialog" role="dialog" aria-modal="true" aria-labelledby="wcwp-template-library-title">
        <header class="wcwp-template-library-header">
            <h2 id="wcwp-template-library-title"><?php esc_html_e('Template library', 'woochat-pro'); ?></h2>
            <p class="wcwp-template-library-subtitle"><?php esc_html_e('Pick a starter and tweak it to taste. Placeholders inside curly braces are filled in automatically when the message is sent.', 'woochat-pro'); ?></p>
            <button type="button" class="wcwp-template-library-close" aria-label="<?php esc_attr_e('Close', 'woochat-pro'); ?>">&times;</button>
        </header>
        <div class="wcwp-template-library-body">
            <?php
            foreach ($wcwp_template_library as $industry_id => $industry) :
                $industry_label = isset($industry['label']) ? (string) $industry['label'] : (string) $industry_id;
                $templates = isset($industry['templates']) && is_array($industry['templates']) ? $industry['templates'] : [];
                foreach ($templates as $t) :
                    $kind = isset($t['kind']) ? (string) $t['kind'] : '';
                    $name = isset($t['name']) ? (string) $t['name'] : '';
                    $body = isset($t['body']) ? (string) $t['body'] : '';
                    if ($kind === '' || $body === '') continue;
            ?>
                <article class="wcwp-template-card" data-kind="<?php echo esc_attr($kind); ?>" data-industry="<?php echo esc_attr($industry_id); ?>">
                    <div class="wcwp-template-card-meta">
                        <span class="wcwp-template-industry"><?php echo esc_html($industry_label); ?></span>
                        <h3 class="wcwp-template-name"><?php echo esc_html($name); ?></h3>
                    </div>
                    <pre class="wcwp-template-body"><?php echo esc_html($body); ?></pre>
                    <button type="button" class="button button-primary wcwp-template-use" data-body="<?php echo esc_attr($body); ?>"><?php esc_html_e('Use this template', 'woochat-pro'); ?></button>
                </article>
            <?php
                endforeach;
            endforeach;
            ?>
        </div>
    </div>
</div>
