import '../css/index.scss';

const main = () => {
	const PREFIX = 'cmls-remote-ads-txt';
	const JSPREFIX = PREFIX.replace( /\-/g, '_' );
	const AJAXDATA = window[ JSPREFIX ];
	const form = document.getElementById( 'cmls-wp-remote-ads-txt' );
	const cacheMessages = Array.from(
		document.getElementsByClassName( 'cache-message' )
	);

	cacheMessages.forEach( ( msgbox ) => {
		const refreshButton = msgbox.querySelector(
			'a.cmls-remote-ads-rebuild-cache'
		);
		if ( refreshButton ) {
			refreshButton.addEventListener( 'click', async ( e ) => {
				e.preventDefault();

				if ( e.target.classList.contains( 'loading' ) ) {
					return false;
				}

				const msgbox =
					e.target.parentNode.parentNode.querySelector(
						'.cache-time'
					);
				const handle = e.target.getAttribute( 'data-handle' );
				if (
					handle &&
					AJAXDATA?.rebuild_actions?.[ handle ] &&
					msgbox
				) {
					const data = new FormData();
					data.append( 'action', AJAXDATA.rebuild_actions[ handle ] );
					data.append( 'nonce', AJAXDATA.nonce );

					msgbox.style.opacity = 0.5;
					e.target.classList.add( 'loading', 'disabled' );
					e.target.blur();

					await fetch( AJAXDATA.ajaxurl, {
						method: 'POST',
						credentials: 'same-origin',
						cache: 'no-cache',
						body: data,
					} )
						.then( ( response ) => response.json() )
						.then( ( content ) => {
							msgbox.innerText = content.data.msg;
							AJAXDATA.nonce = content.data.nonce;
						} )
						.catch( ( error ) => {
							console.error( error );
						} )
						.finally( () => {
							msgbox.style.opacity = 1;
							e.target.classList.remove( 'loading', 'disabled' );
						} );
				}
			} );
		}
	} );

	// Update display of remotes' cache mtimes
	const refreshMTimes = async () => {
		const data = new FormData();
		data.append( 'action', AJAXDATA.rebuild_actions.get_mtime );
		data.append( 'nonce', AJAXDATA.nonce );
		await fetch( AJAXDATA.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			cache: 'no-cache',
			body: data,
		} )
			.then( ( response ) => response.json() )
			.then( ( content ) => {
				const handles = content.data.msg;
				if ( handles ) {
					Object.keys( handles ).forEach( ( handle ) => {
						const msgbox = document.querySelector(
							`.cache-message.${ handle } .cache-time`
						);
						if ( msgbox ) {
							msgbox.innerText = handles[ handle ];
						}
					} );
				}
				AJAXDATA.nonce = content.data.nonce;
				setTimeout( () => {
					refreshMTimes();
				}, 600000 );
			} )
			.catch( ( error ) => {
				console.error( error );
			} );
	};

	setTimeout( () => {
		refreshMTimes();
	}, 1000 );

	// Handler Cron rebuild button
	const cronRebuild = document.querySelector(
		'.cmls-remote-ads-rebuild-cron'
	);
	cronRebuild.addEventListener( 'click', async ( e ) => {
		e.preventDefault();
		if ( e.target.classList.contains( 'loading' ) ) {
			return false;
		}
		const data = new FormData();
		data.append( 'action', AJAXDATA.rebuild_actions.rebuild_cron );
		data.append( 'nonce', AJAXDATA.nonce );
		e.target.style.opacity = 0.5;
		e.target.classList.add( 'loading', 'disabled' );
		e.target.blur();

		await fetch( AJAXDATA.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			cache: 'no-cache',
			body: data,
		} )
			.then( ( response ) => response.json() )
			.then( ( content ) => {
				AJAXDATA.nonce = content.data.nonce;
			} )
			.catch( ( error ) => {
				console.error( error );
			} )
			.finally( () => {
				e.target.style.opacity = 1;
				e.target.classList.remove( 'loading', 'disabled' );
			} );
	} );

	// Dismiss success messages on a time
	setTimeout( function () {
		const dismiss = document.querySelector(
			'#setting-error-settings_updated.notice-success .notice-dismiss'
		);
		if ( dismiss ) {
			dismiss.click();
		}
	}, 8000 );

	if ( form ) {
		// Handle dirty form
		const formState = {
			isDirty: false,
			initialValues: {},
			fields: Array.from( form.elements ),
		};
		const testField = ( field ) => {
			if (
				! field.name ||
				[ 'submit', 'button', 'hidden' ].includes(
					field.type.toLowerCase()
				)
			) {
				return false;
			}
			return true;
		};
		const checkValues = ( e ) => {
			formState.fields.some( ( field ) => {
				if ( ! testField( field ) ) {
					return false;
				}
				if ( formState.initialValues[ field.name ] !== field.value ) {
					formState.isDirty = true;
					return true;
				}
				formState.isDirty = false;
			} );
		};
		formState.fields.forEach( ( field ) => {
			if ( ! testField( field ) ) {
				return false;
			}
			formState.initialValues[ field.name ] = field.value;
			field.addEventListener( 'change', checkValues );
			field.addEventListener( 'input', checkValues );
		} );
		window.onbeforeunload = () => {
			if ( formState.isDirty ) {
				return 'You have unsaved changes! Are you sure you want to abandon them?';
			}
		};

		// Handle form validation
		var validating = false;
		const handleFormSubmit = async ( e ) => {
			e.preventDefault();
			if ( validating ) {
				return;
			}
			validating = true;
			const urls = Array.from(
				document.querySelectorAll( 'input.remote-url' )
			);
			let errors = false;
			let submitButton = e.target.querySelector(
				'input.button-primary[type="submit"]'
			);
			let originalButton = submitButton.value + '';
			submitButton.value = 'Validating...';
			submitButton.disabled = true;
			await Promise.all(
				urls.map( async ( i ) => {
					if ( i.value ) {
						const data = new FormData();
						data.append(
							'action',
							AJAXDATA.rebuild_actions.validate_remote
						);
						data.append( 'url', i.value );
						data.append( 'nonce', AJAXDATA.nonce );
						await fetch( AJAXDATA.ajaxurl, {
							method: 'POST',
							credentials: 'same-origin',
							cache: 'no-cache',
							body: data,
						} )
							.then( ( response ) => response.json() )
							.then( ( content ) => {
								if ( ! content.success ) {
									errors = true;
									let label = i.closest( '.error-msg' );
									if ( ! label ) {
										label =
											document.createElement( 'label' );
										label.setAttribute( 'for', i.id );
										label.classList.add(
											'error-msg',
											'error'
										);
										i.after( label );
									}
									label.innerText = content.data.msg;
									i.classList.add( 'error' );
									i.scrollIntoView();
								}
								AJAXDATA.nonce = content.data.nonce;
							} )
							.catch( ( error ) => {
								console.error( error );
							} )
							.finally( () => {} );
					}
				} )
			);

			validating = false;

			if ( errors ) {
				submitButton.value = 'Fix the errors and try again!';
				submitButton.disabled = false;
				return false;
			}

			submitButton.value = 'Submitting form...';
			formState.isDirty = false;
			//window.removeEventListener( 'submit', handleFormSubmit );
			form.removeEventListener( 'submit', handleFormSubmit );
			form.submit();
		};
		//window.addEventListener( 'submit', handleFormSubmit );
		form.addEventListener( 'submit', handleFormSubmit );
	}
};

if ( [ 'complete', 'loaded', 'interactive' ].includes( document.readyState ) ) {
	main();
} else {
	window.addEventListener( 'DOMContentLoaded', main );
}
