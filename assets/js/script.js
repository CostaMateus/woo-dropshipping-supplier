
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
                    console.log( err );
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

        // =========================================================

        $( "#doaction"  ).click( function ( e ) {
            const action = $( "#bulk-action-selector-top"    ).val();
            preventSubmitBulkActions( e, action );
        } );

        $( "#doaction2" ).click( function ( e ) {
            const action = $( "#bulk-action-selector-bottom" ).val();
            preventSubmitBulkActions( e, action );
        } );

        function preventSubmitBulkActions( e, a )
        {
            if ( a == "-1" ) return;

            const query  = window.location.search;
            const params = new URLSearchParams( query );

            const page   = params.has( "page" ) ? params.get( "page" ) : 0;
            if ( page == 0 || page != "woo_ds_supplier" ) return;

            const tab    = params.has( "tab" ) ? params.get( "tab" ) : 0;
            const spp_tab = [ "shopkeepers", "orders" ];
            if ( tab  == 0 || !spp_tab.includes( tab ) ) return;

            e.preventDefault();

            if ( $( '#dsm-users-list-table-form' ).length > 0 )
            {
                const users = $( '#dsm-users-list-table-form input[name^="users"]:checked' );

                if ( users.length == 0 )
                {
                    alert( woo_dsm_params.messages.select_users );
                    return;
                }

                if ( a == "del_users" )
                {
                    if ( confirm( woo_dsm_params.messages.del_users ) )
                    {
                        $( "#dsm-modal-load" ).removeClass( "dsm-d-none" );
                        $( "#dsm-users-list-table-form" ).submit();
                    }
                }
                else if ( a == "del_products" )
                {
                    if ( confirm( woo_dsm_params.messages.del_products ) )
                    {
                        $( "#dsm-modal-load" ).removeClass( "dsm-d-none" );
                        $( "#dsm-users-list-table-form" ).submit();
                    }
                }
                else
                {
                    $( "#dsm-modal-load" ).removeClass( "dsm-d-none" );
                    $( "#dsm-users-list-table-form" ).submit();
                }

                return;
            }

            if ( $( '#dsm-orders-list-table-form' ).length > 0 )
            {
                const orders = $( '#dsm-orders-list-table-form input[name^="orders"]:checked' );

                if ( orders.length == 0 )
                {
                    alert( woo_dsm_params.messages.select_orders );
                    return;
                }

                $( "#dsm-modal-load" ).removeClass( "dsm-d-none" );
                $( "#dsm-orders-list-table-form" ).submit();

                return;
            }
        }

        // =========================================================

        $( "#dsm-general-form" ).on( "submit", function() {
            $( "#dsm-modal-load" ).removeClass( "dsm-d-none" );
        } );

        $( "#bulk-action-selector-top"    ).on( "change", function() {
            show_bulk_actions_message( $( this ).val() );
        } );
        $( "#bulk-action-selector-bottom" ).on( "change", function() {
            show_bulk_actions_message( $( this ).val() );
        } );

        function show_bulk_actions_message( val )
        {
            $( ".dsm-bulkactions-del-orders"  ).addClass( "dsm-d-none" );
            $( ".dsm-bulkactions-del-users"   ).addClass( "dsm-d-none" );
            $( ".dsm-bulkactions-upd-shipped" ).addClass( "dsm-d-none" );

            switch ( val )
            {
                case "del_orders":
                    $( ".dsm-bulkactions-del-orders"  ).removeClass( "dsm-d-none" );
                break;

                case "del_users":
                    $( ".dsm-bulkactions-del-users"   ).removeClass( "dsm-d-none" );
                break;

                case "upd_shipped":
                    $( ".dsm-bulkactions-upd_shipped" ).removeClass( "dsm-d-none" );
                break;
            }
        }

        // =========================================================

        $( "#dsm-instructions-toggle" ).click( function( e ) {
            if ( $( "#dsm-instructions" ).hasClass( "dsm-d-none" ) )
                $( "#dsm-instructions" ).removeClass( "dsm-d-none" );
            else
                $( "#dsm-instructions" ).addClass( "dsm-d-none" );
        } );

    } );
}( jQuery ) );