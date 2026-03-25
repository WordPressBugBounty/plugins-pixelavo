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
class Gemini {

    /**
     * Base URL
     */
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

    /**
     * Model name
     */
    private const MODEL = 'gemini-2.5-flash';
    
    /**
     * Default timeout for API requests (seconds)
     */
    private const DEFAULT_TIMEOUT = 60;

    /**
     * Constructor
     */
    public function __construct() {}

    /**
     * Make API request to Gemini
     *
     * @param string $message Message
     * @param string $api_key API key
     * @param string $model Model name
     * @return array
     */
    public function request( $message, $api_key, $model = self::MODEL ) {
        $url = self::BASE_URL . "/models/$model:generateContent?key=$api_key";
        
        $body = [
            'contents' => [
                [
                    'parts' => [
                        [ 'text' => $message ]
                    ]
                ]
            ],
            // Add generation config for better control
            // 'generationConfig' => [
            //     'temperature' => 0.7,
            //     'topK' => 40,
            //     'topP' => 0.95,
            //     'maxOutputTokens' => 2048,
            // ],
            // Add safety settings
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Pixelavo-AI-Consultant/1.0'
            ],
            'body' => wp_json_encode( $body ),
            'timeout' => self::DEFAULT_TIMEOUT,
        ]);

        return $response;
    }

    /**
     * Process Gemini response
     *
     * @param array $data API response
     * @return array
     */
    public function process_response( $data ) {
        // Check if response has the expected structure
        if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {

            // Check for content filtering
            if ( isset( $data['candidates'][0]['finishReason'] ) && $data['candidates'][0]['finishReason'] === 'SAFETY' ) {
                return $this->create_error_response(
                    'Content was filtered due to safety policies. Please rephrase your request.',
                    'content_filtered'
                );
            }

            // Check for other finish reasons that indicate problems
            if ( isset( $data['candidates'][0]['finishReason'] ) ) {
                $finish_reason = $data['candidates'][0]['finishReason'];
                if ( $finish_reason === 'MAX_TOKENS' ) {
                    return $this->create_error_response(
                        'Response was truncated due to length limits. Please try a shorter query.',
                        'max_tokens_reached'
                    );
                } elseif ( $finish_reason === 'RECITATION' ) {
                    return $this->create_error_response(
                        'Response was blocked due to recitation concerns.',
                        'recitation_blocked'
                    );
                }
            }

            $generated_text = $data['candidates'][0]['content']['parts'][0]['text'];

            // Validate that we actually got text content
            if ( empty( trim( $generated_text ) ) ) {
                return $this->create_error_response(
                    'Gemini returned an empty response. Please try rephrasing your question.',
                    'empty_response'
                );
            }

            $usage = [
                'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
                'total_tokens' => $data['usageMetadata']['totalTokenCount'] ?? 0
            ];
            return [
                'success' => true,
                'message' => trim($generated_text),
                'usage' => $usage,
            ];
        }

        // Check for specific error messages from Gemini
        if ( isset( $data['error'] ) ) {
            $error_message = $data['error']['message'] ?? 'Unknown Gemini API error';
            $error_code = $data['error']['code'] ?? 'unknown_error';

            // Provide user-friendly messages for common errors
            if ( strpos( $error_message, 'API key' ) !== false ) {
                return $this->create_error_response(
                    'Invalid Gemini API key. Please check your settings.',
                    'invalid_api_key'
                );
            } elseif ( strpos( $error_message, 'quota' ) !== false ) {
                return $this->create_error_response(
                    'Gemini API quota exceeded. Please try again later or upgrade your plan.',
                    'quota_exceeded'
                );
            }

            return $this->create_error_response( $error_message, $error_code );
        }

        // Check if candidates array is empty or blocked
        if ( isset( $data['candidates'] ) && empty( $data['candidates'] ) ) {
            return $this->create_error_response(
                'No response generated. The content may have been blocked by safety filters.',
                'no_candidates'
            );
        }

        // If we get here, the response structure is completely unexpected
        return $this->create_error_response(
            'Unexpected response format from Gemini API. Please try again.',
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
    private function create_error_response( $message, $code, $raw_data = null ) {
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