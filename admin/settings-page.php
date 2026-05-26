<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------------------------------------------------------------------------
 * Settings registration — one option group per submenu page.
 * ------------------------------------------------------------------------ */
add_action( 'admin_init', 'zignites_chat_register_settings' );
function zignites_chat_register_settings() {
	// General Settings.
	register_setting( 'zignites_chat_general_group', 'zignites_chat_twilio_sid',              ['sanitize_callback' => 'zignites_chat_sanitize_text'] );
	register_setting( 'zignites_chat_general_group', 'zignites_chat_twilio_auth_token',       ['sanitize_callback' => 'zignites_chat_sanitize_text'] );
	register_setting( 'zignites_chat_general_group', 'zignites_chat_twilio_from',             ['sanitize_callback' => 'zignites_chat_sanitize_text'] );
	register_setting( 'zignites_chat_general_group', 'zignites_chat_api_provider',            ['sanitize_callback' => 'zignites_chat_sanitize_provider'] );
	register_setting( 'zignites_chat_general_group', 'zignites_chat_cloud_token',             ['sanitize_callback' => 'zignites_chat_sanitize_text'] );
	register_setting( 'zignites_chat_general_group', 'zignites_chat_cloud_phone_id',          ['sanitize_callback' => 'zignites_chat_sanitize_text'] );
	register_setting( 'zignites_chat_general_group', 'zignites_chat_cloud_from',              ['sanitize_callback' => 'zignites_chat_sanitize_text'] );
	register_setting( 'zignites_chat_general_group', 'zignites_chat_cloud_app_secret',        ['sanitize_callback' => 'zignites_chat_sanitize_text'] );
	register_setting( 'zignites_chat_general_group', 'zignites_chat_test_mode_enabled',       ['sanitize_callback' => 'zignites_chat_sanitize_yes_no'] );
	register_setting( 'zignites_chat_general_group', 'zignites_chat_data_retention_days',     ['sanitize_callback' => 'zignites_chat_sanitize_int'] );
	register_setting( 'zignites_chat_general_group', 'zignites_chat_delete_data_on_uninstall',['sanitize_callback' => 'zignites_chat_sanitize_yes_no'] );
	register_setting( 'zignites_chat_general_group', 'zignites_chat_optout_keywords',         ['sanitize_callback' => 'zignites_chat_sanitize_optout_keywords'] );
	register_setting( 'zignites_chat_general_group', 'zignites_chat_optout_list',             ['sanitize_callback' => 'zignites_chat_parse_optout_list'] );

	// Messaging.
	register_setting( 'zignites_chat_messaging_group', 'zignites_chat_test_phone',             ['sanitize_callback' => 'zignites_chat_sanitize_text'] );
	register_setting( 'zignites_chat_messaging_group', 'zignites_chat_test_message',           ['sanitize_callback' => 'zignites_chat_sanitize_textarea'] );
	register_setting( 'zignites_chat_messaging_group', 'zignites_chat_order_message_template', ['sanitize_callback' => 'zignites_chat_sanitize_textarea'] );

	// Chatbot.
	register_setting( 'zignites_chat_chatbot_group', 'zignites_chat_chatbot_enabled', ['sanitize_callback' => 'zignites_chat_sanitize_yes_no'] );
	register_setting( 'zignites_chat_chatbot_group', 'zignites_chat_faq_pairs',       ['sanitize_callback' => 'zignites_chat_sanitize_json_faq'] );
	register_setting( 'zignites_chat_chatbot_group', 'zignites_chat_chatbot_welcome', ['sanitize_callback' => 'zignites_chat_sanitize_text'] );
	register_setting( 'zignites_chat_chatbot_group', 'zignites_chat_agent_name',      ['sanitize_callback' => 'zignites_chat_sanitize_text'] );
	register_setting( 'zignites_chat_chatbot_group', 'zignites_chat_agent_phone',     ['sanitize_callback' => 'zignites_chat_sanitize_text'] );
}

/* ---------------------------------------------------------------------------
 * Admin menu.
 * ------------------------------------------------------------------------ */
