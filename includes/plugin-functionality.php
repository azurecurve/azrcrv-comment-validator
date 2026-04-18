<?php
/*
	Plugin functionality - Comment Validator (Enhanced)
*/

/**
 * Declare the Namespace.
 */
namespace azurecurve\CommentValidator;

// ---------------------------------------------------------------------------
// Scoring weights & thresholds helpers
// ---------------------------------------------------------------------------

function cvp_weight( $key, $default ) {
	$weights = get_option( 'azrcrv-cv-weights', [] );
	return isset( $weights[ $key ] ) ? (int) $weights[ $key ] : $default;
}

function cvp_threshold( $key, $default ) {
	$t = get_option( 'azrcrv-cv-thresholds', [] );
	return isset( $t[ $key ] ) ? (int) $t[ $key ] : $default;
}

// ---------------------------------------------------------------------------
// DB table for IP reputation
// ---------------------------------------------------------------------------

function create_ip_reputation_table() {
	global $wpdb;
	$table   = $wpdb->prefix . 'azrcrv_cv_ip_rep';
	$charset = $wpdb->get_charset_collate();
	$sql     = "CREATE TABLE IF NOT EXISTS {$table} (
		ip_hash  VARCHAR(64) NOT NULL,
		score    TINYINT     NOT NULL DEFAULT 0,
		updated  DATETIME    NOT NULL,
		PRIMARY KEY (ip_hash)
	) {$charset};";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
register_activation_hook( PLUGIN_FILE, __NAMESPACE__ . '\\create_ip_reputation_table' );

function drop_ip_reputation_table() {
	global $wpdb;
	$table = $wpdb->prefix . 'azrcrv_cv_ip_rep';
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore
}

// ---------------------------------------------------------------------------
// Rate limiting
// ---------------------------------------------------------------------------

function cvp_rate_increment( $ip ) {
	$key     = 'azrcrv_cv_rate_' . md5( $ip ) . '_' . gmdate( 'YmdHi' );
	$current = (int) get_transient( $key );
	$current++;
	set_transient( $key, $current, MINUTE_IN_SECONDS * 2 );
	return $current;
}

// ---------------------------------------------------------------------------
// IP reputation helpers
// ---------------------------------------------------------------------------

function cvp_get_ip_rep( $ip ) {
	global $wpdb;
	$table = $wpdb->prefix . 'azrcrv_cv_ip_rep';
	$hash  = md5( $ip );
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT score FROM {$table} WHERE ip_hash = %s", $hash ) ); // phpcs:ignore
	return $row ? (int) $row->score : 0;
}

function cvp_increment_ip_rep( $ip, $amount = 2 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'azrcrv_cv_ip_rep';
	$hash  = md5( $ip );
	$now   = current_time( 'mysql', true );
	$wpdb->query( // phpcs:ignore
		$wpdb->prepare(
			"INSERT INTO {$table} (ip_hash, score, updated) VALUES (%s, %d, %s)
			 ON DUPLICATE KEY UPDATE score = LEAST(score + %d, 10), updated = %s",
			$hash, $amount, $now, $amount, $now
		)
	);
}

// ---------------------------------------------------------------------------
// Daily IP reputation decay cron
// ---------------------------------------------------------------------------

add_action( 'azrcrv_cv_decay_ip_rep', __NAMESPACE__ . '\\decay_ip_rep' );

function decay_ip_rep() {
	global $wpdb;
	$table = $wpdb->prefix . 'azrcrv_cv_ip_rep';
	$wpdb->query( "UPDATE {$table} SET score = score - 1 WHERE score > 0" ); // phpcs:ignore
	$wpdb->query( "DELETE FROM {$table} WHERE score <= 0" );                 // phpcs:ignore
}

if ( ! wp_next_scheduled( 'azrcrv_cv_decay_ip_rep' ) ) {
	wp_schedule_event( time(), 'daily', 'azrcrv_cv_decay_ip_rep' );
}

// ---------------------------------------------------------------------------
// Main scoring / validation
// ---------------------------------------------------------------------------

