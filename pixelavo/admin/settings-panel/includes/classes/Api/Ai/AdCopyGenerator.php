<?php
namespace Pixelavo\Api\Ai;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * REST API Handler for Ad Copy Generator
 * 
 * Handles communication with AI providers (Gemini, OpenAI) for generating ad copy.
 * 
 * @package Pixelavo\Api
 * @since 1.0.0
 */
class AdCopyGenerator {

    // =============================================================================
    // CLASS CONSTANTS
    // =============================================================================

    /**
     * Maximum message length allowed
     */
    private const MAX_MESSAGE_LENGTH = 50000;

    /**
     * Default chat title length limit
     */
    private const MAX_TITLE_LENGTH = 50;

    /**
     * Default pagination limit for chat lists
     */
    private const DEFAULT_CHAT_LIMIT = 1000;

    // =============================================================================
    // CLASS PROPERTIES
    // =============================================================================

    /**
     * Gemini API handler instance
     *
     * @var Gemini
     */
    private $gemini;

    /**
     * OpenAI API handler instance
     *
     * @var OpenAI
     */
    private $openai;

    // =============================================================================
    // CONSTRUCTOR & INITIALIZATION
    // =============================================================================

    /**
     * Constructor - Initialize AI handlers
     */
    public function __construct() {
        $this->initialize_ai_handlers();
    }

    /**
     * Initialize AI handler instances
     *
     * @return void
     */
    private function initialize_ai_handlers() {
        require_once __DIR__ . '/Gemini.php';
        require_once __DIR__ . '/OpenAI.php';
        
        $this->gemini = new Gemini();
        $this->openai = new OpenAI();
    }

    public function generate_ad_copy($request) {
        try {
            $settings = $request['data'];

            $model = $settings['ai_model'];
            $ai = explode('-', $model, 2)[0];

            unset( $settings['ai_model'], $settings['ai_type'] );

            // Get API key for the specified provider
            $api_key = $this->get_api_key( $ai );
            if ( ! $api_key ) {
                return $this->create_error_response(
                    'API key not configured. Please add your ' . ucfirst($ai) . ' API key in the plugin settings.',
                    'missing_api_key'
                );
            }

            $api_response = $this->make_ai_request( $ai, $model, $settings, $api_key );

            // Process and return the response
            return $this->process_ai_response( $api_response, $ai, $model );

        } catch ( Exception $e ) {
            return $this->create_error_response( 
                'An unexpected error occurred: ' . $e->getMessage(),
                'unexpected_error'
            );
        }
    }

    // =============================================================================
    // AI API INTEGRATION
    // =============================================================================

    /**
     * Get API key for the specified AI provider
     *
     * @param string $ai AI provider name (gemini|openai)
     * @return string|false API key or false if not found
     */
    private function get_api_key( $ai ) {
        try {
            // Load EncryptionHelper if not already loaded
            if (!class_exists('\Pixelavo\Api\Ai\EncryptionHelper')) {
                require_once __DIR__ . '/EncryptionHelper.php';
            }

            $ai = $ai === 'gpt' ? 'openai' : $ai;
            $settings = get_option( 'pixelavo_settings', [] );
            $api_key_field = "{$ai}_api_key";

            if (!isset( $settings[$api_key_field] ) || empty( $settings[$api_key_field] )) {
                return false;
            }

            // Decrypt the API key
            $decrypted = EncryptionHelper::decrypt( $settings[$api_key_field] );

            if ($decrypted === false) {
                return false;
            }

            if (empty($decrypted)) {
                return false;
            }

            return $decrypted;

        } catch (\Exception $e) {
            return false;
        } catch (\Error $e) {
            return false;
        }
    }

    /**
     * Make AI request based on provider
     *
     * @param string $ai AI provider name
     * @param string $model Model name
     * @param array $settings Campaign settings
     * @param string $api_key API key
     * @return array|WP_Error API response or error
     */
    private function make_ai_request( $ai, $model, $settings, $api_key ) {
        $full_prompt = $this->get_prompt( $settings );
        
        switch ( $ai ) {
            case 'gemini':
                return $this->gemini->request( $full_prompt, $api_key, $model );
            case 'openai':
            case 'gpt':
                return $this->openai->request( $full_prompt, $api_key, $model );
            default:
                return $this->create_error_response(
                    "Invalid AI provider: $ai",
                    'invalid_ai_provider'
                );
        }
    }

