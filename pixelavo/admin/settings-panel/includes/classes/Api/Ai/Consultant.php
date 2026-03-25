<?php
namespace Pixelavo\Api\Ai;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use WP_REST_Response;

/**
 * REST API Handler for AI Consultant
 * 
 * Handles communication with AI providers (Gemini, OpenAI) for Facebook advertising consultation.
 * Manages chat sessions, message storage, and API interactions.
 * 
 * @package Pixelavo\Api
 * @since 1.0.0
 */
class Consultant {

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

    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Chat table name
     *
     * @var string
     */
    private $table_name;

    // =============================================================================
    // CONSTRUCTOR & INITIALIZATION
    // =============================================================================

    /**
     * Constructor - Initialize AI handlers and database connection
     */
    public function __construct() {
        global $wpdb;
        
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'pixelavo_ad_chat';
        
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

    // =============================================================================
    // PUBLIC API METHODS
    // =============================================================================

    /**
     * Handle chat request with AI
     *
     * @param WP_REST_Request $request The REST request object
     * @return array Response array with chat data or error information
     */
    public function create( $request ) {
        try {
            // Validate and sanitize input parameters
            $validation_result = $this->validate_request_params( $request );
            if ( isset( $validation_result['error'] ) ) {
                return $validation_result;
            }

            $ai = $validation_result['ai'];
            $model = $validation_result['model'];
            $message = $validation_result['message'];
            $id = $validation_result['id'] ?? null;

            // Get API key for the specified provider
            $api_key = $this->get_api_key( $ai );
            if ( ! $api_key ) {
                return $this->create_error_response(
                    'API key not configured. Please add your ' . ucfirst($ai) . ' API key in the plugin settings.',
                    'missing_api_key'
                );
            }

            // Save user message to database
            $chat_id = $this->save_user_message( $id, $message, $ai, $model );
            if ( ! $chat_id ) {
                return $this->create_error_response( 
                    'Failed to save user message to database',
                    'database_save_failed'
                );
            }

            // Make API request to AI provider with full conversation context
            $api_response = $this->make_ai_request( $ai, $model, $message, $api_key, $chat_id );

            // Process and return the response
            return $this->process_ai_response( $api_response, $ai, $model, $chat_id );

        } catch ( Exception $e ) {
            return $this->create_error_response( 
                'An unexpected error occurred: ' . $e->getMessage(),
                'unexpected_error'
            );
        }
    }

    /**
     * Get chat history for a specific chat ID
     *
     * @param int $chat_id Chat ID
     * @return array|false Chat data with messages or false on failure
     */
    public function get_chat_history( int $chat_id ) {
        try {
            $chat = $this->get_chat_by_id( $chat_id );

            if ( ! $chat ) {
                return false;
            }

            return [
                'id' => $chat->id,
                'title' => $chat->title,
                'messages' => $this->decode_chat_list( $chat->list ),
                'created_at' => $chat->created_at,
                'updated_at' => $chat->updated_at
            ];
        } catch ( \Exception $e ) {

            return false;
        }
    }

    /**
     * Get paginated list of chats
     *
     * @param \WP_REST_Request $request The request object
     * @return \WP_REST_Response List of chat objects with decoded message lists
     */
    public function get( \WP_REST_Request $request ): \WP_REST_Response {
        try {
            $limit = absint( $request->get_param( 'limit' ) ) ?: 50;  // Reduced default limit for better performance
            $offset = absint( $request->get_param( 'offset' ) ) ?: 0;

            // Validate limit to prevent excessive memory usage
            $limit = min( $limit, 100 );

            $query = $this->wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is a class property, not user input
                "SELECT id, title, list, created_at, updated_at FROM {$this->table_name} ORDER BY updated_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            );

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is built with $wpdb->prepare() above
            $results = $this->wpdb->get_results( $query );

            if ( $results === null ) {
                throw new \Exception( 'Database query failed' );
            }

            // Decode the JSON list field for each chat
            foreach ( $results as $chat ) {
                $chat->list = $this->decode_chat_list( $chat->list );
            }

            return rest_ensure_response([
                'success' => true,
                'data' => $results,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => count( $results ) === $limit
                ]
            ]);
        } catch ( \Exception $e ) {

            return rest_ensure_response([
                'success' => false,
                'error' => 'Failed to retrieve chats',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete a chat by ID
     *
     * @param \WP_REST_Request $request The request object containing chat ID
     * @return \WP_REST_Response Response object with success status
     */
    public function delete( \WP_REST_Request $request ): \WP_REST_Response {
        try {
            $id = $request->get_param('id');

            if ( ! $id ) {
                throw new \Exception( 'Chat ID is required' );
            }

            $result = $this->wpdb->delete(
                $this->table_name,
                [ 'id' => absint( $id ) ],
                [ '%d' ]
            );

            if ( $result === false ) {
                throw new \Exception( 'Database deletion failed' );
            }

            return rest_ensure_response([
                'success' => true,
                'message' => 'Chat deleted successfully'
            ]);
        } catch ( \Exception $e ) {

            return rest_ensure_response([
                'success' => false,
                'message' => 'Failed to delete chat',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Rename a chat by ID
     *
     * @param \WP_REST_Request $request The request object containing chat ID and new title
     * @return \WP_REST_Response Response object with success status
     */
    public function rename( \WP_REST_Request $request ): \WP_REST_Response {
        try {
            $id = $request->get_param('id');
            $title = $request->get_param('title');

            if ( ! $id ) {
                throw new \Exception( 'Chat ID is required' );
            }

            if ( ! $title ) {
                throw new \Exception( 'Chat title is required' );
            }

            $sanitized_title = sanitize_text_field( $title );

            if ( strlen( $sanitized_title ) > self::MAX_TITLE_LENGTH ) {
                $sanitized_title = substr( $sanitized_title, 0, self::MAX_TITLE_LENGTH );
            }

            $result = $this->wpdb->update(
                $this->table_name,
                [ 'title' => $sanitized_title ],
                [ 'id' => absint( $id ) ],
                [ '%s' ],
                [ '%d' ]
            );

            if ( $result === false ) {
                throw new \Exception( 'Database update failed' );
            }

            return rest_ensure_response([
                'success' => true,
                'message' => 'Chat renamed successfully'
            ]);
        } catch ( \Exception $e ) {

            return rest_ensure_response([
                'success' => false,
                'message' => 'Failed to rename chat',
                'error' => $e->getMessage()
            ]);
        }
    }

    // =============================================================================
    // REQUEST VALIDATION & SANITIZATION
    // =============================================================================

    /**
     * Validate and sanitize request parameters
     *
     * @param WP_REST_Request $request The REST request object
     * @return array Validated parameters or error response
     */
    private function validate_request_params( $request ) {
        $ai = $request->get_param('ai');
        $model = $request->get_param('model');
        $message = $request->get_param('message');
        $id = $request->get_param('id');

        // Validate required parameters
        if ( empty( $ai ) ) {
            return $this->create_error_response( 'AI provider parameter is required', 'missing_ai_param' );
        }

        if ( empty( $model ) ) {
            return $this->create_error_response( 'Model parameter is required', 'missing_model_param' );
        }

        if ( empty( $message ) ) {
            return $this->create_error_response( 'Message parameter is required', 'missing_message_param' );
        }

        // Sanitize inputs
        $ai = sanitize_text_field( $ai );
        $model = sanitize_text_field( $model );
        $message = wp_kses_post( $message ); // Allow some HTML but sanitize
        $id = $id ? absint( $id ) : null; // Sanitize ID as positive integer

        // Validate message length
        if ( strlen( $message ) > self::MAX_MESSAGE_LENGTH ) {
            return $this->create_error_response( 
                'Message is too long. Maximum length is ' . self::MAX_MESSAGE_LENGTH . ' characters.',
                'message_too_long'
            );
        }

        // Validate AI provider
        if ( ! in_array( $ai, ['gemini', 'openai'], true ) ) {
            return $this->create_error_response( 
                'Invalid AI provider. Supported providers: gemini, openai',
                'invalid_ai_provider'
            );
        }

        return [
            'ai' => $ai,
            'model' => $model,
            'message' => $message,
            'id' => $id
        ];
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
     * @param string $prompt User message
     * @param string $api_key API key
     * @param int|null $chat_id Chat ID for context
     * @return array|WP_Error API response or error
     */
    private function make_ai_request( $ai, $model, $prompt, $api_key, $chat_id = null ) {
        // Get full conversation context if chat_id is provided
        $full_prompt = $chat_id ? $this->get_prompt_with_context( $prompt, $chat_id ) : $this->get_prompt( $prompt );

        switch ( $ai ) {
            case 'gemini':
                return $this->gemini->request( $full_prompt, $api_key, $model );
            case 'openai':
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
     * @param int $chat_id Chat ID
     * @return array Processed response with chat data or error
     */
    private function process_ai_response( $response, $ai, $model, $chat_id ) {
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
        $data = json_decode( $response_body, true );
        
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

        // Check if we got a successful response with a message
        if ( ! empty( $processed_response ) && isset( $processed_response['success'] ) ) {
            // If it's an error response from the provider
            if ( $processed_response['success'] === false ) {
                $error_message = $processed_response['error'] ?? 'Failed to generate response from AI';
                return $this->create_error_response(
                    $error_message,
                    $processed_response['error_code'] ?? 'ai_processing_failed',
                    $data
                );
            }

            // If it's a successful response but missing message
            if ( $processed_response['success'] === true && empty( $processed_response['message'] ) ) {
                return $this->create_error_response(
                    'AI generated an empty response. Please try again.',
                    'empty_ai_response',
                    $data
                );
            }

            // Success - save and return the response
            if ( $processed_response['success'] === true && ! empty( $processed_response['message'] ) ) {
                // Save AI response to database
                $this->save_ai_response(
                    $chat_id,
                    $processed_response['message'],
                    $ai,
                    $model,
                    $processed_response['usage'] ?? null
                );

                return array_merge(
                    $processed_response,
                    [
                        'id' => $chat_id,
                        'ai' => $ai,
                        'model' => $model,
                        'role' => 'ai',
                        'created_at' => current_time( 'mysql' ),
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
    // DATABASE OPERATIONS
    // =============================================================================

    /**
     * Save user message to database
     *
     * @param int|null $id Chat ID (null for new chat)
     * @param string $message User message
     * @param string $ai AI provider
     * @param string $model Model name
     * @return int|false Chat ID on success, false on failure
     */
    private function save_user_message( $id, $message, $ai, $model ) {
        $user_message_data = [
            'role' => 'user',
            'message' => $message,
            'ai' => $ai,
            'model' => $model,
            'created_at' => current_time( 'mysql' ),
        ];

        if ( $id ) {
            return $this->update_existing_chat( $id, $user_message_data );
        } else {
            return $this->create_new_chat( $message, $user_message_data );
        }
    }

    /**
     * Update existing chat with new message
     *
     * @param int $id Chat ID
     * @param array $message_data Message data containing role, message, ai, model, and created_at
     * @return int|false Chat ID on success, false on failure
     */
    private function update_existing_chat( int $id, array $message_data ) {
        $chat = $this->get_chat_by_id( $id );
        if ( ! $chat ) {
            return false;
        }

        $list = $this->decode_chat_list( $chat->list );
        $list[] = $message_data;

        $result = $this->wpdb->update(
            $this->table_name,
            [
                'list' => wp_json_encode( $list ),
                'updated_at' => current_time( 'mysql' ),
            ],
            [ 'id' => $id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );

        return $result !== false ? $id : false;
    }

    /**
     * Create new chat with initial message
     *
     * @param string $message Initial message for title generation
     * @param array $message_data Message data containing role, message, ai, model, and created_at
     * @return int|false Chat ID on success, false on failure
     */
    private function create_new_chat( string $message, array $message_data ) {
        $title = $this->generate_chat_title( $message );
        
        $result = $this->wpdb->insert(
            $this->table_name,
            [
                'title' => $title,
                'list' => wp_json_encode( [ $message_data ] ),
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s' ]
        );

        return $result !== false ? $this->wpdb->insert_id : false;
    }

    /**
     * Save AI response to database
     *
     * @param int $chat_id Chat ID
     * @param string $message AI response message
     * @param string $ai AI provider (gemini|openai)
     * @param string $model Model name
     * @param array|null $usage Usage information containing tokens used
     * @return bool Success status
     */
    private function save_ai_response( int $chat_id, string $message, string $ai, string $model, ?array $usage = null ): bool {
        $chat = $this->get_chat_by_id( $chat_id );
        if ( ! $chat ) {
            return false;
        }

        $list = $this->decode_chat_list( $chat->list );
        
        $ai_response_data = [
            'role' => 'ai',
            'message' => $message,
            'ai' => $ai,
            'model' => $model,
            'created_at' => current_time( 'mysql' ),
        ];

        if ( $usage ) {
            $ai_response_data['usage'] = $usage;
        }

        $list[] = $ai_response_data;

        $result = $this->wpdb->update(
            $this->table_name,
            [
                'list' => wp_json_encode( $list ),
                'updated_at' => current_time( 'mysql' ),
            ],
            [ 'id' => $chat_id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );

        return $result !== false;
    }

    /**
     * Get chat by ID from database
     *
     * @param int $id Chat ID
     * @return object|null Chat object containing id, title, list, created_at, updated_at or null if not found
     */
    private function get_chat_by_id( int $id ): ?object {
        $query = $this->wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is a class property, not user input
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            absint( $id )
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is built with $wpdb->prepare() above
        return $this->wpdb->get_row( $query );
    }

    // =============================================================================
    // UTILITY METHODS
    // =============================================================================

    /**
     * Decode chat list JSON safely
     *
     * @param string|null $list_json JSON encoded list
     * @return array Decoded list of messages or empty array on failure
     */
    private function decode_chat_list( ?string $list_json ): array {
        if ( empty( $list_json ) ) {
            return [];
        }

        $list = json_decode( $list_json, true );
        return is_array( $list ) ? $list : [];
    }

    /**
     * Generate chat title from message
     *
     * @param string $message User message
     * @return string Generated title (max 50 characters)
     */
    private function generate_chat_title( string $message ): string {
        // Remove HTML tags and trim
        $clean_message = wp_strip_all_tags( $message );
        $clean_message = trim( $clean_message );
        
        // Limit title length
        if ( strlen( $clean_message ) > self::MAX_TITLE_LENGTH ) {
            $clean_message = substr( $clean_message, 0, self::MAX_TITLE_LENGTH - 3 ) . '...';
        }
        
        return $clean_message ?: 'New Chat';
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
     * @param string $message User message
     * @return string Complete prompt for AI with system instructions and user question
     */
    public function get_prompt( string $message ): string {
        $base_prompt = $this->get_base_prompt();
        return $base_prompt . "\n\n## User Question:\n" . $message . "\n\nPlease provide your expert advice:";
    }

    /**
     * Get AI prompt with full conversation context
     *
     * @param string $current_message Current user message
     * @param int $chat_id Chat ID to retrieve history
     * @return string Complete prompt with conversation history
     */
    private function get_prompt_with_context( string $current_message, int $chat_id ): string {
        // Get base prompt
        $base_prompt = $this->get_base_prompt();

        // Retrieve conversation history
        $chat = $this->get_chat_by_id( $chat_id );
        if ( ! $chat ) {
            // If no history found, fall back to single message
            return $this->get_prompt( $current_message );
        }

        // Decode chat messages
        $messages = $this->decode_chat_list( $chat->list );
        if ( empty( $messages ) || ! is_array( $messages ) ) {
            return $this->get_prompt( $current_message );
        }

        // Build conversation history
        $conversation = $base_prompt . "\n\n## Conversation History:\n\n";

        // Add previous messages (excluding the current one we just saved)
        $message_count = count( $messages );
        $messages_to_include = array_slice( $messages, 0, -1 ); // Exclude last message (current one)

        foreach ( $messages_to_include as $msg ) {
            if ( isset( $msg['role'] ) && isset( $msg['message'] ) ) {
                if ( $msg['role'] === 'user' ) {
                    $conversation .= "User: " . $msg['message'] . "\n\n";
                } elseif ( $msg['role'] === 'ai' ) {
                    $conversation .= "Assistant: " . $msg['message'] . "\n\n";
                }
            }
        }

        // Add current message
        $conversation .= "User: " . $current_message . "\n\n";
        $conversation .= "Please provide your expert advice, taking into account our previous conversation:";

        return $conversation;
    }

    /**
     * Get the base prompt for the AI consultant
     *
     * @return string Base system prompt for Facebook advertising expertise
     */
    private function get_base_prompt(): string {
        return 'You are an AI Ad Consultant, a specialized expert in Facebook advertising with 15+ years of experience helping businesses optimize their ad campaigns and maximize ROI. You provide strategic advice, troubleshooting guidance, and actionable recommendations specifically for Facebook Ads.

## Your Expertise Areas:
- **Campaign Strategy & Planning**: Objective selection, funnel optimization, budget allocation
- **Audience Targeting**: Custom audiences, lookalikes, interest targeting, behavioral targeting
- **Ad Creative Optimization**: Copy writing, visual best practices, video ads, carousel formats
- **Performance Analysis**: KPI interpretation, A/B testing, attribution modeling
- **Budget & Bidding**: Cost optimization, bid strategies, campaign budget optimization (CBO)
- **Compliance & Policies**: Facebook ad policies, disapprovals, account restrictions
- **Advanced Features**: Dynamic ads, retargeting, conversion optimization, iOS 14.5+ adaptations
- **Industry Best Practices**: E-commerce, lead generation, brand awareness, app installs

## Conversation Guidelines:
1. **Stay Focused**: Only provide advice related to Facebook advertising, digital marketing strategy, and related topics
2. **Be Practical**: Offer specific, actionable recommendations with clear next steps
3. **Ask Clarifying Questions**: When needed, ask for campaign details, objectives, or current challenges
4. **Provide Examples**: Use real-world examples and case studies when helpful
5. **Consider Context**: Factor in business size, industry, budget, and experience level
6. **Reference Best Practices**: Cite current Facebook advertising best practices and platform updates

## Response Structure:
- **Direct Answer**: Address the specific question first
- **Strategic Context**: Explain the "why" behind recommendations
- **Actionable Steps**: Provide clear, implementable action items
- **Additional Considerations**: Mention related factors or potential challenges
- **Follow-up Questions**: Ask relevant questions to provide more targeted advice

## Handling Off-Topic Requests:
If users ask about topics unrelated to Facebook advertising or digital marketing, politely redirect them:
"I\'m specifically designed to help with Facebook advertising questions and digital marketing strategy. Could you please ask me something related to your Facebook ads, campaign optimization, or advertising challenges?"

## Current Facebook Advertising Context (2024-2025):
- iOS 14.5+ privacy changes and Conversions API importance
- First-party data collection strategies
- Creative diversity and ad fatigue prevention
- Performance Max and Advantage+ campaign types
- Meta Business Suite and updated interface
- Privacy-focused targeting and broad audience strategies
- Video-first content strategy trends

## Example Response Format:
**Quick Answer**: [Direct response to the question]

**Why This Matters**: [Strategic explanation]

**Action Steps**:
1. [Specific step]
2. [Specific step]
3. [Specific step]

**Pro Tips**: [Additional insights or best practices]

**Need More Help?**: [Follow-up questions to provide deeper assistance]

Remember: Always be helpful, professional, and focused on driving real business results through effective Facebook advertising strategies.';
    }
}