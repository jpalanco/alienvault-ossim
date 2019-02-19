/**
 * @summary     DataTables Plugins
 * @author      AlienVault
 *
 */

/*
Reload ajax with new url
*/

jQuery.fn.dataTableExt.oApi.fnReloadAjax = function ( oSettings, sNewSource, fnCallback, bStandingRedraw )
{
	// DataTables 1.10 compatibility - if 1.10 then `versionCheck` exists.
	// 1.10's API has ajax reloading built in, so we use those abilities
	// directly.
	if ( jQuery.fn.dataTable.versionCheck ) {
		var api = new jQuery.fn.dataTable.Api( oSettings );

		if ( sNewSource ) {
			api.ajax.url( sNewSource ).load( fnCallback, !bStandingRedraw );
		}
		else {
			api.ajax.reload( fnCallback, !bStandingRedraw );
		}
		return;
	}

	if ( sNewSource !== undefined && sNewSource !== null ) {
		oSettings.sAjaxSource = sNewSource;
	}

	// Server-side processing should just call fnDraw
	if ( oSettings.oFeatures.bServerSide ) {
		this.fnDraw();
		return;
	}

	this.oApi._fnProcessingDisplay( oSettings, true );
	var that = this;
	var iStart = oSettings._iDisplayStart;
	var aData = [];

	this.oApi._fnServerParams( oSettings, aData );

	oSettings.fnServerData.call( oSettings.oInstance, oSettings.sAjaxSource, aData, function(json) {
		/* Clear the old information from the table */
		that.oApi._fnClearTable( oSettings );

		/* Got the data - add it to the table */
		var aData =  (oSettings.sAjaxDataProp !== "") ?
			that.oApi._fnGetObjectDataFn( oSettings.sAjaxDataProp )( json ) : json;

		for ( var i=0 ; i<aData.length ; i++ )
		{
			that.oApi._fnAddData( oSettings, aData[i] );
		}

		oSettings.aiDisplay = oSettings.aiDisplayMaster.slice();

		that.fnDraw();

		if ( bStandingRedraw === true )
		{
			oSettings._iDisplayStart = iStart;
			that.oApi._fnCalculateEnd( oSettings );
			that.fnDraw( false );
		}

		that.oApi._fnProcessingDisplay( oSettings, false );

		/* Callback user function - for event handlers etc */
		if ( typeof fnCallback == 'function' && fnCallback !== null )
		{
			fnCallback( oSettings );
		}
	}, oSettings );
};


jQuery.fn.dataTableExt.oApi.fnSetFilteringDelay = function ( oSettings, iDelay ) {
    var _that = this;

    if ( iDelay === undefined ) {
        iDelay = 250;
    }

    this.each( function ( i ) {
        $.fn.dataTableExt.iApiIndex = i;
        var
            $this = this,
            oTimerId = null,
            sPreviousSearch = null,
            anControl = $( 'input', _that.fnSettings().aanFeatures.f );

            anControl.unbind( 'keyup' ).bind( 'keyup', function() {
            var $$this = $this;

            if (sPreviousSearch === null || sPreviousSearch != anControl.val()) {
                window.clearTimeout(oTimerId);
                sPreviousSearch = anControl.val();
                oTimerId = window.setTimeout(function() {
                    $.fn.dataTableExt.iApiIndex = i;
                    _that.fnFilter( anControl.val() );
                }, iDelay);
            }
        });

        return this;
    } );
    return this;
};


jQuery.fn.dataTableExt.oApi.fnStandingRedraw = function(oSettings, start, end)
{
    if(oSettings.oFeatures.bServerSide === false)
    {
        if (typeof start == 'undefined' || start == null)
        {
            start = oSettings._iDisplayStart;
        }
        if (typeof end == 'undefined' || end == null)
        {
            end = oSettings._iDisplayEnd;
        }

        if (start == (end - 1) && (start - oSettings._iDisplayLength) >= 0)
        {
            start -= oSettings._iDisplayLength;
        }

        oSettings.oApi._fnReDraw(oSettings);

        // iDisplayStart has been reset to zero - so lets change it back
        oSettings._iDisplayStart = start;
        oSettings.oApi._fnCalculateEnd(oSettings);
    }

    // draw the 'current' page
    oSettings.oApi._fnDraw(oSettings);
};

/* Plugin for sorting by KB,MB,B and Bytes.
 * http://datatables.net/plug-ins/sorting extended to deal with:
 *    560 kb / quota;
 *    5.02 MB
 *    0 bytes / O b
 */


function get_unit(fs_data)
{
    var unit = 1;

    if (fs_data.match(/GB/i))
    {
        unit = 1024 * 1024 * 1024;
    }
    else if (fs_data.match(/MB/i))
    {
        unit = 1024 * 1024;
    }
    else if (fs_data.match(/KB/i))
    {
        unit = 1024;
    }

    return unit;
}


$.fn.dataTableExt.oSort['file-size-asc']  = function(a,b) {

    var x = parseFloat(a);

    if (isNaN(x))
    {
        x = -1;
    }

    var y = parseFloat(b);

    if (isNaN(y))
    {
        y = -1;
    }

    a = a.replace(/\s+?\/.*/,'');
    b = b.replace(/\s+?\/.*/,'');

    var x_unit = get_unit(a);
    var y_unit = get_unit(b);

    x = parseInt(parseFloat(x) * x_unit) || 0;
    y = parseInt(parseFloat(y) * y_unit) || 0;

    return ((x < y) ? -1 : ((x > y) ? 1 : 0));
};


$.fn.dataTableExt.oSort['file-size-desc']  = function(a,b) {

    var x = parseFloat(a);

    if (isNaN(x))
    {
        x = 1;
    }

    var y = parseFloat(b);

    if (isNaN(y))
    {
        y = 1;
    }

    a = a.replace(/\s+?\/.*/,'')
    b = b.replace(/\s+?\/.*/,'')

    var x_unit = get_unit(a);
    var y_unit = get_unit(b);

    x = parseInt(parseFloat(x) * x_unit) || 0;
    y = parseInt(parseFloat(y) * y_unit) || 0;

    return ((x < y) ? 1 : ((x > y) ?  -1 : 0));
};
