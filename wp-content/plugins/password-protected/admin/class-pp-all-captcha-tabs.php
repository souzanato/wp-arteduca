<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @package     Password Protected
 * @subpackage  All-Captchas
 *
 * @since  2.7.8
 */

class Password_Protected_Free_allCaptchas {
    private static $instance = null;
    public $options_group = 'password-protected-all-captchas';
	public $options_name  = 'password_protected_allcaptchas';
	public $tab           = 'password-protected&tab=advanced-all-captchas';
	public $settings      = array();

	public function __construct() {
		$this->settings = get_option( $this->options_name );
		add_action( 'admin_init', array( $this, 'register_all_captchas_settings' ), 6 );
		add_action( 'password_protected_subtab_all-captchas_content', array( $this, 'all_captchas_settings' ) );
	}
	
    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

	public function register_all_captchas_settings() {

		add_settings_section(
			$this->options_group,
			__( ' ', 'password-protected-pro' ),
			'__return_null',
			$this->tab
		);

		add_settings_field(
			'password_protected_enable_allcaptchas',
			__( '', 'password-protected-pro' ),
			array( $this, 'all_captchas_enable' ),
			$this->tab,
			$this->options_group
		);
        
        add_settings_field(
			'password_protected_show_captcha_fields',
			__( ' ', 'password-protected-pro' ),
			array( $this, 'show_captchas_fields' ),
			$this->tab,
			$this->options_group
		);

		register_setting( $this->options_group, $this->options_name, array( 'type' => 'array' ) );

	}

	public function all_captchas_settings() {
		?>
        <div class="wrap allCaptchaTabsTable">
            <h1 class="enableCaptchaHeading"><?php _e( 'Enable Captcha', 'password-protected-pro' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->options_group );
                do_settings_sections( 'password-protected&tab=advanced-all-captchas' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function pro_version_verify() {

        global $Password_Protected_Pro;

        if ( isset( $Password_Protected_Pro->version ) && version_compare( $Password_Protected_Pro->version, '1.9', '>=' ) ) {
            return true;
        }

        return false;

    }

	public function all_captchas_enable() {
        
                $captcha_providers = array(
                    'recaptcha' => array(
                        'label' => 'reCAPTCHA',
                        'pro'   => false,
                    ),
                    'hcaptcha' => array(
                        'label' => 'hCaptcha',
                        'pro'   => true,
                    ),
                    'turnstile' => array(
                        'label' => 'Turnstile',
                        'pro'   => true,
                    ),
                    'none' => array(
                        'label' => 'None',
                        'pro'   => false,
                    ),
                );
                $i = 1;

                if ( $this->pro_version_verify() ) {
                    $captcha_providers['hcaptcha']['pro']   = false;
                    $captcha_providers['turnstile']['pro']  = false;
                }     
                ?>
                <span class="captcha-setting-field">
                <?php
                foreach ( $captcha_providers as $value => $provider ) {
                        $input_id   = 'captcha-setting-captcha-provider[' . $i . ']';
                        $input_name = 'captcha_provider'; // common name for radio group
                        $label      = $provider['label'];
                        $is_pro     = $provider['pro'];

                        $checked = ( $this->settings === $value ) ? 'checked="checked"' : '';

                        echo '<span class="captcha-settings-field-radio-wrapper' . ( $is_pro ? ' click-to-display-purchase-popup' : '' ) . '">';
                        echo '<input type="radio" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $this->options_name ) . '" value="' . esc_attr( $value ) . '" '. $checked .' />';
                        echo '<label for="' . esc_attr( $input_id ) . '" class="option-' . esc_attr( strtolower( $value ) ) . '' . ( $is_pro ? '-free' : '' ) . '">' . esc_html( $label ) . '</label>';

                        if ( $is_pro ) {
                            echo '<span class="pro-badge captchaProBadge"><a class="actionDisable" href="https://passwordprotectedwp.com/pricing/">PRO</a></span>';
                        }

                        echo '</span>';

                    $i++;
                }
                ?>
                </span>
                <?php
	}

    public function show_captchas_fields() {
        
        echo do_action( 'password_protected_all_captchas' );
        echo '
            <div class="noneTab"></div>
        ';

	}

}