add_action( 'admin_menu', 'zignites_chat_register_admin_menus' );
function zignites_chat_register_admin_menus() {
	add_menu_page(
		__( 'Zignites Chat', 'zignites-chat' ),
		__( 'Zignites Chat', 'zignites-chat' ),
		'manage_options',
		'zignites-chat-dashboard',
		'zignites_chat_render_dashboard_page',
		'dashicons-format-chat',
		66
	);

	$submenus = [
		['zignites-chat-dashboard', __( 'Dashboard',        'zignites-chat' ), 'zignites_chat_render_dashboard_page'],
		['zignites-chat-general',   __( 'General Settings', 'zignites-chat' ), 'zignites_chat_render_general_page'],
		['zignites-chat-messaging', __( 'Messaging',        'zignites-chat' ), 'zignites_chat_render_messaging_page'],
		['zignites-chat-chatbot',   __( 'Chatbot',          'zignites-chat' ), 'zignites_chat_render_chatbot_page'],
		['zignites-chat-logs',      __( 'Logs',             'zignites-chat' ), 'zignites_chat_render_logs_page'],
	];

	foreach ( $submenus as list( $slug, $title, $callback ) ) {
		add_submenu_page( 'zignites-chat-dashboard', $title, $title, 'manage_options', $slug, $callback );
	}
}

/* ---------------------------------------------------------------------------
 * Asset loading.
 * ------------------------------------------------------------------------ */
add_action( 'admin_enqueue_scripts', 'zignites_chat_enqueue_admin_scripts' );
function zignites_chat_enqueue_admin_scripts( $hook ) {
	if ( strpos( $hook, 'zignites-chat-' ) === false ) {
		return;
	}

	wp_enqueue_style(  'zignites-chat-admin-premium-css', ZIGNITES_CHAT_URL . 'assets/css/admin-premium.css', [], ZIGNITES_CHAT_VERSION );
	wp_enqueue_script( 'zignites-chat-admin-premium-js',  ZIGNITES_CHAT_URL . 'assets/js/admin-premium.js',  [], ZIGNITES_CHAT_VERSION, true );
	wp_localize_script( 'zignites-chat-admin-premium-js', 'zignitesChatAdminData', [
		'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
		'resendNonce'      => wp_create_nonce( 'zignites_chat_resend_cart' ),
		'testNonce'        => wp_create_nonce( 'zignites_chat_test_message' ),
		'logClearConfirm'  => __( 'Clear the log file? This cannot be undone.', 'zignites-chat' ),
	] );

	// Dashboard — first-run onboarding wizard.
	if ( strpos( $hook, 'zignites-chat-dashboard' ) !== false && get_option( 'zignites_chat_onboarding_completed', 'no' ) !== 'yes' ) {
		wp_enqueue_style(  'zignites-chat-onboarding-css', ZIGNITES_CHAT_URL . 'assets/css/onboarding.css', [], ZIGNITES_CHAT_VERSION );
		wp_enqueue_script( 'zignites-chat-onboarding-js',  ZIGNITES_CHAT_URL . 'assets/js/onboarding.js',  [], ZIGNITES_CHAT_VERSION, true );
		wp_localize_script( 'zignites-chat-onboarding-js', 'zignitesChatOnboarding', [
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'dismissNonce' => wp_create_nonce( 'zignites_chat_dismiss_onboarding' ),
			'saveNonce'    => wp_create_nonce( 'zignites_chat_save_onboarding' ),
			'i18n'         => [
				'saveError'    => __( 'Could not save. Please check the highlighted fields.', 'zignites-chat' ),
				'networkError' => __( 'Network error. Please try again.', 'zignites-chat' ),
				'saving'       => __( 'Saving…', 'zignites-chat' ),
				'next'         => __( 'Next', 'zignites-chat' ),
			],
		] );
	}

	// Messaging page — template library.
	if ( strpos( $hook, 'zignites-chat-messaging' ) !== false ) {
		wp_enqueue_script( 'zignites-chat-template-library-js', ZIGNITES_CHAT_URL . 'assets/js/template-library.js', [], ZIGNITES_CHAT_VERSION, true );
		wp_localize_script( 'zignites-chat-template-library-js', 'zignitesChatTemplateLibraryI18n', [
			'empty' => __( 'No templates available for this section yet.', 'zignites-chat' ),
		] );
	}
}

/* ---------------------------------------------------------------------------
 * Shared page chrome.
 * ------------------------------------------------------------------------ */

/**
 * Open the standard admin page wrapper.
 *
 * @param string $title Page heading (already translated).
 */
function zignites_chat_admin_page_open( $title ) {
	echo '<div class="wrap zignites-chat-admin-premium-wrap zignites-chat-admin-wrap">';
	echo '<h1>' . esc_html( $title ) . '</h1>';
}

function zignites_chat_admin_page_close() {
	echo '</div>';
}

/**
 * Render a settings submenu: heading, form, section view.
 *
 * @param string $title            Page heading.
 * @param string $group            Registered settings group.
 * @param string $view             View filename inside admin/views/.
 * @param bool   $with_template_lib Whether to append the template-library modal.
 */
