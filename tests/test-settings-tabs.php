<?php
/**
 * Tests for the settings page's tabs: Settings and the shared "More plugins".
 *
 * @package DFX_Feed_For_Telegram_Channels
 */

/**
 * The settings page's tabs.
 */
class Test_Settings_Tabs extends WP_UnitTestCase {

	/**
	 * URLs requested during the test.
	 *
	 * @var string[]
	 */
	private $requests = array();

	/**
	 * An administrator, no network, and an empty catalog cache.
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		delete_transient( 'dfx_plugin_showcase_catalog' );
		$this->requests = array();

		// The library keeps the catalog in memory for the rest of the request: start afresh.
		if ( class_exists( 'DFX_Plugin_Showcase', false ) ) {
			$instance = new ReflectionProperty( 'DFX_Plugin_Showcase', 'instance' );
			if ( PHP_VERSION_ID < 80100 ) {
				$instance->setAccessible( true );
			}
			$instance->setValue( null, null );
		}

		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) {
				$this->requests[] = $url;
				return new WP_Error( 'offline', 'No network in tests.' );
			},
			10,
			3
		);
	}

	/**
	 * The library is loaded from the plugin's own copy.
	 */
	public function test_showcase_library_is_loaded() {
		$this->assertTrue( function_exists( 'dfx_plugin_showcase_render' ) );
	}

	/**
	 * The settings tab shows both tabs and the form, and makes no request to davefx.com.
	 */
	public function test_settings_tab_shows_the_form_without_network() {
		$html = $this->render( '' );

		$this->assertStringContainsString( 'nav-tab-wrapper', $html );
		$this->assertStringContainsString( 'tab=more-plugins', $html );
		$this->assertStringContainsString( 'name="dfxfftc_bot_token"', $html );
		$this->assertSame( array(), $this->requests );
	}

	/**
	 * The "More plugins" tab lists the catalog without this plugin, and hides the form.
	 */
	public function test_more_plugins_tab_lists_the_catalog_without_this_plugin() {
		$html = $this->render( 'more-plugins' );

		$this->assertStringContainsString( 'dfx-showcase', $html );
		$this->assertStringContainsString( 'Coupon Audit', $html );
		$this->assertStringNotContainsString( 'DFX Feed for Telegram Channels</h3>', $html );
		$this->assertStringNotContainsString( 'name="dfxfftc_bot_token"', $html );
	}

	/**
	 * The catalog request does not carry the site's address.
	 */
	public function test_catalog_request_does_not_name_the_site() {
		$agent = null;
		add_filter(
			'pre_http_request',
			static function ( $pre, $args ) use ( &$agent ) {
				$agent = $args['user-agent'];
				return $pre;
			},
			5,
			2
		);

		$this->render( 'more-plugins' );

		$this->assertNotNull( $agent );
		$this->assertStringNotContainsString( wp_parse_url( home_url(), PHP_URL_HOST ), $agent );
	}

	/**
	 * Renders the settings page.
	 *
	 * @param string $tab Tab, or ''.
	 * @return string
	 */
	private function render( $tab ) {
		$_GET = '' === $tab ? array() : array( 'tab' => $tab );

		ob_start();
		\DFXFFTC\Settings::instance()->render_page();
		$html = (string) ob_get_clean();
		$_GET = array();

		return $html;
	}
}
