<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
ob_start();
?>
<div class="pixelavo-opt-admin-sidebar">
    <?php do_action('pixelavo_admin_sidebar_notices') ?>
    <div class="pixelavo-opt-get-pro">
        <h3 class="pixelavo-opt-get-pro-title"><?php esc_html_e('Get Pixelavo Pro', 'pixelavo')?></h3>
        <ul>
            <li><?php esc_html_e('Multiple Pixel Support', 'pixelavo') ?></li>
            <li><?php esc_html_e('Multiple Custom Event Support', 'pixelavo') ?></li>
            <li><?php esc_html_e('WooCommerce Integration', 'pixelavo') ?></li>
            <li><?php esc_html_e('Exclude Bouncing Visitors', 'pixelavo') ?></li>
            <li><?php esc_html_e('Advanced Matching to Boost Meta Conversions', 'pixelavo') ?></li>
            <li><?php esc_html_e('Additional User Information', 'pixelavo') ?></li>
            <li><?php esc_html_e('Additional Purchase Information', 'pixelavo') ?></li>
        </ul>
        <a href="https://pixelavo.com/pricing/#pixelavo-pricing" class="button pixelavo-opt-get-pro-btn" target="_blank"><?php esc_html_e('Get Pro Now', 'pixelavo') ?></a>
    </div>

    <div class="pixelavo-opt-support">
        <img src="<?php echo esc_url(PIXELAVO_PL_URL.'admin/settings-panel/assets/images/icons/customer-service.png'); ?>" alt="<?php echo esc_attr__( 'Support And Feedback', 'pixelavo' ); ?>" width="65" height="65">
        <h3 class="pixelavo-opt-support-title"><?php esc_html_e('Support And Feedback', 'pixelavo')?></h3>
        <p><?php esc_html_e('If you have any questions, concerns, or feedback, please do not hesitate to reach out to us. We are always available and ready to assist you with your needs. Thank you for choosing our products and services, and we look forward to hearing from you soon!', 'pixelavo')?></p>
        <a href="https://hasthemes.com/contact-us/" class="button pixelavo-opt-support-btn" target="_blank"><?php esc_html_e('Get Support', 'pixelavo') ?></a>
    </div>

</div>
<?php echo wp_kses_post(apply_filters('pixelavo_admin_sidebar', ob_get_clean() )); ?>