function zignites_chat_render_settings_view( $title, $group, $view, $with_template_lib = false ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized', 'zignites-chat' ) );
	}
	zignites_chat_admin_page_open( $title );
	echo '<form method="post" action="options.php">';
	settings_fields( $group );
	require ZIGNITES_CHAT_PATH . 'admin/views/' . $view;
	submit_button();
	echo '</form>';
	if ( $with_template_lib ) {
		require ZIGNITES_CHAT_PATH . 'admin/views/template-library-modal.php';
	}
	zignites_chat_admin_page_close();
}

/* ---------------------------------------------------------------------------
 * Page render callbacks.
 * ------------------------------------------------------------------------ */

function zignites_chat_render_dashboard_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized', 'zignites-chat' ) );
	}
	zignites_chat_admin_page_open( __( 'Zignites Chat Dashboard', 'zignites-chat' ) );
	require ZIGNITES_CHAT_PATH . 'admin/views/dashboard.php';
	zignites_chat_admin_page_close();
}

function zignites_chat_render_general_page() {
	zignites_chat_render_settings_view( __( 'General Settings', 'zignites-chat' ), 'zignites_chat_general_group', 'tab-general.php' );
}

function zignites_chat_render_messaging_page() {
	zignites_chat_render_settings_view( __( 'Messaging', 'zignites-chat' ), 'zignites_chat_messaging_group', 'tab-messaging.php', true );
}

function zignites_chat_render_chatbot_page() {
	zignites_chat_render_settings_view( __( 'Chatbot', 'zignites-chat' ), 'zignites_chat_chatbot_group', 'tab-chatbot.php' );
}

function zignites_chat_render_logs_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized', 'zignites-chat' ) );
	}
	zignites_chat_admin_page_open( __( 'Logs', 'zignites-chat' ) );
	require ZIGNITES_CHAT_PATH . 'admin/views/tab-logs.php';
	zignites_chat_admin_page_close();
}

/* ---------------------------------------------------------------------------
 * First-run onboarding wizard modal.
 * ------------------------------------------------------------------------ */
