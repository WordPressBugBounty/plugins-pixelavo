<?php

namespace Pixelavo;

/* Exit if accessed directly */
if (!defined('ABSPATH')) {
    exit;
}

class PixelFeed{
    
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
        if ('pixelavo' === $type) {
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
        add_feed( 'pixelavo', [$this, 'pixel_feed'] );
    }

    function pixel_feed() {

        /**
         * Settings
         */
        $settings = get_option('pixelavo_settings');
        $exclude_categories = array_key_exists('exclude_categories', $settings) ? $settings['exclude_categories'] : [];
        $exclude_tags = array_key_exists('exclude_tags', $settings) ? $settings['exclude_tags'] : [];

        /**
         * Products
         */
        $args = [
            'status' => 'publish',
            'limit' => -1,
            'orderby' => 'ID',
            'order' => 'ASC',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            'tax_query' => [
                'relation' => 'AND',
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $exclude_categories,
                    'operator' => 'NOT IN',
                ],
                [
                    'taxonomy' => 'product_tag',
                    'field' => 'term_id',
                    'terms' => $exclude_tags,
                    'operator' => 'NOT IN',
                ]
            ],
        ];
        $products = wc_get_products($args);

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
        foreach ($products as $product) {
            $pro['id'] = $settings['product_identifier'] == 'post_id' ? $product->get_id() : $product->get_sku();
            $pro['group_id'] = $pro['id'];
            $pro['title'] = $product->get_title();
            $pro['desc'] = $settings['description_field'] == 'description' ? $product->get_description() : $product->get_short_description();
            $pro['link'] = $product->get_permalink();
            $pro['image_link'] = $this->get_image_link($product->get_id());
            $pro['brand'] = $this->get_brand($product->get_id());
            $pro['condition'] = $this->get_condition($product->get_id());
            $pro['availability'] = $product->is_in_stock() ? 'in stock' : 'out of stock';
            $pro['price'] = $this->get_price($product);
            $pro['extra_fields'] = $this->get_extra_fields($product);
            $pro['additional_images'] = $this->get_additional_images($product);
            $pro['google_product_category'] = empty( $settings['product_feed_gpc'] ) ? $product->get_attribute( 'google_product_category' ) : $settings['product_feed_gpc'];

            if($product->is_type('variable') && pixelavo_get_option('include_variations', 'pixelavo_settings') == 'on') {
                foreach ($product->get_available_variations() as $variation) {
                    $pro['image_link'] = $this->get_image_link($product->get_id(), $variation);
                    $pro['price'] = $this->get_price($product, $variation);
                    $pro['availability'] = $variation['is_in_stock'] ? 'in stock' : 'out of stock';
                    $pro['link'] = !empty($variation['url']) ? $variation['url'] : $product->get_permalink();
                    $pro['additional_images'] = $this->get_additional_images($product, $variation);
                    $this->feed_item($channel, $pro, $namespace, $variation);
                }
            } else {
                $this->feed_item($channel, $pro, $namespace);
            }
        }

