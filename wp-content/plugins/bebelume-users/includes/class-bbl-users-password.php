<?php
/**
 * Gerador de senha configurável.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBL_Users_Password {

	const OPTION = 'bbl_users_password';

	const LOWER      = 'abcdefghijklmnopqrstuvwxyz';
	const UPPER      = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const NUMBERS    = '0123456789';
	const SYMBOLS    = '!@#$%&*-_=+?';
	const AMBIGUOUS  = '0O1lI|`\'"';

	/**
	 * Configuração salva, com defaults.
	 */
	public static function get_options() {
		$defaults = array(
			'length'      => 12,
			'lower'       => 1,
			'upper'       => 1,
			'numbers'     => 1,
			'symbols'     => 0,
			'symbols_set' => self::SYMBOLS,
			'no_ambiguous' => 1,
		);

		$opts = get_option( self::OPTION, array() );
		$opts = wp_parse_args( is_array( $opts ) ? $opts : array(), $defaults );

		$opts['length'] = max( 6, min( 64, (int) $opts['length'] ) );

		if ( '' === trim( (string) $opts['symbols_set'] ) ) {
			$opts['symbols_set'] = self::SYMBOLS;
		}

		// Sem nenhum conjunto marcado a senha seria impossível: volta pro mínimo seguro.
		if ( ! $opts['lower'] && ! $opts['upper'] && ! $opts['numbers'] && ! $opts['symbols'] ) {
			$opts['lower']   = 1;
			$opts['numbers'] = 1;
		}

		return $opts;
	}

	/**
	 * Salva a configuração já saneada.
	 */
	public static function save_options( $input ) {
		$opts = array(
			'length'       => max( 6, min( 64, (int) ( $input['length'] ?? 12 ) ) ),
			'lower'        => empty( $input['lower'] ) ? 0 : 1,
			'upper'        => empty( $input['upper'] ) ? 0 : 1,
			'numbers'      => empty( $input['numbers'] ) ? 0 : 1,
			'symbols'      => empty( $input['symbols'] ) ? 0 : 1,
			'symbols_set'  => sanitize_text_field( $input['symbols_set'] ?? self::SYMBOLS ),
			'no_ambiguous' => empty( $input['no_ambiguous'] ) ? 0 : 1,
		);

		if ( ! $opts['lower'] && ! $opts['upper'] && ! $opts['numbers'] && ! $opts['symbols'] ) {
			$opts['lower']   = 1;
			$opts['numbers'] = 1;
		}

		update_option( self::OPTION, $opts, false );

		return $opts;
	}

	/**
	 * Remove os caracteres ambíguos de um conjunto.
	 */
	private static function strip_ambiguous( $set ) {
		return str_replace( str_split( self::AMBIGUOUS ), '', $set );
	}

	/**
	 * Gera uma senha respeitando a configuração.
	 *
	 * Garante ao menos um caractere de cada conjunto habilitado.
	 *
	 * @param array|null $opts Configuração alternativa (para preview).
	 */
	public static function generate( $opts = null ) {
		$o = is_array( $opts ) ? wp_parse_args( $opts, self::get_options() ) : self::get_options();

		$sets = array();

		if ( ! empty( $o['lower'] ) ) {
			$sets[] = $o['no_ambiguous'] ? self::strip_ambiguous( self::LOWER ) : self::LOWER;
		}
		if ( ! empty( $o['upper'] ) ) {
			$sets[] = $o['no_ambiguous'] ? self::strip_ambiguous( self::UPPER ) : self::UPPER;
		}
		if ( ! empty( $o['numbers'] ) ) {
			$sets[] = $o['no_ambiguous'] ? self::strip_ambiguous( self::NUMBERS ) : self::NUMBERS;
		}
		if ( ! empty( $o['symbols'] ) ) {
			$symbols = $o['symbols_set'];
			$sets[]  = $o['no_ambiguous'] ? self::strip_ambiguous( $symbols ) : $symbols;
		}

		$sets = array_values( array_filter( $sets, 'strlen' ) );

		if ( empty( $sets ) ) {
			$sets = array( self::strip_ambiguous( self::LOWER . self::NUMBERS ) );
		}

		$length = max( (int) $o['length'], count( $sets ) );
		$pool   = implode( '', $sets );
		$chars  = array();

		// Um de cada conjunto, para a senha bater com a configuração escolhida.
		foreach ( $sets as $set ) {
			$chars[] = $set[ random_int( 0, strlen( $set ) - 1 ) ];
		}

		while ( count( $chars ) < $length ) {
			$chars[] = $pool[ random_int( 0, strlen( $pool ) - 1 ) ];
		}

		// Fisher-Yates com CSPRNG, para não deixar a ordem dos conjuntos previsível.
		for ( $i = count( $chars ) - 1; $i > 0; $i-- ) {
			$j = random_int( 0, $i );
			$tmp        = $chars[ $i ];
			$chars[ $i ] = $chars[ $j ];
			$chars[ $j ] = $tmp;
		}

		return implode( '', $chars );
	}
}