    /**
     * Process API response from AI provider
     *
     * @param array|WP_Error $response API response
     * @param string $ai AI provider name
     * @param string $model Model name
     * @return array Processed response with chat data or error
     */
    private function process_ai_response( $response, $ai, $model ) {
        if ( is_wp_error( $response ) ) {
            return $this->create_error_response( 
                'API request failed: ' . $response->get_error_message(),
                'api_request_failed'
            );
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        // Check for HTTP errors
        if ( $response_code !== 200 ) {
            // Check for specific HTTP error codes
            if ( $response_code === 502 ) {
                return $this->create_error_response(
                    'Bad Gateway (502): The AI service is temporarily unavailable. Please try again.',
                    'bad_gateway_error'
                );
            } elseif ( $response_code === 503 ) {
                return $this->create_error_response(
                    'Service Unavailable (503): The server is temporarily overloaded. Please try again later.',
                    'service_unavailable'
                );
            } elseif ( $response_code === 504 ) {
                return $this->create_error_response(
                    'Gateway Timeout (504): The request took too long. Please try again.',
                    'gateway_timeout'
                );
            }

            $error_message = $this->extract_api_error_message( $response_code, $response_body );
            return $this->create_error_response( $error_message, 'api_http_error' );
        }

        // Parse JSON response
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return $this->create_error_response( 
                'Invalid JSON response from API: ' . json_last_error_msg(),
                'invalid_json_response'
            );
        } 

        // Check for API-level errors
        if ( isset( $data['error'] ) ) {
            return $this->create_error_response( 
                'API error: ' . ( $data['error']['message'] ?? 'Unknown API error' ),
                'api_error'
            );
        }

        // Process response based on AI provider
        $processed_response = $this->process_provider_response( $ai, $data );

        // Check if we got a response with success indicator
        if ( ! empty( $processed_response ) && isset( $processed_response['success'] ) ) {
            // If it's an error response from the provider
            if ( $processed_response['success'] === false ) {
                $error_message = $processed_response['error'] ?? 'Failed to generate ad copy';
                return $this->create_error_response(
                    $error_message,
                    $processed_response['error_code'] ?? 'ai_processing_failed',
                    $data
                );
            }

            // If successful but no message
            if ( $processed_response['success'] === true && empty( $processed_response['message'] ) ) {
                return $this->create_error_response(
                    'AI generated an empty response. Please try again with different inputs.',
                    'empty_ai_response',
                    $data
                );
            }

            // Process successful response with message
            if ( $processed_response['success'] === true && ! empty( $processed_response['message'] ) ) {
                $json_text = preg_replace('/^```json\s*/', '', $processed_response['message']);
                $json_text = preg_replace('/\s*```$/', '', $json_text);
                $json_text = trim($json_text);

                // Try to decode as JSON
                $decoded_message = json_decode( $json_text, true );

                if ( json_last_error() === JSON_ERROR_NONE ) {
                    $processed_response['message'] = $this->transform_response( $decoded_message );
                } else {
                    // If JSON decode fails, return error with the raw response for debugging
                    return $this->create_error_response(
                        'Failed to parse AI response. The AI may not have returned properly formatted ad copy.',
                        'json_parse_error',
                        $json_text
                    );
                }

                return array_merge(
                    $processed_response,
                    [
                        'ai' => $ai,
                        'model' => $model,
                        'role' => 'ai',
                    ]
                );
            }
        }

        // Fallback error for completely unexpected responses
        return $this->create_error_response(
            'Unexpected response from AI provider. Please try again.',
            'unexpected_ai_response',
            $data
        );
    }

    /**
     * Process response based on AI provider
     *
     * @param string $ai AI provider name
     * @param array $data Response data
     * @return array Processed response
     */
    private function process_provider_response( $ai, $data ) {
        switch ( $ai ) {
            case 'gemini':
                return $this->gemini->process_response( $data );
            case 'openai':
            case 'gpt':
                return $this->openai->process_response( $data );
            default:
                return [];
        }
    }

    /**
     * Extract error message from API response
     *
     * @param int $response_code HTTP response code
     * @param string $response_body Response body
     * @return string Error message
     */
    private function extract_api_error_message( $response_code, $response_body ) {
        $error_message = "API returned HTTP {$response_code}";
        
        if ( ! empty( $response_body ) ) {
            $error_data = json_decode( $response_body, true );
            if ( isset( $error_data['error']['message'] ) ) {
                $error_message .= ': ' . $error_data['error']['message'];
            }
        }
        
        return $error_message;
    }

    // =============================================================================
    // UTILITY METHODS
    // =============================================================================

