/*
 * The MIT License

Copyright (c) 2010 by Juergen Marsch

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/
function HighLighting(plot, eventHolder, findNearbyFNC, active, highlights)
{         var findNearby = findNearbyFNC;
    var options = plot.getOptions();
    var canvas = plot.getCanvas();
        var target = $(canvas).parent();
        var data = plot.getData();
    if(active && options.grid.hoverable) eventHolder.unbind('mousemove').mousemove(onMouseMove);
    if(active && options.grid.clickable) eventHolder.unbind('click').click(onClick);
    function onMouseMove(e)
    {         triggerClickHoverEvent('plothover', e);}
    function onClick(e)
    {          triggerClickHoverEvent('plotclick', e);}
    function triggerClickHoverEvent(eventname, e)
    {   var r; var item;
                var offset = plot.offset();
        var mouseX = parseInt(e.pageX - offset.left);
        var mouseY = parseInt(e.pageY - offset.top);
        r = findNearby(plot,mouseX, mouseY);
                if(r) item = itemNearBy(r.i,r.j);
                if (options.grid.autoHighlight)
        {          for (var i = 0; i < highlights.length; ++i)
                   {        var h = highlights[i];
                if (h.auto && !(item && h.series == item.series && h.point == item.datapoint))
                         unhighlight(h.series, h.point);
            }
              }
        if (item) {        highlight(item.series, item.datapoint, eventname); }
        var pos = { pageX: e.pageX, pageY: e.pageY };
        target.trigger(eventname, [ pos, item ]);
           }
        function itemNearBy(i,j)
        {        var r;
                r = { datapoint: data[i].data[j],
              dataIndex: j,
              series: data[i],
              seriesIndex: i
                        }
                return r;
        }
    function highlight(s, point, auto)
        {        if(typeof s == "number") s = series[s];
        if(typeof point == "number") point = s.data[point];
        var i = indexOfHighlight(s, point);
        if(i == -1)
                { highlights.push({ series: s, point: point, auto: auto });
          plot.triggerRedrawOverlay();
        }
        else if(!auto) highlights[i].auto = false;
          }
    function unhighlight(s, point)
        {         if(typeof s == "number") s = series[s];
              if(typeof point == "number") point = s.data[point];
              var i = indexOfHighlight(s, point);
              if(i != -1)
                  {         highlights.splice(i, 1);
                plot.triggerRedrawOverlay();
                     }
    }
    function indexOfHighlight(s, p)
        {         for(var i = 0; i < highlights.length; ++i)
                  {         var h = highlights[i];
            if (h.series == s && h.point[0] == p[0] && h.point[1] == p[1]) return i;
        }
        return -1;
    }
}