function validate_comment( $commentdata ) {

	global $wpdb;

	$options = get_option_with_defaults( PLUGIN_HYPHEN );

	if ( $options['use_network'] == 1 ) {
		$options = get_site_option_with_defaults( PLUGIN_HYPHEN );
	}

	$score   = 0;
	$reasons = [];
	$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';

	// ---- NONCE ----
	$nonce_value = isset( $_POST['azrcrv_cv_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['azrcrv_cv_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce_value, 'azrcrv_cv_comment' ) ) {
		wp_die(
			esc_html__( 'Invalid submission. Please refresh the page and try again.', 'azrcrv-cv' ),
			'', [ 'response' => 403 ]
		);
	}

	// ---- RATE LIMITING ----
	$rate_limit  = isset( $options['rate_limit_per_minute'] ) ? (int) $options['rate_limit_per_minute'] : 5;
	$submissions = cvp_rate_increment( $ip );
	if ( $submissions > $rate_limit ) {
		$score    += cvp_weight( 'rate_limit', 3 );
		$reasons[] = 'rate_limit';
	}

	// ---- HONEYPOT ----
	if ( $options['honeypot_enabled'] == 1 ) {
		$hp_val = isset( $_POST[ $options['honeypot_name'] ] )
			? sanitize_text_field( wp_unslash( $_POST[ $options['honeypot_name'] ] ) )
			: '';
		if ( ! empty( $hp_val ) ) {
			$score    += cvp_weight( 'honeypot', 5 );
			$reasons[] = 'honeypot';
		}
	}

	// ---- SECURE TIME TOKEN ----
	$token = isset( $_POST['azrcrv_cv_token'] ) ? sanitize_text_field( wp_unslash( $_POST['azrcrv_cv_token'] ) ) : '';
	$ttime = get_transient( 'azrcrv_cv_time_' . $token );

	if ( false === $ttime ) {
		$score    += cvp_weight( 'missing_token', 2 );
		$reasons[] = 'missing_token';
	} else {
		$elapsed = time() - (int) $ttime;
		if ( $options['time_delay_enabled'] == 1 && $elapsed < (int) $options['time_delay_seconds'] ) {
			wp_die(
				esc_html__( 'Your comment submission was too fast; please try to resubmit your comment after a short delay.', 'azrcrv-cv' ),
				'', [ 'response' => 200 ]
			);
		} elseif ( $elapsed < 3 ) {
			$score    += cvp_weight( 'too_fast', 2 );
			$reasons[] = 'too_fast';
		}
	}

	// ---- JS CHECK (verified, not just presence) ----
	$js_token = isset( $_POST['azrcrv_cv_js_token'] ) ? sanitize_text_field( wp_unslash( $_POST['azrcrv_cv_js_token'] ) ) : '';
	if ( empty( $js_token ) || false === base64_decode( $js_token, true ) ) {
		$score    += cvp_weight( 'no_js', 3 );
		$reasons[] = 'no_js';
	}

	// ---- INTERACTION TIME ----
	$interaction = (int) ( isset( $_POST['azrcrv_cv_interaction_time'] ) ? sanitize_text_field( wp_unslash( $_POST['azrcrv_cv_interaction_time'] ) ) : 0 );
	if ( $interaction < 500 ) {
		$score    += cvp_weight( 'interaction', 2 );
		$reasons[] = 'fast_interaction';
	}

	// ---- USER AGENT ----
	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		$score    += cvp_weight( 'no_agent', 2 );
		$reasons[] = 'no_agent';
	}

	// ---- CONTENT ANALYSIS ----
	$content = $commentdata['comment_content'];
	$len     = mb_strlen( $content );

	if ( $len < $options['min_length'] ) {
		wp_die( esc_html__( 'This comment is shorter than the minimum allowed size.', 'azrcrv-cv' ), '', [ 'response' => 200 ] );
	}

	if ( $options['max_length'] > 0 && $len > $options['max_length'] ) {
		wp_die( esc_html__( 'This comment is longer than the maximum allowed size.', 'azrcrv-cv' ), '', [ 'response' => 200 ] );
	}

	if ( $options['mod_length'] > 0 && $len > $options['mod_length'] ) {
		add_filter( 'pre_comment_approved', __NAMESPACE__ . '\\return_validated_comment', 99, 2 );
	}

	// Excessive links
	$link_count = preg_match_all( '/https?:\/\/|www\./i', $content );
	if ( $link_count > 2 ) {
		$score    += cvp_weight( 'links', 3 );
		$reasons[] = 'links';
	}

	// Spam keywords
	if ( preg_match( '/\b(viagra|cialis|casino|poker|crypto|bitcoin|loan|payday|free.?money|click.?here|buy.?now|weight.?loss|diet.?pill)\b/i', $content ) ) {
		$score    += cvp_weight( 'keywords', 3 );
		$reasons[] = 'keywords';
	}

	// All-caps ratio
	$letters = preg_replace( '/[^a-zA-Z]/', '', $content );
	if ( strlen( $letters ) >= 10 ) {
		$upper_ratio = strlen( preg_replace( '/[^A-Z]/', '', $letters ) ) / strlen( $letters );
		if ( $upper_ratio > 0.6 ) {
			$score    += cvp_weight( 'all_caps', 2 );
			$reasons[] = 'all_caps';
		}
	}

	// Repeated character sequences
	if ( preg_match( '/(.)\1{6,}/', $content ) ) {
		$score    += cvp_weight( 'repeated_chars', 1 );
		$reasons[] = 'repeated_chars';
	}

	// Duplicate content detection
	$content_hash     = md5( strtolower( trim( preg_replace( '/\s+/', ' ', $content ) ) ) );
	$recent_hashes    = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->commentmeta} WHERE meta_key = 'azrcrv_cv_content_hash' ORDER BY meta_id DESC LIMIT 100" ); // phpcs:ignore
	if ( in_array( $content_hash, $recent_hashes, true ) ) {
		$score    += cvp_weight( 'duplicate', 4 );
		$reasons[] = 'duplicate';
	}

	// Disposable email domains
	$email        = strtolower( $commentdata['comment_author_email'] ?? '' );
	$domain       = substr( strrchr( $email, '@' ), 1 );
	$spam_domains = [
		'mailnull.com', 'trashmail.com', 'guerrillamail.com', 'tempmail.com',
		'throwam.com', 'sharklasers.com', 'grr.la', 'yopmail.com',
		'maildrop.cc', 'dispostable.com', 'fakeinbox.com', 'mailinator.com',
		'spamgourmet.com', 'trashmail.me', 'discard.email',
	];
	if ( $domain && in_array( $domain, $spam_domains, true ) ) {
		$score    += cvp_weight( 'spam_email', 4 );
		$reasons[] = 'spam_email';
	}

	// ---- USERNAME PROTECTION ----
	if ( $options['prevent_unreg_using_reg_name'] == 1 && ! is_user_logged_in() ) {
		$author = $commentdata['comment_author'];
		$count  = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
			"SELECT COUNT(ID) FROM {$wpdb->users} WHERE user_login = %s OR user_nicename = %s OR display_name = %s",
			$author, $author, $author
		) );
		if ( $count > 0 ) {
			wp_die( esc_html__( 'This name is reserved.', 'azrcrv-cv' ), '', [ 'response' => 200 ] );
		}
	}

	// ---- IP REPUTATION ----
	$ip_rep = cvp_get_ip_rep( $ip );
	$score += $ip_rep;
	if ( $ip_rep > 0 ) {
		$reasons[] = 'ip_rep';
	}

	// ---- DECISION ----
	$block_threshold    = cvp_threshold( 'block', 7 );
	$moderate_threshold = cvp_threshold( 'moderate', 3 );

	if ( $score >= $block_threshold ) {
		cvp_increment_ip_rep( $ip, 2 );
		add_filter( 'pre_comment_approved', function () { return 'spam'; } );
	} elseif ( $score >= $moderate_threshold ) {
		add_filter( 'pre_comment_approved', function ( $approved ) {
			return ( 'spam' === $approved ) ? $approved : 0;
		} );
	}

	// ---- STORE META ----
	add_action( 'comment_post', function ( $comment_id ) use ( $score, $reasons, $content_hash, $token ) {
		delete_transient( 'azrcrv_cv_time_' . $token );
		add_comment_meta( $comment_id, 'azrcrv_cv_score',        $score,        true );
		add_comment_meta( $comment_id, 'azrcrv_cv_reasons',      $reasons,      true );
		add_comment_meta( $comment_id, 'azrcrv_cv_content_hash', $content_hash, true );
	} );

	return $commentdata;
}