    private function transform_response($message) {
        $transformed = array(
            'primary_text' => $this->format_text_variations($message['primary_text'] ?? []),
            'headlines' => $this->format_text_variations($message['headlines'] ?? []),
            'descriptions' => $this->format_text_variations($message['descriptions'] ?? []),
            'optimization_tips' => $message['optimization_tips'] ?? array(),
        );

        return $transformed;
    }

    private function format_text_variations($variations) {
        $formatted = array();
        
        foreach ($variations as $text) {
            $length = strlen($text);
            $status = $this->get_text_status($text, $length);
            
            $formatted[] = array(
                'text' => $text,
                'length' => $length,
                'status' => $status
            );
        }
        
        return $formatted;
    }

    private function get_text_status($text, $length) {
        // Determine status based on text type and length
        if (strpos($text, '🎉') !== false || strpos($text, '🎁') !== false) {
            // Primary text with emojis
            if ($length >= 125 && $length <= 200) {
                return 'optimal';
            } elseif ($length < 125) {
                return 'short';
            } else {
                return 'long';
            }
        } else {
            // Headlines and descriptions
            if ($length >= 40 && $length <= 80) {
                return 'optimal';
            } elseif ($length < 40) {
                return 'short';
            } else {
                return 'long';
            }
        }
    }

    /**
     * Create standardized error response
     *
     * @param string $message Error message
     * @param string $code Error code
     * @param mixed $raw_data Optional raw data for debugging
     * @return array Standardized error response
     */
    private function create_error_response( $message, $code, $raw_data = null ) {
        $error_response = [
            'success' => false,
            'error' => $message,
            'error_code' => $code,
            'created_at' => current_time( 'mysql' )
        ];

        // Include raw data only in debug mode
        if ( $raw_data && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $error_response['debug_data'] = $raw_data;
        }

        return $error_response;
    }

    // =============================================================================
    // AI PROMPT MANAGEMENT
    // =============================================================================

    /**
     * Get the complete AI prompt with user message
     *
     * @param array $settings Campaign settings
     * @return string Complete prompt for AI
     */
    public function get_prompt( $settings = [] ) {
        $base_prompt = $this->get_base_prompt();

        // Build product information section
        $product_info = $this->build_product_information( $settings );

        // Build campaign settings section
        $campaign_info = $this->build_campaign_settings( $settings );

        $input_data = "\n### Product/Service Information:\n" . $product_info;
        $input_data .= "\n### Campaign Settings:\n" . $campaign_info;
        $input_data .= "\nGenerate 3 distinct variations for each copy type (primary_text, headlines, descriptions).\nReturn only valid JSON, no explanation.\n";

        return $base_prompt . $input_data;
    }

    /**
     * Build product information section for the prompt
     *
     * @param array $settings Campaign settings
     * @return string Formatted product information
     */
    private function build_product_information( $settings ) {
        $product_info = "";

        // Handle custom product description
        if ( !empty( $settings['product_description'] ) ) {
            $product_info .= "Product/Service Description: " . $settings['product_description'] . "\n";
        }

        // Handle existing product selection (WC/EDD)
        if ( !empty( $settings['existing_product_id'] ) ) {
            $product_details = $this->get_existing_product_details( $settings['existing_product_id'] );
            if ( $product_details ) {
                $product_info .= "Selected Product: " . $product_details['title'] . "\n";
                if ( !empty( $product_details['description'] ) ) {
                    $product_info .= "Product Details: " . $product_details['description'] . "\n";
                }
                if ( !empty( $product_details['price'] ) ) {
                    $product_info .= "Price: " . $product_details['price'] . "\n";
                }
                if ( !empty( $product_details['categories'] ) ) {
                    $product_info .= "Categories: " . implode( ', ', $product_details['categories'] ) . "\n";
                }
            }
        }

        // If no product info available, provide fallback
        if ( empty( $product_info ) ) {
            $product_info = "No specific product information provided. Generate general high-converting ad copy.\n";
        }

        return $product_info;
    }

    /**
     * Build campaign settings section for the prompt
     *
     * @param array $settings Campaign settings
     * @return string Formatted campaign settings
     */
    private function build_campaign_settings( $settings ) {
        $campaign_info = "";
        $excluded_fields = ['product_description', 'existing_product_id'];

        foreach ( $settings as $key => $value ) {
            // Skip product-related fields as they're handled separately
            if ( in_array( $key, $excluded_fields ) || empty( $value ) ) {
                continue;
            }

            if ( is_array( $value ) ) {
                $campaign_info .= ucfirst( str_replace( '_', ' ', $key ) ) . ": " . implode( ", ", $value ) . "\n";
            } else {
                $campaign_info .= ucfirst( str_replace( '_', ' ', $key ) ) . ": " . $value . "\n";
            }
        }

        return $campaign_info;
    }

