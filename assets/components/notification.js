App.event.ContentLoaded( () => {

	const tl = $all( 'toast' )

	if ( !tl.length ) return

	const tc = $get(
		'section#notifications',
		{ class: 'fixed top right', role: 'list' },
	).prependTo( document.body )

	tl.forEach( ( t, i ) => {

		console.log(t.animations())

		if ( !t.attr.style.get( '--timeout' ) ) {

			t.classList.add( 'hidden' )

			const remove = () => {
				const tp = t.parentElement

				t.attr.class.add( 'hidden' )

				t.on( 'transitionend', ( ta ) => {
					if ( ta.propertyName !== t.animations().longest.property ) return
					t.remove()
					if ( tp && tp.children.length === 0 ) tp.remove()
				} )
			}

			const to = parseInt( t.attr.get( 'timeout' ) ?? '0' )

			setTimeout( () => {
					t.attr.class.remove( 'hidden' )

					if ( to ) {

						const tob = $new( '<div class="progress-bar"></div>' )
						t.attr.style.set( '--timeout', to + 'ms' )
						tob.appendTo( t )

						t.on( 'click', () => {
							tob.fadeOut()
							t.attr.set( 'timeout', 'stopped' )
							t.off( 'animationend', remove )
						}, { once: true } )
						t.on( 'animationend', remove, { once: true } )
					}
				},
				// The 250 the stagger time when rendering multiple toasts
				250 + (i * (250 / 2)) )

			t.$( 'button.close' ).on( 'click', remove )

			t.attr.set( 'role', 'listitem' )
			t.appendTo( /** @type {HTMLElement} */ tc )

		}
	} )
}, true )