function zignites_chat_render_onboarding_modal() {
	if ( get_option( 'zignites_chat_onboarding_completed', 'no' ) === 'yes' ) {
		return;
	}

	$ob_provider = get_option( 'zignites_chat_api_provider', 'twilio' );
	if ( ! in_array( $ob_provider, ['twilio', 'cloud'], true ) ) {
		$ob_provider = 'twilio';
	}
	$ob_twilio_sid   = get_option( 'zignites_chat_twilio_sid', '' );
	$ob_twilio_token = get_option( 'zignites_chat_twilio_auth_token', '' );
	$ob_twilio_from  = get_option( 'zignites_chat_twilio_from', '' );
	$ob_cloud_token  = get_option( 'zignites_chat_cloud_token', '' );
	$ob_cloud_phone  = get_option( 'zignites_chat_cloud_phone_id', '' );
	$ob_cloud_from   = get_option( 'zignites_chat_cloud_from', '' );
	?>
	<div id="zignites-chat-onboarding-modal">
		<div class="zignites-chat-onboarding-content">
			<div class="zignites-chat-onboarding-progress"><div class="zignites-chat-onboarding-progress-inner"></div></div>

			<div class="zignites-chat-onboarding-step" data-step="welcome">
				<h2><?php esc_html_e( 'Welcome to Zignites Chat!', 'zignites-chat' ); ?></h2>
				<p><?php esc_html_e( "Let's connect your WhatsApp account in a couple of steps.", 'zignites-chat' ); ?></p>
			</div>

			<div class="zignites-chat-onboarding-step" data-step="provider">
				<h2><?php esc_html_e( 'Choose your WhatsApp provider', 'zignites-chat' ); ?></h2>
				<p><?php esc_html_e( 'Pick the API you have an account with. You can change this later.', 'zignites-chat' ); ?></p>
				<div class="zignites-chat-onboarding-provider-choices">
					<label class="zignites-chat-onboarding-provider-choice">
						<input type="radio" name="zignites_chat_ob_provider" value="twilio" <?php checked( $ob_provider, 'twilio' ); ?> />
						<span class="zignites-chat-onboarding-provider-title"><?php esc_html_e( 'Twilio', 'zignites-chat' ); ?></span>
						<span class="zignites-chat-onboarding-provider-desc"><?php esc_html_e( 'WhatsApp via Twilio Programmable Messaging.', 'zignites-chat' ); ?></span>
					</label>
					<label class="zignites-chat-onboarding-provider-choice">
						<input type="radio" name="zignites_chat_ob_provider" value="cloud" <?php checked( $ob_provider, 'cloud' ); ?> />
						<span class="zignites-chat-onboarding-provider-title"><?php esc_html_e( 'Meta Cloud API', 'zignites-chat' ); ?></span>
						<span class="zignites-chat-onboarding-provider-desc"><?php esc_html_e( 'WhatsApp Business Platform direct from Meta.', 'zignites-chat' ); ?></span>
					</label>
				</div>
			</div>

			<div class="zignites-chat-onboarding-step" data-step="credentials">
				<h2><?php esc_html_e( 'Enter your credentials', 'zignites-chat' ); ?></h2>
				<p class="zignites-chat-onboarding-step-hint" data-provider-hint="twilio"><?php esc_html_e( 'Find these in your Twilio Console.', 'zignites-chat' ); ?></p>
				<p class="zignites-chat-onboarding-step-hint" data-provider-hint="cloud"><?php esc_html_e( 'Find these in your Meta for Developers app.', 'zignites-chat' ); ?></p>

				<div class="zignites-chat-onboarding-fields" data-provider-fields="twilio">
					<div class="zignites-chat-onboarding-field">
						<label for="zignites_chat_ob_twilio_sid"><?php esc_html_e( 'Account SID', 'zignites-chat' ); ?></label>
						<input type="text" id="zignites_chat_ob_twilio_sid" name="twilio_sid" value="<?php echo esc_attr( $ob_twilio_sid ); ?>" autocomplete="off" />
						<span class="zignites-chat-onboarding-field-error" data-error-for="twilio_sid"></span>
					</div>
					<div class="zignites-chat-onboarding-field">
						<label for="zignites_chat_ob_twilio_token"><?php esc_html_e( 'Auth Token', 'zignites-chat' ); ?></label>
						<input type="password" id="zignites_chat_ob_twilio_token" name="twilio_token" value="<?php echo esc_attr( $ob_twilio_token ); ?>" autocomplete="off" />
						<span class="zignites-chat-onboarding-field-error" data-error-for="twilio_token"></span>
					</div>
					<div class="zignites-chat-onboarding-field">
						<label for="zignites_chat_ob_twilio_from"><?php esc_html_e( 'From Number', 'zignites-chat' ); ?></label>
						<input type="text" id="zignites_chat_ob_twilio_from" name="twilio_from" value="<?php echo esc_attr( $ob_twilio_from ); ?>" placeholder="whatsapp:+14155238886" autocomplete="off" />
						<span class="zignites-chat-onboarding-field-error" data-error-for="twilio_from"></span>
					</div>
				</div>

				<div class="zignites-chat-onboarding-fields" data-provider-fields="cloud">
					<div class="zignites-chat-onboarding-field">
						<label for="zignites_chat_ob_cloud_token"><?php esc_html_e( 'Access Token', 'zignites-chat' ); ?></label>
						<input type="password" id="zignites_chat_ob_cloud_token" name="cloud_token" value="<?php echo esc_attr( $ob_cloud_token ); ?>" autocomplete="off" />
						<span class="zignites-chat-onboarding-field-error" data-error-for="cloud_token"></span>
					</div>
					<div class="zignites-chat-onboarding-field">
						<label for="zignites_chat_ob_cloud_phone_id"><?php esc_html_e( 'Phone Number ID', 'zignites-chat' ); ?></label>
						<input type="text" id="zignites_chat_ob_cloud_phone_id" name="cloud_phone_id" value="<?php echo esc_attr( $ob_cloud_phone ); ?>" autocomplete="off" />
						<span class="zignites-chat-onboarding-field-error" data-error-for="cloud_phone_id"></span>
					</div>
					<div class="zignites-chat-onboarding-field">
						<label for="zignites_chat_ob_cloud_from"><?php esc_html_e( 'From Number', 'zignites-chat' ); ?></label>
						<input type="text" id="zignites_chat_ob_cloud_from" name="cloud_from" value="<?php echo esc_attr( $ob_cloud_from ); ?>" placeholder="+14155238886" autocomplete="off" />
						<span class="zignites-chat-onboarding-field-error" data-error-for="cloud_from"></span>
					</div>
				</div>

				<div class="zignites-chat-onboarding-form-error" role="alert"></div>
			</div>

			<div class="zignites-chat-onboarding-step" data-step="done">
				<h2><?php esc_html_e( 'All set!', 'zignites-chat' ); ?></h2>
				<p><?php esc_html_e( 'Your provider is connected. Use the menu on the left to enable the chatbot and customise your messages.', 'zignites-chat' ); ?></p>
			</div>

			<div class="zignites-chat-onboarding-buttons">
				<button type="button" class="zignites-chat-onboarding-prev"><?php esc_html_e( 'Back', 'zignites-chat' ); ?></button>
				<button type="button" class="zignites-chat-onboarding-next"><?php esc_html_e( 'Next', 'zignites-chat' ); ?></button>
				<button type="button" class="zignites-chat-onboarding-finish"><?php esc_html_e( 'Finish', 'zignites-chat' ); ?></button>
				<button type="button" class="zignites-chat-onboarding-skip"><?php esc_html_e( 'Skip', 'zignites-chat' ); ?></button>
			</div>
		</div>
	</div>
	<?php
}