        echo $rss->asXML(); // phpcs:ignore
    }

    /**
     * Feed item function
     */
    function feed_item($channel, $product, $namespace, $variation = false) {

        if($variation) {
            $product['id'] = $product['id'] . '-' . $variation['variation_id'];
            $product['title'] = $product['title'] . ' - ' . $this->get_variation_title($variation['attributes']);
            $product['extra_fields'] = array_merge($product['extra_fields'], $this->get_extra_fields(null,$variation['attributes']));
        }

        $item = $channel->addChild('item');
        $item->addChild("g:id", $product['id'], $namespace);
        $item->addChild("g:item_group_id", $product['group_id'], $namespace);
        $item->addChild("g:title", $product['title'], $namespace);
        $item->addChild("g:description", $product['desc'], $namespace);
        $item->addChild("g:availability", $product['availability'], $namespace);
        $item->addChild("g:condition", $product['condition'], $namespace);
        if($product['price']) {
            $item->addChild("g:price", $product['price'], $namespace);
        }
        $item->addChild("g:link", $product['link'], $namespace);
        if($product['image_link']) {
            $item->addChild("g:image_link", $product['image_link'], $namespace);
        }
        if(isset($product['google_product_category']) && !empty($product['google_product_category'])) {
            $item->addChild("g:google_product_category", $product['google_product_category'], $namespace);
        }
        if(!empty($product['brand'])) {
            $item->addChild("g:brand", $product['brand'], $namespace);
        }
        foreach ($product['extra_fields'] as $key => $value) {
            $item->addChild("g:{$key}", $value, $namespace);
        }
        foreach ($product['additional_images'] as $image) {
            $item->addChild("g:additional_image_link", $image, $namespace);
        }
    }

    /**
     * Get variation product title
     */
    function get_variation_title($variations) {
        if (is_array($variations)) {
            $variation_values = [];
            foreach ($variations as $variation) {
                if(!empty($variation)) {
                    $variation_values[] = $variation;
                }
            }
            return '(' . implode( '-', $variation_values ) . ')';
        }
        return '';
    }

    /**
     * Get product thumbnail image link
     */
    function get_image_link($product_id, $variation = null) {
        // Try variation image first if provided and has an image
        if (is_array($variation) && !empty($variation['image_id'])) {
            $image = wp_get_attachment_image_src($variation['image_id'], 'single-post-thumbnail');
            if ($image) {
                return $image[0];
            }
        }
        // Fall back to parent product thumbnail
        $image = wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'single-post-thumbnail');
        if ($image) {
            return $image[0];
        }
        return false;
    }

    /**
     * Get brand name
     */
    function get_brand($product_id) {
        $brand_terms = wp_get_post_terms( $product_id, 'product_brand' );
        if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
            return $brand_terms[0]->name;
        }
        $settings = get_option('pixelavo_settings');
        return isset($settings['product_feed_brand']) ? $settings['product_feed_brand'] : '';
    }
    
    /**
     * Get product condition
     */
    function get_condition($product_id) {
        $condition = get_post_meta($product_id, '_product_condition', true);
        if (empty($condition)) {
            return 'new';
        }
        return $condition;
    }

    /**
     * Get product price
     */
    function get_price($product, $variation = null) {
        $the_price = is_array($variation) ? $variation['display_price'] : wc_get_price_to_display( $product );
        if ($the_price) {
            return $the_price . ' ' . get_woocommerce_currency();
        }
        return false;
    }

    /**
     * Get product extra fields
     */
    function get_extra_fields($product, $variation = null) {
        $fields = ['color', 'gender', 'pattern', 'material', 'size'];
        $extra_fields = [];
        forEach ( $fields as $field ) {
            if ( isSet($variation[ 'attribute_pa_' . $field ]) && !empty($variation[ 'attribute_pa_' . $field ])) {
                $extra_fields[$field] = $variation[ 'attribute_pa_' . $field ];
            } else if (isSet($product) && $product->get_attribute($field) && !empty($product->get_attribute($field))) {
                $extra_fields[$field] = $product->get_attribute($field);
            }
        }
        return $extra_fields;
    }

    /**
     * Get product categories
     */
    function get_categories($product) {
        $categories = get_the_terms($product->get_id(), 'product_cat');
        $category_string = '';
        if (!empty($categories)) {
            $categories = wp_list_sort($categories, ['parent' => 'ASC', 'name' => 'ASC'], 'ASC');
            foreach ($categories as $category) {
                $category_string = empty($category_string) ? $category_string . $category->name : $category_string .' > '. $category->name;
            }
        }
        return $category_string;
    }

    /**
     * Get product additional images
     */
    function get_additional_images( $product, $variation = null ) {
        $images = [];
        /* Variation Image */
        $variation_image = is_array($variation) ? wp_get_attachment_image_src($variation['image_id'], 'single-post-thumbnail') : false;
        if ($variation_image) {
            $images[] = $variation_image[0];
        }
        /* Gallery Images */
        $gallery_ids = $product->get_gallery_image_ids();
        if (!empty($gallery_ids)) {
            foreach($gallery_ids as $gallery_id) {
                $images[] = wp_get_attachment_url($gallery_id);
            }
        }
        return array_unique($images);
    }

}

/**
 * Initialize PixelFeed Class
 */
PixelFeed::instance();
