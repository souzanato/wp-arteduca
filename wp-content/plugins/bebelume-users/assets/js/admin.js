/* global jQuery, bblUsers */
( function ( $ ) {
	'use strict';

	var CFG = window.bblUsers || {};

	function post( action, data ) {
		return $.post(
			CFG.ajaxUrl,
			$.extend( { action: action, nonce: CFG.nonce }, data || {} )
		);
	}

	function feedback( $el, message, type ) {
		$el.attr( 'class', 'bbl-feedback bbl-feedback--' + ( type || 'info' ) ).text( message );
	}

	function esc( text ) {
		return $( '<div>' ).text( text == null ? '' : text ).html();
	}

	/* -----------------------------------------------------------------
	 * Tela: Adicionar usuários
	 * -------------------------------------------------------------- */

	function initAdicionar() {
		var $textarea = $( '#bbl-bulk' );

		if ( ! $textarea.length ) {
			return;
		}

		var $preview = $( '#bbl-preview' );
		var $add = $( '#bbl-add' );
		var $box = $( '#bbl-preview-box' );
		var $rows = $( '#bbl-preview-rows' );
		var $summary = $( '#bbl-preview-summary' );
		var $fb = $( '#bbl-add-feedback' );

		$textarea.on( 'input', function () {
			$add.prop( 'disabled', true );
			$box.prop( 'hidden', true );
		} );

		$preview.on( 'click', function () {
			var text = $textarea.val();

			if ( ! $.trim( text ) ) {
				feedback( $fb, 'Cole ao menos uma linha para conferir.', 'erro' );
				return;
			}

			$preview.prop( 'disabled', true ).text( 'Conferindo...' );

			post( 'bbl_users_preview', { text: text } )
				.done( function ( res ) {
					if ( ! res.success ) {
						feedback( $fb, res.data.message || 'Não foi possível conferir a lista.', 'erro' );
						return;
					}

					var d = res.data;
					var html = '';

					$.each( d.rows, function ( i, row ) {
						var situacao;

						if ( row.erro ) {
							situacao = '<span class="bbl-badge bbl-badge--erro">' + esc( row.erro ) + '</span>';
						} else if ( row.existente ) {
							situacao = '<span class="bbl-badge bbl-badge--existente">Já tem conta</span>';
						} else {
							situacao = '<span class="bbl-badge bbl-badge--criado">Pronto para salvar</span>';
						}

						html += '<tr><td>' + esc( row.nome ) + '</td><td>' + esc( row.email ) + '</td><td>' + situacao + '</td></tr>';
					} );

					$rows.html( html );
					$summary.html(
						'<strong>' + d.validos + '</strong> de <strong>' + d.total + '</strong> linha(s) prontas para salvar.'
					);
					$box.prop( 'hidden', false );
					$add.prop( 'disabled', d.validos === 0 );
					feedback( $fb, '', 'info' );
				} )
				.fail( function () {
					feedback( $fb, 'A conferência falhou. Recarregue a página e tente de novo.', 'erro' );
				} )
				.always( function () {
					$preview.prop( 'disabled', false ).text( 'Conferir lista' );
				} );
		} );

		$add.on( 'click', function () {
			$add.prop( 'disabled', true ).text( 'Salvando...' );

			post( 'bbl_users_add', { text: $textarea.val() } )
				.done( function ( res ) {
					if ( ! res.success ) {
						feedback( $fb, res.data.message || 'Não foi possível salvar.', 'erro' );
						return;
					}

					feedback( $fb, res.data.message, 'ok' );
					window.setTimeout( function () {
						window.location.reload();
					}, 800 );
				} )
				.fail( function () {
					feedback( $fb, 'O salvamento falhou. Tente novamente.', 'erro' );
				} )
				.always( function () {
					$add.text( 'Salvar no rascunho' );
				} );
		} );

		// Edição inline do rascunho.
		$( '#bbl-draft-table' ).on( 'click', '.bbl-save-row', function () {
			var $tr = $( this ).closest( 'tr' );
			var $btn = $( this );

			$btn.prop( 'disabled', true );

			post( 'bbl_users_update_row', {
				id: $tr.data( 'id' ),
				nome: $tr.find( '.bbl-field-nome' ).val(),
				email: $tr.find( '.bbl-field-email' ).val()
			} )
				.done( function ( res ) {
					if ( res.success ) {
						$btn.text( 'Salvo' );
						window.setTimeout( function () {
							$btn.text( 'Salvar' );
						}, 1500 );
					} else {
						window.alert( res.data.message );
					}
				} )
				.always( function () {
					$btn.prop( 'disabled', false );
				} );
		} );

		$( '#bbl-draft-table' ).on( 'click', '.bbl-delete-row', function () {
			var $tr = $( this ).closest( 'tr' );

			if ( ! window.confirm( 'Remover esta linha do rascunho?' ) ) {
				return;
			}

			post( 'bbl_users_delete_row', { id: $tr.data( 'id' ) } ).done( function () {
				$tr.fadeOut( 200, function () {
					$tr.remove();
				} );
			} );
		} );
	}

	/* -----------------------------------------------------------------
	 * Tela: Criar usuários
	 * -------------------------------------------------------------- */

	function initCriar() {
		var $create = $( '#bbl-create' );

		if ( ! $create.length ) {
			return;
		}

		var $modal = $( '#bbl-modal' );
		var $progress = $( '#bbl-progress' );
		var $fill = $( '#bbl-progress-fill' );
		var $label = $( '#bbl-progress-label' );
		var $table = $( '#bbl-create-table' );

		function pendingIds() {
			var ids = [];

			$table.find( 'tr[data-status="rascunho"]' ).each( function () {
				ids.push( $( this ).data( 'id' ) );
			} );

			return ids;
		}

		function levelLabel() {
			var $sel = $( '#bbl-level' );

			if ( ! $sel.is( 'select' ) || $sel.val() === '0' ) {
				return 'sem nível PMPro';
			}

			return 'com o nível ' + $sel.find( 'option:selected' ).text();
		}

		$create.on( 'click', function () {
			var total = pendingIds().length;

			if ( ! total ) {
				return;
			}

			$( '#bbl-modal-text' ).text(
				'Você está prestes a criar ' + total + ' usuário(s) ' + levelLabel() + '.'
			);
			$modal.prop( 'hidden', false );
			$( '#bbl-modal-confirm' ).trigger( 'focus' );
		} );

		$( '#bbl-modal-cancel' ).on( 'click', function () {
			$modal.prop( 'hidden', true );
		} );

		$modal.on( 'click', function ( e ) {
			if ( e.target === $modal[ 0 ] ) {
				$modal.prop( 'hidden', true );
			}
		} );

		$( document ).on( 'keyup', function ( e ) {
			if ( e.key === 'Escape' && ! $modal.prop( 'hidden' ) ) {
				$modal.prop( 'hidden', true );
			}
		} );

		$( '#bbl-modal-confirm' ).on( 'click', function () {
			$modal.prop( 'hidden', true );
			run();
		} );

		function paint( result ) {
			var $tr = $table.find( 'tr[data-id="' + result.id + '"]' );

			if ( ! $tr.length ) {
				return;
			}

			var labels = {
				criado: [ 'Criado', 'criado' ],
				existente: [ 'Já existia', 'existente' ],
				erro: [ 'Erro', 'erro' ],
				rascunho: [ 'Rascunho', 'rascunho' ]
			};

			var item = labels[ result.status ] || labels.rascunho;

			$tr.attr( 'data-status', result.status );
			$tr.find( '.bbl-cell-status' ).html(
				'<span class="bbl-badge bbl-badge--' + item[ 1 ] + '">' + item[ 0 ] + '</span>'
			);
			$tr.find( '.bbl-cell-login' ).html( result.username ? '<code>' + esc( result.username ) + '</code>' : '' );
			$tr.find( '.bbl-cell-msg' ).text( result.message || '' );
		}

		function run() {
			var queue = pendingIds();
			var total = queue.length;
			var done = 0;
			var size = CFG.chunkSize || 5;

			$create.prop( 'disabled', true );
			$progress.prop( 'hidden', false );
			$label.text( 'Criando 0 de ' + total + '...' );

			function step() {
				if ( ! queue.length ) {
					$label.text( 'Concluído: ' + done + ' de ' + total + ' linha(s) processada(s).' );
					$create.text( 'Processamento concluído' );
					return;
				}

				var chunk = queue.splice( 0, size );

				post( 'bbl_users_create_chunk', {
					ids: chunk,
					level_id: $( '#bbl-level' ).val() || 0
				} )
					.done( function ( res ) {
						if ( ! res.success ) {
							$label.text( res.data.message || 'O processamento parou por causa de um erro.' );
							$create.prop( 'disabled', false );
							return;
						}

						$.each( res.data.results, function ( i, result ) {
							paint( result );
						} );

						done += chunk.length;
						$fill.css( 'width', Math.round( ( done / total ) * 100 ) + '%' );
						$label.text( 'Criando ' + done + ' de ' + total + '...' );

						step();
					} )
					.fail( function () {
						$label.text(
							'A conexão falhou depois de ' + done + ' de ' + total +
							'. As linhas já criadas continuam salvas. Recarregue a página para retomar de onde parou.'
						);
						$create.prop( 'disabled', false );
					} );
			}

			step();
		}
	}

	/* -----------------------------------------------------------------
	 * Tela: Configurações
	 * -------------------------------------------------------------- */

	function initConfig() {
		$( '#bbl-test-email' ).on( 'click', function () {
			var $btn = $( this );
			var $fb = $( '#bbl-test-feedback' );

			$btn.prop( 'disabled', true ).text( 'Enviando...' );
			feedback( $fb, '', 'info' );

			post( 'bbl_users_test_email', { to: $( '#bbl-test-to' ).val() } )
				.done( function ( res ) {
					feedback( $fb, res.data.message, res.success ? 'ok' : 'erro' );
				} )
				.fail( function () {
					feedback( $fb, 'A requisição falhou. Verifique a conexão e tente de novo.', 'erro' );
				} )
				.always( function () {
					$btn.prop( 'disabled', false ).text( 'Enviar mensagem de teste' );
				} );
		} );

		$( '#bbl-test-insert' ).on( 'click', function () {
			var $btn = $( this );
			var $fb = $( '#bbl-insert-feedback' );

			$btn.prop( 'disabled', true ).text( 'Testando...' );
			feedback( $fb, '', 'info' );

			post( 'bbl_users_test_insert' )
				.done( function ( res ) {
					feedback( $fb, res.data.message, res.success ? 'ok' : 'erro' );
				} )
				.fail( function () {
					feedback( $fb, 'A requisição falhou. Verifique a conexão e tente de novo.', 'erro' );
				} )
				.always( function () {
					$btn.prop( 'disabled', false ).text( 'Executar teste' );
				} );
		} );

		/**
		 * Lê o conteúdo do editor, respeitando o modo em que ele está.
		 * No modo visual o valor mora no TinyMCE; no modo texto, no textarea.
		 */
		function editorContent( id ) {
			var editor = window.tinymce && window.tinymce.get( id );

			if ( editor && ! editor.isHidden() ) {
				return editor.getContent();
			}

			return $( '#' + id ).val() || '';
		}

		function setEditorContent( id, content ) {
			var editor = window.tinymce && window.tinymce.get( id );

			if ( editor && ! editor.isHidden() ) {
				editor.setContent( content );
			}

			$( '#' + id ).val( content );
		}

		function insertIntoEditor( id, text ) {
			var editor = window.tinymce && window.tinymce.get( id );

			if ( editor && ! editor.isHidden() ) {
				editor.execCommand( 'mceInsertContent', false, text );
				return true;
			}

			var el = document.getElementById( id );

			if ( ! el ) {
				return false;
			}

			var start = el.selectionStart || 0;
			var end = el.selectionEnd || 0;

			el.value = el.value.slice( 0, start ) + text + el.value.slice( end );
			el.selectionStart = el.selectionEnd = start + text.length;
			el.focus();

			return true;
		}

		// Marcadores: inserem no último campo em foco. Sem foco algum, copiam.
		var lastFocused = null;

		$( document ).on( 'focus', '#email-subject, #email-subject-existing', function () {
			lastFocused = { type: 'input', el: this };
		} );

		$( document ).on( 'focus', '#email_body, #email_body_existing', function () {
			lastFocused = { type: 'editor', id: this.id };
		} );

		// O TinyMCE roda num iframe, então o foco dele não sobe pelo document.
		$( window ).on( 'load', function () {
			if ( ! window.tinymce ) {
				return;
			}

			$.each( [ 'email_body', 'email_body_existing' ], function ( i, id ) {
				var editor = window.tinymce.get( id );

				if ( editor ) {
					editor.on( 'focus', function () {
						lastFocused = { type: 'editor', id: id };
					} );
				}
			} );
		} );

		$( document ).on( 'click', '.bbl-token', function () {
			var $btn = $( this );
			var token = $btn.data( 'token' );
			var label = 'inserido';
			var done = false;

			if ( lastFocused && lastFocused.type === 'editor' ) {
				done = insertIntoEditor( lastFocused.id, token );
			} else if ( lastFocused && lastFocused.type === 'input' ) {
				var el = lastFocused.el;
				var start = el.selectionStart || 0;
				var end = el.selectionEnd || 0;

				el.value = el.value.slice( 0, start ) + token + el.value.slice( end );
				el.selectionStart = el.selectionEnd = start + token.length;
				el.focus();
				done = true;
			}

			if ( ! done ) {
				label = 'copiado';

				if ( window.navigator.clipboard ) {
					window.navigator.clipboard.writeText( token );
				}
			}

			var original = $btn.text();
			$btn.addClass( 'bbl-token--ok' ).text( label );

			window.setTimeout( function () {
				$btn.removeClass( 'bbl-token--ok' ).text( original );
			}, 900 );
		} );

		// Prévia da mensagem.
		var $mailModal = $( '#bbl-mail-preview' );

		$( '.bbl-preview-mail' ).on( 'click', function () {
			var $btn = $( this );
			var editorId = $btn.data( 'editor' );

			$btn.prop( 'disabled', true ).text( 'Montando...' );

			post( 'bbl_users_preview_mail', {
				subject: $( $btn.data( 'subject' ) ).val(),
				body: editorContent( editorId ),
				sem_senha: $btn.data( 'sem-senha' ) ? 1 : 0
			} )
				.done( function ( res ) {
					if ( ! res.success ) {
						window.alert( res.data.message || 'Não foi possível montar a prévia.' );
						return;
					}

					$( '#bbl-mail-preview-subject' ).text( res.data.subject );
					$( '#bbl-mail-preview-body' ).html( res.data.html );

					var $aviso = $( '#bbl-mail-preview-aviso' );

					if ( res.data.aviso ) {
						$aviso.text( res.data.aviso ).prop( 'hidden', false );
					} else {
						$aviso.prop( 'hidden', true );
					}

					$mailModal.prop( 'hidden', false );
					$( '#bbl-mail-preview-close' ).trigger( 'focus' );
				} )
				.fail( function () {
					window.alert( 'A requisição da prévia falhou.' );
				} )
				.always( function () {
					$btn.prop( 'disabled', false ).text( 'Ver prévia' );
				} );
		} );

		$( '#bbl-mail-preview-close' ).on( 'click', function () {
			$mailModal.prop( 'hidden', true );
		} );

		$mailModal.on( 'click', function ( e ) {
			if ( e.target === $mailModal[ 0 ] ) {
				$mailModal.prop( 'hidden', true );
			}
		} );

		$( document ).on( 'keyup', function ( e ) {
			if ( e.key === 'Escape' && ! $mailModal.prop( 'hidden' ) ) {
				$mailModal.prop( 'hidden', true );
			}
		} );

		// Restaurar o texto padrão.
		$( '.bbl-restore' ).on( 'click', function () {
			var $btn = $( this );
			var key = $btn.data( 'default' );
			var defaults = CFG.defaults || {};

			if ( ! defaults[ key ] ) {
				return;
			}

			if ( ! window.confirm( 'Substituir o texto atual pelo padrão? O que você escreveu será perdido.' ) ) {
				return;
			}

			setEditorContent( $btn.data( 'editor' ), defaults[ key ] );
		} );

		$( '#bbl-pass-preview' ).on( 'click', function () {
			var $box = $( '#bbl-pass-samples' );

			post( 'bbl_users_preview_pass', {
				opts: {
					length: $( '#pass-length' ).val(),
					lower: $( '#pass-lower' ).is( ':checked' ) ? 1 : 0,
					upper: $( '#pass-upper' ).is( ':checked' ) ? 1 : 0,
					numbers: $( '#pass-numbers' ).is( ':checked' ) ? 1 : 0,
					symbols: $( '#pass-symbols' ).is( ':checked' ) ? 1 : 0,
					symbols_set: $( '#pass-symbols-set' ).val(),
					no_ambiguous: $( '#pass-no-ambiguous' ).is( ':checked' ) ? 1 : 0
				}
			} ).done( function ( res ) {
				if ( ! res.success ) {
					return;
				}

				var html = '';

				$.each( res.data.samples, function ( i, sample ) {
					html += '<code>' + esc( sample ) + '</code>';
				} );

				$box.html( html );
			} );
		} );
	}

	$( function () {
		initAdicionar();
		initCriar();
		initConfig();
	} );
} )( jQuery );
