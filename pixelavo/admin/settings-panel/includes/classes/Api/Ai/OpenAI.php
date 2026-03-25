<?php
namespace Pixelavo\Api\Ai;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * REST_API Handler for Gemini
 * 
 * Handles communication with Google's Gemini API
 * 
 * @package Pixelavo\Api
 * @since 1.0.0
 */
class OpenAI {

    /**
     * Base URL
     */
    private const BASE_URL = 'https://api.openai.com/v1/chat/completions';

    /**
     * Model name
     */
    private const MODEL = 'gpt-4o-mini';
    
    /**
     * Default timeout for API requests (seconds)
     */
    private const DEFAULT_TIMEOUT = 60;

    /**
     * Maximum message length
     */
    private const MAX_MESSAGE_LENGTH = 50000;

    /**
     * Constructor
     */
    public function __construct() {}

    /**
     * Make API request to OpenAI
     *
     * @param string $message User message
     * @param string $api_key API key
     * @param string $model Model name
     * @return array
     */
    public function request( $message, $api_key, $model = self::MODEL ) {
        $url = self::BASE_URL;

        // Map our model names to actual OpenAI model names
        $model = $this->map_model_name( $model );

        $body = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ],
            'temperature' => 0.7,
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => "Bearer $api_key",
                'User-Agent'    => 'Pixelavo-AI-Consultant/1.0',
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => self::DEFAULT_TIMEOUT,
        ]);

        return $response;
    }

    /**
     * Map our model names to actual OpenAI API model names
     *
     * @param string $model Our model name
     * @return string Actual OpenAI model name
     */
    private function map_model_name( $model ) {
        $model_mapping = [
            // ChatGPT 5 models (when available)
            'gpt-5' => 'gpt-4o', // Fallback to GPT-4o for now as GPT-5 isn't fully released
            'gpt-5-mini' => 'gpt-4o-mini', // Fallback to GPT-4o Mini
            'gpt-5-nano' => 'gpt-4o-mini', // Fallback to GPT-4o Mini

            // GPT-4 models
            'gpt-4o' => 'gpt-4o',
            'gpt-4o-mini' => 'gpt-4o-mini',
            'gpt-4-turbo' => 'gpt-4-turbo-preview',
            'gpt-4' => 'gpt-4',

            // GPT-3.5 models
            'gpt-3.5-turbo' => 'gpt-3.5-turbo',
        ];

        return isset( $model_mapping[$model] ) ? $model_mapping[$model] : $model;
    }

    /**
     * Process OpenAI response
     *
     * @param array $data API response
     * @return array
     */
    public function process_response( $data ) {
        // Check if response has the expected structure
        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            // Check for content filtering
            if ( isset( $data['choices'][0]['finish_reason'] ) ) {
                $finish_reason = $data['choices'][0]['finish_reason'];
                if ( $finish_reason === 'content_filter' ) {
                    return $this->create_error_response(
                        'Content was filtered due to OpenAI safety policies. Please rephrase your request.',
                        'content_filtered'
                    );
                } elseif ( $finish_reason === 'length' ) {
                    return $this->create_error_response(
                        'Response was truncated due to length limits. Please try a shorter query.',
                        'max_tokens_reached'
                    );
                }
            }

            $generated_text = $data['choices'][0]['message']['content'];

            // Validate that we actually got text content
            if ( empty( trim( $generated_text ) ) ) {
                return $this->create_error_response(
                    'OpenAI returned an empty response. Please try rephrasing your question.',
                    'empty_response'
                );
            }

            $usage = [
                'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $data['usage']['total_tokens'] ?? 0
            ];
            return [
                'success' => true,
                'message' => trim($generated_text),
                'usage' => $usage,
            ];
        }

        // Check for specific error messages from OpenAI
        if ( isset( $data['error'] ) ) {
            $error_message = $data['error']['message'] ?? 'Unknown OpenAI API error';
            $error_type = $data['error']['type'] ?? 'unknown_error';

            // Provide user-friendly messages for common errors
            if ( strpos( $error_message, 'API key' ) !== false || strpos( $error_message, 'Incorrect API key' ) !== false ) {
                return $this->create_error_response(
                    'Invalid OpenAI API key. Please check your settings.',
                    'invalid_api_key'
                );
            } elseif ( strpos( $error_message, 'quota' ) !== false || strpos( $error_message, 'limit' ) !== false ) {
                return $this->create_error_response(
                    'OpenAI API quota exceeded. Please check your usage limits or upgrade your plan.',
                    'quota_exceeded'
                );
            } elseif ( strpos( $error_message, 'model' ) !== false ) {
                return $this->create_error_response(
                    'Invalid model selected. Please choose a different model.',
                    'invalid_model'
                );
            }

            return $this->create_error_response( $error_message, $error_type );
        }

        // Check if choices array is empty
        if ( isset( $data['choices'] ) && empty( $data['choices'] ) ) {
            return $this->create_error_response(
                'No response generated. Please try again.',
                'no_choices'
            );
        }

        // If we get here, the response structure is completely unexpected
        return $this->create_error_response(
            'Unexpected response format from OpenAI API. Please try again.',
            'invalid_response_format',
            $data
        );
    }

    /**
     * Create standardized error response
     *
     * @param string $message Error message
     * @param string $code Error code
     * @param mixed $raw_data Optional raw data for debugging
     * @return array
     */
    public function create_error_response( $message, $code, $raw_data = null ) {
        $error_response = [
            'success' => false,
            'error' => $message,
            'error_code' => $code,
        ];

        // Include raw data only in debug mode
        if ( $raw_data && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $error_response['debug_data'] = $raw_data;
        }

        return $error_response;
    }
}