/**
 * Return Validated Comment (moderation by length).
 */
function return_validated_comment( $approved, $commentdata ) {
	if ( 'spam' !== $approved ) {
		return 0;
	}
	return $approved;
}

// ---------------------------------------------------------------------------
// Comment form fields
// ---------------------------------------------------------------------------

function add_honeypot_field() {

	$options = get_option_with_defaults( PLUGIN_HYPHEN );

	// Plugin nonce
	wp_nonce_field( 'azrcrv_cv_comment', 'azrcrv_cv_nonce' );

	// Honeypot
	if ( $options['honeypot_enabled'] == 1 ) {
		echo '<div class="azrcrv-cv" aria-hidden="true" style="display:none!important;position:absolute!important;left:-9999px!important;">'
			. '<label for="' . esc_attr( $options['honeypot_name'] ) . '">' . esc_html( $options['honeypot_name'] ) . '</label>'
			. '<input type="text" name="' . esc_attr( $options['honeypot_name'] ) . '" id="' . esc_attr( $options['honeypot_name'] ) . '" tabindex="-1" autocomplete="off">'
			. '</div>';
	}

	// Secure time token
	$token = wp_generate_uuid4();
	set_transient( 'azrcrv_cv_time_' . $token, time(), 10 * MINUTE_IN_SECONDS );
	echo '<input type="hidden" name="azrcrv_cv_token" value="' . esc_attr( $token ) . '">';

	// JS proof + interaction time
	$js_seed = wp_create_nonce( 'azrcrv_cv_js_seed' );
	?>
	<input type="hidden" name="azrcrv_cv_js_token" id="azrcrv_cv_js_token">
	<input type="hidden" name="azrcrv_cv_interaction_time" id="azrcrv_cv_interaction_time" value="0">
	<script>
	(function(){
		var start = Date.now();
		var seed  = <?php echo wp_json_encode( $js_seed ); ?>;
		document.getElementById('azrcrv_cv_js_token').value = btoa(seed);
		document.addEventListener('input', function(){
			var el = document.getElementById('azrcrv_cv_interaction_time');
			if ( el ) el.value = Date.now() - start;
		});
	})();
	</script>
	<?php
}