    /**
     * Get existing product details for WooCommerce or EDD products
     *
     * @param string $product_title Product title
     * @return array|null Product details or null if not found
     */
    private function get_existing_product_details( $product_title ) {
        global $wpdb;

        // Find product by title in WooCommerce
        if ( function_exists( 'wc_get_product' ) ) {
            $product_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'product' AND post_status = 'publish' LIMIT 1",
                $product_title
            ) );

            if ( $product_id ) {
                $product = wc_get_product( $product_id );
                if ( $product ) {
                    $categories = wp_get_post_terms( $product_id, 'product_cat', ['fields' => 'names'] );
                    return [
                        'title' => $product->get_name(),
                        'description' => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
                        'price' => $product->get_price_html(),
                        'categories' => ( !is_wp_error( $categories ) && is_array( $categories ) ) ? $categories : []
                    ];
                }
            }
        }

        // Find product by title in Easy Digital Downloads
        if ( function_exists( 'edd_get_download' ) ) {
            $download_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'download' AND post_status = 'publish' LIMIT 1",
                $product_title
            ) );

            if ( $download_id ) {
                $download = get_post( $download_id );
                if ( $download ) {
                    $categories = wp_get_post_terms( $download_id, 'download_category', ['fields' => 'names'] );
                    return [
                        'title' => $download->post_title,
                        'description' => wp_strip_all_tags( $download->post_excerpt ?: $download->post_content ),
                        'price' => function_exists( 'edd_price' ) ? edd_price( $download_id, false ) : '',
                        'categories' => ( !is_wp_error( $categories ) && is_array( $categories ) ) ? $categories : []
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Get the base prompt for the AI consultant
     *
     * @return string Base system prompt for Facebook advertising expertise
     */
    private function get_base_prompt() {
        return 'You are an expert Facebook Ads copywriter with 10+ years of experience creating high-converting ad copy across all industries and campaign objectives.

Your task is to generate compelling Facebook ad copy variations that drive engagement and conversions.

## Facebook Ad Copy Guidelines:
- **Primary Text**: 125-200 characters ideal for maximum visibility (Meta best practice)
- **Headlines**: 40-60 characters for optimal display across all placements
- **Descriptions**: 80 characters maximum for news feed ads
- Always consider mobile-first design and readability
- Use action-oriented language that creates urgency or desire
- Include social proof elements when appropriate

## Tone Guidelines:
- Friendly: Conversational, warm, approachable
- Professional: Authoritative, trustworthy, polished
- Urgent: Time-sensitive, scarcity-driven
- Casual: Relaxed, everyday language
- Premium/Luxury: Sophisticated, exclusive, high-end

## Campaign Objective Optimization:
- Awareness/Reach: Focus on broad appeal, brand introduction, lifestyle benefits
- Traffic: Use clear CTAs, highlight value propositions, create curiosity
- Conversions: Include offers, emphasize benefits, use urgency and scarcity
- Retargeting: Reference previous interaction, offer incentives, remove barriers
- Catalog Sales: Highlight product features, pricing, availability

## Copywriting Frameworks:
- AIDA
- PAS
- FOMO
- Social Proof
- Before & After
- Question Hook
- Benefits

## Copy Best Practices:
- Hooks, emotional triggers, specific benefits
- FOMO, social proof, CTAs, emojis where relevant

## Response Format:
Return ad copy as valid JSON with this structure:

{
    "primary_text": [
        "Variation 1",
        "Variation 2",
        "Variation 3"
    ],
    "headlines": [
        "Variation 1",
        "Variation 2",
        "Variation 3"
    ],
    "descriptions": [
        "Variation 1",
        "Variation 2",
        "Variation 3"
    ],
    "copy_insights": {
        "tone_applied": "[tone_name]",
        "campaign_focus": "[objective]",
        "key_elements": ["urgency","social_proof","benefit_focused"],
        "target_demographic": "[audience_type]",
        "framework_used": "[framework_name]"
    },
    "optimization_tips": [
        "Tip 1",
        "Tip 2",
        "Tip 3"
    ],
    "character_counts": {
        "primary_text_range": "125-200 chars",
        "headline_range": "40-60 chars",
        "description_range": "Under 80 chars"
    }
}

## Input Data:';
    }
}