/* ---------------------------------------------------------------------------
 * AJAX handlers.
 * ------------------------------------------------------------------------ */

add_action( 'wp_ajax_zignites_chat_dismiss_onboarding', 'zignites_chat_ajax_dismiss_onboarding' );
function zignites_chat_ajax_dismiss_onboarding() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( ['message' => __( 'Unauthorized', 'zignites-chat' )], 403 );
	}
	if ( ! check_ajax_referer( 'zignites_chat_dismiss_onboarding', 'nonce', false ) ) {
		wp_send_json_error( ['message' => __( 'Bad nonce', 'zignites-chat' )], 400 );
	}
	update_option( 'zignites_chat_onboarding_completed', 'yes', false );
	wp_send_json_success();
}

add_action( 'wp_ajax_zignites_chat_save_onboarding_credentials', 'zignites_chat_ajax_save_onboarding_credentials' );
function zignites_chat_ajax_save_onboarding_credentials() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( ['message' => __( 'Unauthorized', 'zignites-chat' )], 403 );
	}
	if ( ! check_ajax_referer( 'zignites_chat_save_onboarding', 'nonce', false ) ) {
		wp_send_json_error( ['message' => __( 'Bad nonce', 'zignites-chat' )], 400 );
	}

	$provider_raw = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';
	if ( ! in_array( $provider_raw, ['twilio', 'cloud'], true ) ) {
		wp_send_json_error( ['message' => __( 'Choose a provider before continuing.', 'zignites-chat' )], 422 );
	}

	$errors = [];

	if ( $provider_raw === 'twilio' ) {
		$sid   = isset( $_POST['twilio_sid'] )   ? sanitize_text_field( wp_unslash( $_POST['twilio_sid'] ) )   : '';
		$token = isset( $_POST['twilio_token'] ) ? sanitize_text_field( wp_unslash( $_POST['twilio_token'] ) ) : '';
		$from  = isset( $_POST['twilio_from'] )  ? sanitize_text_field( wp_unslash( $_POST['twilio_from'] ) )  : '';

		if ( $sid === '' )   { $errors['twilio_sid']   = __( 'Twilio Account SID is required.', 'zignites-chat' ); }
		if ( $token === '' ) { $errors['twilio_token'] = __( 'Twilio Auth Token is required.', 'zignites-chat' ); }
		if ( $from === '' )  { $errors['twilio_from']  = __( 'From Number is required.', 'zignites-chat' ); }

		if ( ! empty( $errors ) ) {
			wp_send_json_error( ['fields' => $errors], 422 );
		}

		update_option( 'zignites_chat_api_provider', 'twilio', false );
		update_option( 'zignites_chat_twilio_sid', $sid, false );
		update_option( 'zignites_chat_twilio_auth_token', $token, false );
		update_option( 'zignites_chat_twilio_from', $from, false );
	} else {
		$token = isset( $_POST['cloud_token'] )    ? sanitize_text_field( wp_unslash( $_POST['cloud_token'] ) )    : '';
		$phone = isset( $_POST['cloud_phone_id'] ) ? sanitize_text_field( wp_unslash( $_POST['cloud_phone_id'] ) ) : '';
		$from  = isset( $_POST['cloud_from'] )     ? sanitize_text_field( wp_unslash( $_POST['cloud_from'] ) )     : '';

		if ( $token === '' ) { $errors['cloud_token']    = __( 'Access Token is required.', 'zignites-chat' ); }
		if ( $phone === '' ) { $errors['cloud_phone_id'] = __( 'Phone Number ID is required.', 'zignites-chat' ); }
		if ( $from === '' )  { $errors['cloud_from']     = __( 'From Number is required.', 'zignites-chat' ); }

		if ( ! empty( $errors ) ) {
			wp_send_json_error( ['fields' => $errors], 422 );
		}

		update_option( 'zignites_chat_api_provider', 'cloud', false );
		update_option( 'zignites_chat_cloud_token', $token, false );
		update_option( 'zignites_chat_cloud_phone_id', $phone, false );
		update_option( 'zignites_chat_cloud_from', $from, false );
	}

	wp_send_json_success();
}