// ---------------------------------------------------------------------------
// Admin: spam score injected into comment row actions (edit-comments.php)
// ---------------------------------------------------------------------------

/**
 * Append spam analysis box to comment row actions.
 */
function comment_row_spam_score( $actions, $comment ) {

	$score   = get_comment_meta( $comment->comment_ID, 'azrcrv_cv_score',   true );
	$reasons = get_comment_meta( $comment->comment_ID, 'azrcrv_cv_reasons', true );

	if ( '' === $score ) {
		return $actions;
	}

	$block_threshold    = cvp_threshold( 'block', 7 );
	$moderate_threshold = cvp_threshold( 'moderate', 3 );

	if ( (int) $score >= $block_threshold ) {
		$verdict = esc_html__( 'Spam', 'azrcrv-cv' );
	} elseif ( (int) $score >= $moderate_threshold ) {
		$verdict = esc_html__( 'Moderated', 'azrcrv-cv' );
	} else {
		$verdict = esc_html__( 'Passed', 'azrcrv-cv' );
	}

	$reasons_text = ( ! empty( $reasons ) && is_array( $reasons ) )
		? implode( ', ', $reasons )
		: esc_html__( 'none', 'azrcrv-cv' );

	$box  = '<div style="'
		. 'display:inline-block;'
		. 'margin-top:4px;'
		. 'padding:4px 8px;'
		. 'background:#FFC700;'
		. 'border:1px solid #C9A000;'
		. 'border-radius:3px;'
		. 'font-size:11px;'
		. 'line-height:1.5;'
		. 'color:#3d2f00;'
		. '">';
	$box .= '<strong>' . esc_html__( 'Spam Score:', 'azrcrv-cv' ) . '</strong> '
		. esc_html( $score ) . ' &mdash; '
		. '<strong>' . esc_html( $verdict ) . '</strong>'
		. '<br>'
		. '<strong>' . esc_html__( 'Rules:', 'azrcrv-cv' ) . '</strong> '
		. esc_html( $reasons_text );
	$box .= '</div>';

	$actions['azrcrv_cv_score'] = $box;

	return $actions;
}
