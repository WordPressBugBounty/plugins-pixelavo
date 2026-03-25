<?php
namespace Pixelavo\Api\Ai;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Database Installer for AI Features
 *
 * Handles creation and management of database tables required for AI functionality
 *
 * @package Pixelavo\Api\Ai
 * @since 1.0.0
 */
class DatabaseInstaller {

    /**
     * Create all required database tables for AI features
     *
     * @return void
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'pixelavo_ad_chat';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) DEFAULT '',
            list longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_updated_at (updated_at)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        // Store the current database version
        update_option( 'pixelavo_ai_db_version', '1.0.0' );
    }

    /**
     * Drop all AI-related database tables
     *
     * Used during plugin uninstall
     *
     * @return void
     */
    public static function drop_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pixelavo_ad_chat';

        // Use direct query for DROP TABLE as dbDelta doesn't handle it
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe, comes from $wpdb->prefix
        $wpdb->query( "DROP TABLE IF EXISTS $table_name" );

        // Remove the database version option
        delete_option( 'pixelavo_ai_db_version' );
    }

    /**
     * Check if the AI chat table exists
     *
     * @return bool
     */
    public static function table_exists() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pixelavo_ad_chat';

        $result = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ) );

        return $result === $table_name;
    }

    /**
     * Upgrade database schema if needed
     *
     * This method can be used for future database migrations
     *
     * @return void
     */
    public static function upgrade_tables() {
        $current_version = get_option( 'pixelavo_ai_db_version', '0' );

        // Future database migrations can be added here
        // Example:
        // if ( version_compare( $current_version, '1.1.0', '<' ) ) {
        //     self::upgrade_to_1_1_0();
        // }

        // Always ensure tables exist
        if ( ! self::table_exists() ) {
            self::create_tables();
        }
    }
}