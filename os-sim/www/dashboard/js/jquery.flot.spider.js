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
/*
Flot plugin for Spiders data sets

  series: {
    spider: { active: true, lineWidth: 8
  }
data: [

  $.plot($("#placeholder"), [{ data: [ [y, x, size], [....], ...], spider: {show: true, lineWidth: 5} } ])

*/

(function ($)

{	var options =

	{	series:

		{ 	spider:

            {	active: false

               ,show: false

               ,spiderSize: 0.8

               ,lineWidth: 0.5

			   ,lineStyle: "rgba(0,0,0,0.5)"

               ,pointSize: 4

			   ,scaleMode: "leg"

			   ,legMin: null

			   ,legMax: null

               ,connection: { width: 4 }

               ,highlight: { opacity: 0.5, mode: "point" }

               ,legs: { font: "11px Arial, Sans-Serif"

					   ,fillStyle: "#666666"

					   ,legScaleMin: 0.95

					   ,legScaleMax: 1.05

					   ,legStartAngle: 20

					  }

        	}

		}

        ,grid:{  tickColor: "rgba(0,0,0,0.15)"

                ,ticks: 8

				,mode: "radar"}

	};

    var data = null, canvas = null, target = null, axes = null, offset = null, highlights = [],opt = null;

        var maxRadius = null, centerLeft = null, centerTop = null;

        var lineRanges = [];



    function init(plot)

    {	plot.hooks.processOptions.push(processOptions);

      	function processOptions(plot,options)

        {	if(options.series.spider.active)

            {	options.grid.show = false;

                plot.hooks.draw.push(draw);

                plot.hooks.bindEvents.push(bindEvents);

                plot.hooks.drawOverlay.push(drawOverlay);

      		}

   		}
   		
   		

        function draw(plot, ctx)

        {	canvas = plot.getCanvas();

          	target = $(canvas).parent();

         	data = plot.getData();

            opt = plot.getOptions();

         	clear(ctx);

          	setupspider();

          	calculateRanges();

         	drawspider(ctx,opt.grid);

    	}

       	function calculateRanges()

		{	var ranges = [];

			if (data[0].spider.scaleMode == 'leg')

			{	for (var j = 0; j < data[0].data.length; j++)

				{	ranges.push(calculateItemRanges(j)); }

			}

			else

			{	var range = calculateRange();

				for(var j = 0; j < data[0].data.length; j++)

				{	ranges.push(range); }

			}

			data.ranges = ranges;

		}

     	function calculateItemRanges(j)

      	{	var min = Number.POSITIVE_INFINITY, max = Number.NEGATIVE_INFINITY;

           	for(var i = 0; i < data.length; i++)

         	{	min = Math.min(min,data[i].data[j][1]);

               	max = Math.max(max,data[i].data[j][1]);

            } 

          	min = min * data[0].spider.legs.legScaleMin;

          	max = max * data[0].spider.legs.legScaleMax;

			if(opt.series.spider.legMin) min = opt.series.spider.legMin;

			if(opt.series.spider.legMax) max = opt.series.spider.legMax;

          	return {min: min, max:max, range: max - min};

        }

		function calculateRange()

		{	var min = Number.POSITIVE_INFINITY, max = Number.NEGATIVE_INFINITY;

			for(var j = 0; j < data[0].data.length; j++)

			{	for(var i = 0; i < data.length; i++)

				{	min = Math.min(min,data[i].data[j][1]);

					max = Math.max(max,data[i].data[j][1]);

				}

			}

          	min = min * data[0].spider.legs.legScaleMin;

          	max = max * data[0].spider.legs.legScaleMax;

			if(opt.series.spider.legMin) min = opt.series.spider.legMin;

			if(opt.series.spider.legMax) max = opt.series.spider.legMax;

          	return {min: min, max:max, range: max - min};			

		}

       	function clear(ctx)

       	{	ctx.clearRect(0,0,canvas.width,canvas.height); }

        function setupspider()

       	{	maxRadius =  Math.min(canvas.width,canvas.height)/2 * data[0].spider.spiderSize;

           	centerTop  = (canvas.height/2);

           	centerLeft = (canvas.width/2);

        }

       	function drawspiderPoints(ctx,cnt,serie,opt)

        {	for(var j = 0; j < serie.data.length; j++)

           	{	drawspiderPoint(ctx,cnt,serie,j,opt); }

       	}

        function drawspiderPoint(ctx,cnt,serie,j,c)

        {	var pos;

           	var d = calculatePosition(serie,data.ranges,j);

           	ctx.beginPath();

           	ctx.lineWidth = 1;

           	ctx.fillStyle = c;

          	ctx.strokeStyle = c;

        	pos = calculateXY(cnt,j,d);

        	ctx.arc(pos.x,pos.y,serie.spider.pointSize,0,Math.PI * 2,true);

        	ctx.closePath();

        	ctx.fill();

        }

        function drawspiderConnections(ctx,cnt,serie,c,fill)

       	{	var pos,d, fill_color;
		
		
			c = c.replace("rgb(","");
			c = c.replace(")","");
			fill_color = "rgba("+c+",0.5)";

           	ctx.beginPath();

           	ctx.lineWidth = serie.spider.connection.width;

           	ctx.strokeStyle = c;

          	ctx.fillStyle = fill_color;

        	d = calculatePosition(serie,data.ranges,0);

        	pos = calculateXY(cnt,0,d);

        	ctx.moveTo(pos.x,pos.y);

        	for(var j = 1;j < serie.data.length; j++)

        	{	d = calculatePosition(serie,data.ranges,j);

           		pos = calculateXY(cnt,j,d);

               	ctx.lineTo(pos.x,pos.y);

       		}

           	d = calculatePosition(serie,data.ranges,0);

        	pos = calculateXY(cnt,0,d);

        	ctx.lineTo(pos.x,pos.y);

      		if(fill) ctx.fill(); else ctx.stroke();

        }

     	function drawspider(ctx, opt)

     	{	var cnt = data[0].data.length;

           	drawGrid(ctx, opt);

           	for(var i = 0;i < data.length; i++)
            {	
				drawspiderPoints(ctx,cnt,data[i],data[i].color);
			}

            for(var i = 0;i < data.length; i++)
            {	
				drawspiderConnections(ctx,cnt,data[i],data[i].color, true); 
			}

            function drawGridRadar(ctx,opt)
			{	
				ctx.lineWidth = 1;

                ctx.strokeStyle = opt.tickColor;

                for (var i = 1; i <= opt.ticks; i++) 

				{	ctx.beginPath();

					ctx.arc(centerLeft, centerTop, maxRadius / opt.ticks * i, 0, Math.PI * 2, true);

                	ctx.closePath();

                	ctx.stroke();

				}

                for (var j = 0; j < cnt; j++)
                {	
					drawspiderLine(ctx,j);
					drawspiderLeg(ctx,j);

              	}

			}

			function drawGridSpider(ctx,opt)
			{	
				ctx.linewidth = 1;

				ctx.strokeStyle = opt.tickColor;

				for(var i = 0; i<= opt.ticks; i++)

				{	var pos = calculateXY(cnt,0,100 / opt.ticks * i);

					ctx.beginPath();

				    ctx.moveTo(pos.x, pos.y);

					for(var j = 1; j < cnt; j++)

					{	pos = calculateXY(cnt,j,100 / opt.ticks * i);

					    ctx.lineTo(pos.x, pos.y);

					}

					ctx.closePath();

					ctx.stroke();

				}

				
				for (var j = 0; j < cnt; j++) 
				{	
					drawspiderLine(ctx,j);
					drawspiderLeg(ctx,j);
				}

			}

			function drawGrid(ctx, opt)

            {	switch(opt.mode)

				{	case "radar":

						drawGridRadar(ctx,opt);

						break;

					case "spider":

						drawGridSpider(ctx,opt);

						break;

					default:

						drawGridRadar(ctx,opt);

						break;

				}

			}

			function drawScale(ctx,opt)

			{	if(opt.series.spider.scaleMode != "leg")

				{	for(var i = 0; i <= opt.ticks; i++)

					{	

						

					}

				}

			}

        	function drawspiderLine(ctx, j)

        	{	var pos;

      			ctx.beginPath();

      			ctx.lineWidth = options.series.spider.lineWidth;

       			ctx.strokeStyle = options.series.spider.lineStyle;

     			ctx.moveTo(centerLeft, centerTop);

      			pos = calculateXY(cnt,j,100);

      			ctx.lineTo(pos.x, pos.y);

        		ctx.stroke();

   			}

       		function drawspiderLeg(ctx,j,gridColor)

     		{	var pos;

           		pos   = calculateXY(cnt,j,100);
				
				label = data[0].spider.legs.data[j].label;

        		ctx.font = data[0].spider.legs.font;

        		ctx.fillStyle = data[0].spider.legs.fillStyle;
				
				if(pos.y > centerTop){
					pos.y +=10;
				} else {
					pos.y -=5;
				}
				
           		ctx.fillText(label, pos.x, pos.y);

           	}



  		}

        function calculatePosition(serie,ranges,j)

		{	var p;

			p = Math.max(Math.min(serie.data[j][1],ranges[j].max),ranges[j].min);

			return (p - ranges[j].min) / ranges[j].range * 100; 

		}

    	function calculateXY(cnt,j,d)

    	{	var x,y,s;

			s = 2 * Math.PI * opt.series.spider.legs.legStartAngle / 360;

           	x = centerLeft + Math.round(Math.cos(2 * Math.PI / cnt * j + s) * maxRadius * d / 100);

           	y = centerTop + Math.round(Math.sin(2 * Math.PI / cnt * j + s) * maxRadius * d / 100);

            return {x: x, y: y};

        }

       	function bindEvents(plot, eventHolder)

       	{	var options = plot.getOptions();

           	var hl = new HighLighting(plot, eventHolder, findNearby, options.series.spider.active,highlights)

        }

       	function findNearby(plot,mousex, mousey)

       	{	var serie, r = null, cnt;

            data = plot.getData();

            cnt = data[0].data.length;


            axes = plot.getAxes();

          	for(var i = 0;i < data.length;i++)

          	{	serie = data[i];

               	if(serie.spider.show)

               	{	for(var j = 0; j < serie.data.length; j++)

                    {	var pos = calculateXY(cnt,j,calculatePosition(serie,data.ranges,j));

                      	var dx = Math.abs(pos.x - mousex)

                           	,dy = Math.abs(pos.y - mousey)

                           	,dist = Math.sqrt(dx * dx + dy * dy);

               			if (dist <= serie.spider.pointSize) { r = {i: i,j: j}; }

                    }

               	}

           	} 

           	return r;

       	}

       	function drawOverlay(plot, octx)

       	{	var cnt;

            cnt = data[0].data.length;

            octx.save();

            octx.clearRect(0, 0, target.width(), target.height());

            for(i = 0; i < highlights.length; ++i)

           	{	drawHighlight(highlights[i]);}

           	octx.restore();

           	function drawHighlight(s)

           	{   var c = "rgba(255, 255, 255, " + s.series.spider.highlight.opacity + ")";

               	switch(s.series.spider.highlight.mode)

           		{	case "point":

               			drawspiderPoints(octx,cnt,s.series,c);

                   		break;

               		case "line":

                       	drawspiderConnections(octx,cnt,s.series,c,false);

               			break;

           			case "area":

       					drawspiderConnections(octx,cnt,s.series,s.series.color,true);

                   		break;

               		default:

                  		break;

       			}

       		}

       	}

    }

    $.plot.plugins.push({

        init: init,

        options: options,

        name: 'spider',

        version: '0.2'

    });

})(jQuery);