/**
 * Siparis listesindeki hizli takip no ekleme formu.
 *
 * Tek form ustunde birden fazla kez tikla korumasi: gonderim sirasinda
 * buton kilitlenir, sonuc gelince kolon tamamen sunucudan gelen HTML ile
 * degistirilir (durum rozeti / on izleme linki de guncel gelsin diye).
 */
( function () {
	'use strict';

	if ( 'undefined' === typeof WPKT_Admin ) {
		return;
	}

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target.closest( '.wpkt-quick-form' );

		if ( ! form ) {
			return;
		}

		event.preventDefault();

		var input = form.querySelector( '.wpkt-quick-input' );
		var button = form.querySelector( 'button' );
		var errorEl = form.querySelector( '.wpkt-quick-error' );
		var number = input.value.trim();

		if ( '' === number ) {
			input.focus();
			return;
		}

		button.disabled = true;
		errorEl.hidden = true;

		var data = new FormData();
		data.append( 'action', 'wpkt_quick_save_tracking' );
		data.append( 'nonce', WPKT_Admin.nonce );
		data.append( 'order_id', form.dataset.orderId );
		data.append( 'tracking_number', number );

		fetch( WPKT_Admin.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				if ( result && result.success ) {
					var cell = form.closest( 'td' );
					if ( cell ) {
						cell.innerHTML = result.data.html;
					}
					return;
				}

				button.disabled = false;
				errorEl.textContent = ( result && result.data && result.data.message ) || WPKT_Admin.strings.network;
				errorEl.hidden = false;
			} )
			.catch( function () {
				button.disabled = false;
				errorEl.textContent = WPKT_Admin.strings.network;
				errorEl.hidden = false;
			} );
	} );
}() );
