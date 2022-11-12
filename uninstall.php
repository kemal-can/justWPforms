<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// Free version specific options
delete_transient( 'justwpforms_review_notice_recommend' );
delete_option( 'justwpforms_modal_dismissed_onboarding' );
delete_option( 'justwpforms_show_powered_by' );
delete_option( '_justwpforms_received_submissions' );

// Forms
if ( ! defined( 'justwpforms_VERSION' ) ) {
    $statuses = array( 'publish', 'trash', 'any' );

    foreach( $statuses as $status ) {
        $form_ids = get_posts( array(
            'post_type' => 'justwpform',
            'post_status' => $status,
            'numberposts' => -1,
            'fields' => 'ids',
        ) );

        foreach( $form_ids as $form_id ) {
            wp_delete_post( $form_id, true );
        }
    }
}

// Admin stats
delete_option( 'justwpforms_stat_settings' );
delete_option( 'justwpforms_stat_settings' );
delete_option( 'justwpforms_stat_new_contacts' );
delete_option( 'justwpforms_stat_reached_goals' );
delete_option( 'justwpforms_stat_responses_abandoned' );
delete_option( 'justwpforms_stat_responses_mobile' );
delete_option( 'justwpforms_stat_responses_started' );
delete_option( 'justwpforms_stat_responses_submitted' );
delete_option( 'justwpforms_stat_settings' );
delete_option( 'justwpforms_stat_validation_errors' );

// General options
delete_option( 'justwpforms-data-version' );
delete_option( 'widget_justwpforms_widget' );
delete_option( 'justwpforms_goal_pages' );
delete_transient( '_justwpforms_has_responses' );
delete_option( 'justwpforms-tracking' );
delete_option( 'ttf_updates_key_justwpforms' );

// User meta
$users = get_users();

foreach( $users as $user ) {
    delete_user_meta( $user->ID, 'justwpforms-dismissed-notices' );
    delete_transient( 'justwpforms_admin_notices_' . md5( $user->user_login ) );
    delete_user_meta( $user->ID, 'justwpforms-settings-sections-states' );
}

// Blocklist
delete_option( 'justwpforms_blocklist' );

// Activity
$responses = get_posts( array(
    'post_type' => 'justwpforms-message',
    'post_status' => array_values( get_post_stati() ),
    'numberposts' => -1,
) );

foreach( $responses as $response ) {
    wp_delete_post( $response->ID, true );
}

delete_transient( 'justwpforms_response_counters' );

// Migrations
delete_option( 'justwpforms-data-version' );

// Polls
$polls = get_posts( array(
    'post_type' => 'justwpforms-poll',
    'post_status' => 'any',
    'numberposts' => -1,
) );

foreach( $polls as $poll ) {
    wp_delete_post( $poll->ID, true );
}

// Privacy settings
delete_option( 'justwpforms_privacy_settings' );
wp_clear_scheduled_hook( 'justwpforms_schedule_privacy_cleanup' );

// Role permissions
delete_option( 'justwpforms_role_permissions' );

// Validation messages
delete_option( 'justwpforms-validation-messages' );

// Integrations
delete_option( '_justwpforms_service_credentials' );

// Anti-spam
delete_option( '_justwpforms_antispam_service_active' );

// Email
delete_option( '_justwpforms_email_service_active' );

// Deactivation
delete_option( '_justwpforms_cleanup_on_deactivate' );