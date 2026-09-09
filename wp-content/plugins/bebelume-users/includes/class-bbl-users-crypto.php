<?php
/**
 * Cifra e decifra valores sensíveis (senha de app do Gmail).
 *
 * A chave deriva das salts do wp-config.php. Se as salts mudarem,
 * a senha salva deixa de ser legível e precisa ser informada de novo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBL_Users_Crypto {

	const METHOD = 'aes-256-cbc';

	/**
	 * Deriva a chave a partir das salts do WordPress.
	 */
	private static function key() {
		$material = '';

		foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY' ) as $const ) {
			if ( defined( $const ) ) {
				$material .= constant( $const );
			}
		}

		if ( '' === $material ) {
			$material = get_option( 'bbl_users_fallback_key' );

			if ( ! $material ) {
				$material = wp_generate_password( 64, true, true );
				update_option( 'bbl_users_fallback_key', $material, false );
			}
		}

		return hash( 'sha256', 'bbl-users|' . $material, true );
	}

	/**
	 * Cifra um texto. Retorna string vazia se o valor for vazio.
	 */
	public static function encrypt( $plain ) {
		if ( '' === $plain || null === $plain ) {
			return '';
		}

		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return base64_encode( $plain ); // phpcs:ignore
		}

		$iv     = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::METHOD ) );
		$cipher = openssl_encrypt( $plain, self::METHOD, self::key(), OPENSSL_RAW_DATA, $iv );

		if ( false === $cipher ) {
			return '';
		}

		return base64_encode( $iv . $cipher ); // phpcs:ignore
	}

	/**
	 * Decifra um texto cifrado por encrypt().
	 */
	public static function decrypt( $stored ) {
		if ( '' === $stored || null === $stored ) {
			return '';
		}

		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return base64_decode( $stored ); // phpcs:ignore
		}

		$raw = base64_decode( $stored, true ); // phpcs:ignore

		if ( false === $raw ) {
			return '';
		}

		$iv_length = openssl_cipher_iv_length( self::METHOD );

		if ( strlen( $raw ) <= $iv_length ) {
			return '';
		}

		$iv     = substr( $raw, 0, $iv_length );
		$cipher = substr( $raw, $iv_length );
		$plain  = openssl_decrypt( $cipher, self::METHOD, self::key(), OPENSSL_RAW_DATA, $iv );

		return false === $plain ? '' : $plain;
	}
}
