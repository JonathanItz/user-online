( function( $ ) {
    $( document ).on( 'heartbeat-send', function ( event, data ) {
        console.log('send');
        // Add additional data to Heartbeat data.
        data.interval = 15
        data.user_id  = 0;
        data.action   = 'myplugin_customfield';
    });

    $( document ).on( 'heartbeat-tick', function ( event, data ) {
        console.log('return');
        // Check for our data, and use it.
        if ( ! data.myplugin_customfield_hashed ) {
            return;
        }

        alert( 'The hash is ' + data.myplugin_customfield_hashed );
    });
})( jQuery );
