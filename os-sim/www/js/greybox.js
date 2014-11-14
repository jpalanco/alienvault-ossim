/* Greybox Redux
 * Required: http://jquery.com/
 * Written by: John Resig
 * Based on code by: 4mir Salihefendic (http://amix.dk)
 * License: LGPL (read more in LGPL.txt)
 * 2009-05-1 modified by jmalbarracin. Added GB_TYPE. Fixed  total document width/height
 * 2009-06-4 modified by jmalbarracin. Support of width %, height %
 * 2012-02-01 modified by fjmnav and jmalbarracin. Added move, scale and resize
 * 2012-03-20 Deleting GB_DONE configuration --> Now the container is loaded everytime the GB is called.
 */

function GB_show(caption, url, height, width, nohide, post)
{
    if(typeof(GB_TYPE) != 'undefined' && typeof(parent.LB_TYPE) != 'undefined' && parent.LB_TYPE != GB_TYPE)
    {
        parent.LB_TYPE = GB_TYPE;
    }

    if(typeof(parent.LB_FLAG) != 'undefined' && typeof(parent.is_lightbox_opened) == 'function')
    {
        parent.LB_FLAG = ( parent.is_lightbox_opened() > 0 ) ? true : false;
    }

    if(typeof(parent.LB_show) == 'function')
    {
        parent.LB_show(caption, url, height, width, nohide, post);
    }

    return false;
}

function GB_show_multiple(caption, url, height, width)
{
    if(typeof(GB_TYPE) != 'undefined' && typeof(parent.LB_TYPE) != 'undefined' && parent.LB_TYPE != GB_TYPE)
    {
        parent.LB_TYPE = GB_TYPE;
    }

    if(typeof(parent.LB_FLAG) != 'undefined')
    {
        parent.LB_FLAG = true;
    }

    if(typeof(parent.LB_show) == 'function')
    {
        parent.LB_show(caption, url, height, width, false, false);
    }

    return false;
}

function GB_show_nohide(caption, url, height, width)
{
    GB_show(caption, url, height, width, true);
}

function GB_show_post(caption, url, height, width)
{
    GB_show(caption, url, height, width, false, true);
}

function GB_hide()
{
    if(typeof(parent.GB_hide) == 'function')
    {
        parent.GB_hide();
    }
}

function GB_close()
{
    if(typeof(parent.GB_close) == 'function')
    {
        parent.GB_close();
    }
}

function GB_makeurl(url)
{
	var loc = window.location;
	if (!url.match(/^\//))
	{
		var uri = loc.pathname.split('/'); uri.pop();
		url = uri.join('/') + '/' + url;
	}
	url = '' + loc.protocol + '//' + loc.hostname + (loc.port != '' ? ':' + loc.port : '') + url;
	//console.log(url);
	return url;
}
