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
        $settings = get_option('pixelavo_settings');
        $exclude_categories = array_key_exists('edd_exclude_categories', $settings) ? $settings['edd_exclude_categories'] : [];
        $exclude_tags = array_key_exists('edd_exclude_tags', $settings) ? $settings['edd_exclude_tags'] : [];

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
        $channel->addChild('title', get_bloginfo('name'));
        if(!empty(get_bloginfo('description'))) {
            $channel->addChild('description', get_bloginfo('description'));
        }
        $channel->addChild('link', get_bloginfo('url'));

        /**
         * Feed Item
         */
        foreach ($download_ids as $id) {
            $download = edd_get_download($id);
            $dowl['id'] = $settings['edd_product_identifier'] == 'post_id' ? $download->ID : $download->get_sku();
            $dowl['title'] = $download->post_title;
            $dowl['desc'] = $settings['edd_description_field'] == 'description' ? $download->post_content : $download->post_excerpt;
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
     * Feed item function
     */
    function feed_item($channel, $download, $namespace) {

        $item = $channel->addChild('item');
        $item->addChild("g:id", $download['id'], $namespace);
        $item->addChild("g:title", $download['title'], $namespace);
        if(!empty($download['desc'])) {
            $item->addChild("g:description", $download['desc'], $namespace);
        }
        $item->addChild("g:availability", $download['availability'], $namespace);
        $item->addChild("g:condition", $download['condition'], $namespace);
        if($download['price']) {
            $item->addChild("g:price", $download['price'], $namespace);
        }
        $item->addChild("g:link", $download['link'], $namespace);
        if($download['image_link']) {
            $item->addChild("g:image_link", $download['image_link'], $namespace);
        }
        if(!empty($download['brand'])) {
            $item->addChild("g:brand", $download['brand'], $namespace);
        }
        $item->addChild("g:google_product_category", $download['google_product_category'], $namespace);
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
