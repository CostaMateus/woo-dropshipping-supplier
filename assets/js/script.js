
( function( $ ) {
    "use strict";

    $( function() {
        $( "#set_new_key" ).click( function() {
            const query   = window.location.search;
            const params  = new URLSearchParams( query );
            const user_id = params.has( "user_id" ) ? params.get( "user_id" ) : 0;

            $( "#key_set" ).css( "display", "none" );

            let span      = $( this ).find( "span" );
            span.css( "display", "inline-block" );

            let error     = $( "#set_key_error" );
            error.css( "display", "none" ).html( "" );

            $.ajax( {
                url  : woo_dsm_params.ajax_url,
                type : "POST",
                data : {
                    action : "generate_secret",
                    id     : user_id,
                },
                success: function( response ) {
                    $( "#new_key" ).css( "display", "block" );
                    $( "#woo_ds_new_key" ).val( response.data );
                    span.css( "display", "none" );
                },
                error: function( err ) {
                    error.css( "display", "block" ).html( err.responseJSON.data );
                    span.css( "display", "none" );
                },
            } );
        } );

        $( "#woo_ds_copy" ).click( function() {
            let copyText = document.getElementById( "woo_ds_new_key" );
            copyText.select();
            copyText.setSelectionRange( 0, 99999 );
            document.execCommand( "copy" );

            $( "#key_copied" ).css( "display", "block" );

            setTimeout( function() {
                $( "#key_copied" ).css( "display", "none" );
            }, 4000 );

            return false;
        } );
    } );
}( jQuery ) );