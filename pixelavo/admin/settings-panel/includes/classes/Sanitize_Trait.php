<?php
namespace Pixelavo\SanitizeTrail;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Settings Fields Sanitize handler trait
 */
trait Sanitize_Trait {

    /**
	 * Sanitize the text field.
	 *
	 * @param string $setting_value
	 * @param object $errors
	 * @param array $setting
	 * @return string
	 */
	public function sanitize_text_field( $setting_value, $errors, $setting ) {
		return trim( wp_strip_all_tags( $setting_value, true ) );
	}

	/**
	 * Sanitize textarea field.
	 *
	 * @param string $setting_value
	 * @param object $errors
	 * @param array $setting
	 * @return string
	 */
	public function sanitize_textarea_field( $setting_value, $errors, $setting ) {
		return stripslashes( wp_kses_post( $setting_value ) );
	}

	/**
	 * Sanitize multiselect and multicheck field.
	 *
	 * @param mixed $setting_value
	 * @param object $errors
	 * @param array $setting
	 * @return array
	 */
	public function sanitize_multiple_field( $setting_value, $errors, $setting ) {

		$new_values = [];

		if ( is_array( $setting_value ) && ! empty( $setting_value ) ) {
			foreach ( $setting_value as $key => $value ) {
				if(is_array($value)){
					foreach($value as $key => $val){
						$new_values[ sanitize_key( $key ) ] = sanitize_text_field( $val );
					}
				}else{
					$new_values[ sanitize_key( $key ) ] = sanitize_text_field( $value );
				}
			}
		}

		if ( ! empty( $setting_value ) && ! is_array( $setting_value ) ) {
			$setting_value = explode( ',', $setting_value );
			foreach ( $setting_value as $key => $value ) {
				$new_values[ sanitize_key( $key ) ] = sanitize_text_field( $value );
			}
		}

		return $new_values;

	}

	/**
	 * Sanitize event-table and table fields (preserves array structure).
	 *
	 * @param mixed $setting_value
	 * @param object $errors
	 * @param array $setting
	 * @return array
	 */
	public function sanitize_table_field( $setting_value, $errors, $setting ) {

		if ( ! is_array( $setting_value ) ) {
			return [];
		}

		$sanitized = [];

		// Check if this is a wrapper object with lists
		if ( isset( $setting_value['pixel_lists'] ) || isset( $setting_value['custom_events_lists'] ) ) {
			// Handle pixel_lists
			if ( isset( $setting_value['pixel_lists'] ) && is_array( $setting_value['pixel_lists'] ) ) {
				$sanitized['pixel_lists'] = [];
				foreach ( $setting_value['pixel_lists'] as $item ) {
					$sanitized['pixel_lists'][] = $this->sanitize_table_item( $item );
				}
			}

			// Handle custom_events_lists
			if ( isset( $setting_value['custom_events_lists'] ) && is_array( $setting_value['custom_events_lists'] ) ) {
				$sanitized['custom_events_lists'] = [];
				foreach ( $setting_value['custom_events_lists'] as $item ) {
					$sanitized['custom_events_lists'][] = $this->sanitize_table_item( $item );
				}
			}

			// Preserve other fields like verifynonce
			foreach ( $setting_value as $key => $value ) {
				if ( $key !== 'pixel_lists' && $key !== 'custom_events_lists' ) {
					$sanitized[ $key ] = is_array( $value ) ? $value : sanitize_text_field( $value );
				}
			}
		} else {
			// Direct array of items
			foreach ( $setting_value as $item ) {
				$sanitized[] = $this->sanitize_table_item( $item );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize individual table item.
	 *
	 * @param array $item
	 * @return array
	 */
	private function sanitize_table_item( $item ) {
		if ( ! is_array( $item ) ) {
			return [];
		}

		$sanitized_item = [];

		foreach ( $item as $key => $value ) {
			if ( is_array( $value ) ) {
				// Handle nested arrays (like event_params)
				$sanitized_item[ $key ] = [];
				foreach ( $value as $sub_item ) {
					if ( is_array( $sub_item ) ) {
						$sanitized_sub = [];
						foreach ( $sub_item as $sub_key => $sub_value ) {
							$sanitized_sub[ $sub_key ] = sanitize_text_field( $sub_value );
						}
						$sanitized_item[ $key ][] = $sanitized_sub;
					} else {
						$sanitized_item[ $key ][] = sanitize_text_field( $sub_item );
					}
				}
			} else {
				$sanitized_item[ $key ] = sanitize_text_field( $value );
			}
		}

		return $sanitized_item;
	}

	/**
	 * Sanitize urls for the file field.
	 *
	 * @param string $setting_value
	 * @param object $errors
	 * @param array $setting
	 * @return string
	 */
	public function sanitize_file_field( $setting_value, $errors, $setting ) {
		return esc_url( $setting_value );
	}

	/**
	 * Sanitize the checkbox field. Some fields registered as 'element' are
	 * composite objects (nested toggles + multiselects, e.g. form_submission)
	 * rather than a plain on/off value — preserve their structure instead of
	 * collapsing them to a checkbox string.
	 *
	 * @param mixed $setting_value
	 * @param object $errors
	 * @param array $setting
	 * @return string|array
	 */
	public function sanitize_checkbox_field( $setting_value, $errors, $setting ) {

		if ( is_array( $setting_value ) ) {
			return $this->sanitize_array_recursive( $setting_value );
		}

		return ( isset( $setting_value ) && 'on' == $setting_value ) ? 'on' : 'off';

	}

	/**
	 * Recursively sanitize an array's scalar leaves while preserving its
	 * keys/shape (used for composite settings that aren't a fixed table
	 * structure).
	 *
	 * @param array $value
	 * @return array
	 */
	private function sanitize_array_recursive( $value ) {
		$sanitized = [];

		foreach ( $value as $key => $item ) {
			$sanitized[ $key ] = is_array( $item ) ? $this->sanitize_array_recursive( $item ) : sanitize_text_field( $item );
		}

		return $sanitized;
	}

}