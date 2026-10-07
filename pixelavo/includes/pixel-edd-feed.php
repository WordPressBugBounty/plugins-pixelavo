<?php

namespace Pixelavo;

/* Exit if accessed directly */
if (!defined('ABSPATH')) {
    exit;
}

class PixelEddFeed{
    
    private static $_instance = null;

    /**
     * Instance.
     * Initializes a singleton instance.
     * @return self class
     */
    static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    function __construct() {
        add_action('init', [$this, 'init_feed']);
        add_filter('feed_content_type', [$this, 'feed_content_type'], 10, 2);
    }

    function feed_content_type($content_type, $type) {
        if ('pixelavo-edd' === $type) {
            return feed_content_type( 'rss-http' );
        }
        return $content_type;
    }
    
    /**
     * Feed.
     * Run when user search for any product and go to `search` page.
     * @return void
     */
    function init_feed() {
        add_feed( 'pixelavo-edd', [$this, 'pixel_feed'] );
    }

    function pixel_feed() {

        /**
         * Settings
         */
        $settings = get_option('pixelavo_settings', []);
        if (!is_array($settings)) {
            $settings = [];
        }
        $exclude_categories = isset($settings['edd_exclude_categories']) ? (array) $settings['edd_exclude_categories'] : [];
        $exclude_tags = isset($settings['edd_exclude_tags']) ? (array) $settings['edd_exclude_tags'] : [];
        $include_categories = isset($settings['edd_include_categories']) ? array_filter(array_map('absint', (array) $settings['edd_include_categories'])) : [];

        /**
         * Products
         */
        $args = [
            'fields'    => 'ids',
            'post_type' => 'download',
            'post_status' => 'publish',
            'posts_per_page'   => -1,
            'orderby' => 'ID',
            'order' => 'ASC',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            'tax_query' => [
                'relation' => 'AND',
                [
                    'taxonomy' => 'download_category',
                    'field' => 'term_id',
                    'terms' => $exclude_categories,
                    'operator' => 'NOT IN',
                ],
                [
                    'taxonomy' => 'download_tag',
                    'field' => 'term_id',
                    'terms' => $exclude_tags,
                    'operator' => 'NOT IN',
                ]
            ],
        ];
        // "Include Categories": when set, only downloads in these categories are kept.
        // Exclude rules above still apply on top (the clauses are ANDed).
        if (!empty($include_categories)) {
            // Drop ids of categories that no longer exist; with none left, treat it as "not set".
            $existing = get_terms(['taxonomy' => 'download_category', 'include' => $include_categories, 'fields' => 'ids', 'hide_empty' => false]);
            $include_categories = is_array($existing) ? array_map('absint', $existing) : [];
        }
        if (!empty($include_categories)) {
            $args['tax_query'][] = [
                'taxonomy' => 'download_category',
                'field' => 'term_id',
                'terms' => $include_categories,
                'operator' => 'IN',
            ];
        }
        $download_ids = get_posts($args);

        /**
         * Feed Namespace
         */
        $namespace = 'http://base.google.com/ns/1.0';

        /**
         * Feed RSS
         */
        $rss = new \SimpleXMLElement("<?xml version='1.0' encoding='UTF-8'?><rss xmlns:g='{$namespace}'></rss>");
        $rss->addAttribute('version', '2.0');

        /**
         * Feed Channel
         */
        $channel = $rss->addChild('channel');
        $channel->addChild('title', $this->xml_text(get_bloginfo('name')));
        if(!empty(get_bloginfo('description'))) {
            $channel->addChild('description', $this->xml_text(get_bloginfo('description')));
        }
        $channel->addChild('link', $this->xml_text(get_bloginfo('url')));

        /**
         * Feed Item
         */
        foreach ($download_ids as $id) {
            $download = edd_get_download($id);
            if (!$download) {
                continue;
            }
            $dowl['id'] = ($settings['edd_product_identifier'] ?? '') == 'post_id' ? $download->ID : $download->get_sku();
            $dowl['title'] = $download->post_title;
            $dowl['desc'] = ($settings['edd_description_field'] ?? '') == 'description' ? $download->post_content : $download->post_excerpt;
            $dowl['link'] = get_permalink($dowl['id']);
            $dowl['image_link'] = $this->get_image_link($dowl['id']);
            $dowl['brand'] = $this->get_brand($dowl['id']);
            $dowl['condition'] = 'new';
            $dowl['availability'] = 'in stock';
            $dowl['price'] = $download->get_price() . ' ' . edd_get_option('currency', 'USD');
            $dowl['google_product_category'] = '5032';
            $this->feed_item($channel, $dowl, $namespace);
        }
        echo $rss->asXML(); // phpcs:ignore
    }

    /**
     * Prepare a value for SimpleXML::addChild().
     *
     * addChild() does not escape "&", so a bare "&" (or an HTML-only entity such as
     * &nbsp; / &ndash;) makes libxml raise a warning on some PHP/libxml versions and
     * the element comes out empty. Decode entities to real characters first, then
     * escape once. Text that already worked ("&amp;", "&#8217;") is unchanged.
     */
    private function xml_text($value) {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Feed item function
     */
    function feed_item($channel, $download, $namespace) {

        $item = $channel->addChild('item');
        $item->addChild("g:id", $this->xml_text($download['id']), $namespace);
        $item->addChild("g:title", $this->xml_text($download['title']), $namespace);
        if(!empty($download['desc'])) {
            $item->addChild("g:description", $this->xml_text($download['desc']), $namespace);
        }
        $item->addChild("g:availability", $this->xml_text($download['availability']), $namespace);
        $item->addChild("g:condition", $this->xml_text($download['condition']), $namespace);
        if($download['price']) {
            $item->addChild("g:price", $this->xml_text($download['price']), $namespace);
        }
        $item->addChild("g:link", $this->xml_text($download['link']), $namespace);
        if($download['image_link']) {
            $item->addChild("g:image_link", $this->xml_text($download['image_link']), $namespace);
        }
        if(!empty($download['brand'])) {
            $item->addChild("g:brand", $this->xml_text($download['brand']), $namespace);
        }
        $item->addChild("g:google_product_category", $this->xml_text($download['google_product_category']), $namespace);
    }

    /**
     * Get product thumbnail image link
     */
    function get_image_link($download_id, $variation = null) {
        $image = wp_get_attachment_image_src( get_post_thumbnail_id( $download_id ), 'single-post-thumbnail' );
        if($image) {
            return $image[0];
        }
        return $image;
    }

    /**
     * Get brand name
     */
    function get_brand($download_id) {
        $settings = get_option('pixelavo_settings');
        return isset($settings['edd_product_feed_brand']) && !empty($settings['edd_product_feed_brand']) ? $settings['edd_product_feed_brand'] : '';
    }

}

/**
 * Initialize PixelEddFeed Class
 */
PixelEddFeed::instance();
