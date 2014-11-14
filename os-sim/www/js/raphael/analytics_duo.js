Raphael.fn.drawGrid = function (x, y, w, h, wv, hv, color) {
    color = color || "#000";
    var path = ["M", Math.round(x) + .5, Math.round(y) + .5, "L", Math.round(x + w) + .5, Math.round(y) + .5, Math.round(x + w) + .5, Math.round(y + h) + .5, Math.round(x) + .5, Math.round(y + h) + .5, Math.round(x) + .5, Math.round(y) + .5],
        rowHeight = h / hv,
        columnWidth = w / wv;
    for (var i = 1; i < hv; i++) {
        path = path.concat(["M", Math.round(x) + .5, Math.round(y + i * rowHeight) + .5, "H", Math.round(x + w) + .5]);
    }
    for (i = 1; i < wv; i++) {
        path = path.concat(["M", Math.round(x + i * columnWidth) + .5, Math.round(y) + .5, "V", Math.round(y + h) + .5]);
    }
    return this.path(path.join(",")).attr({stroke: color});
};

window.onload = function () {
    function clickNear(e,dot,rad) {
        x = e.clientX || e.pageX; y = e.clientY || e.pageY;
        dx = dot.attr("cx"); dy = dot.attr("cy");
        //console.log(x+"-"+y+"-"+dx+"-"+dy);
        return (x>=dx-rad && x<=dx+rad && y>=dy-rad && y<=dy+rad) ? true : false;
    }
    function getAnchors(p1x, p1y, p2x, p2y, p3x, p3y) {
        var l1 = (p2x - p1x) / 2,
            l2 = (p3x - p2x) / 2,
            a = Math.atan((p2x - p1x) / Math.abs(p2y - p1y)),
            b = Math.atan((p3x - p2x) / Math.abs(p2y - p3y));
        a = p1y < p2y ? Math.PI - a : a;
        b = p3y < p2y ? Math.PI - b : b;
        var alpha = Math.PI / 2 - ((a + b) % (Math.PI * 2)) / 2,
            dx1 = l1 * Math.sin(alpha + a),
            dy1 = l1 * Math.cos(alpha + a),
            dx2 = l2 * Math.sin(alpha + b),
            dy2 = l2 * Math.cos(alpha + b);
        return {
            x1: p2x - dx1,
            y1: p2y + dy1,
            x2: p2x + dx2,
            y2: p2y + dy2
        };
    }

	//Split large numbers with points. EX: 10000 --> 10.000
	function format_dot_number(num){	
		num = num + "";
        var i=num.length-3;
        while (i>0){
            num=num.substring(0,i)+"."+num.substring(i);
            i-=3;
        }
        return(num);
    }

    // Grab the data
    var labels = [],
        data = [],
        data2 = [],
        y1 = [],
        dot1 = [];
    $("#data tfoot th").each(function () {
        labels.push($(this).html());
    });
    $("#data tbody td").each(function () {
        data.push($(this).html());
    });
    $("#data2 tbody td").each(function () {
        data2.push($(this).html());
    });
    
    // Draw
    var color = '#8DC41B',
    	width = 450,
        height = 180,
        leftgutter = 0,
        bottomgutter = 25,
        topgutter = 18,
        colorhue = .6 || Math.random(),
        //color = "hsb(" + [colorhue, .5, 1] + ")",
        r = Raphael("holder", width, height),
        txt = {font: '11px Helvetica, Arial', fill: "#000"},
        txt1 = {font: '10px Helvetica, Arial', fill: "#000"},
        txt2 = {font: '12px Helvetica, Arial', fill: "#000"},
        X = (width - leftgutter) / labels.length,
        max1 = Math.max.apply(Math, data),
        max2 = Math.max.apply(Math, data2);
    var max = (max1>max2) ? max1 : max2;
    if (max==0 && max_aux!=0) max=max_aux; // fixed all zero values
    var Y = (height - bottomgutter - topgutter) / max; 
    r.drawGrid(leftgutter + X * .5 + .5, topgutter + .5, width - leftgutter - X, height - topgutter - bottomgutter, 16, 8, "#EEE");
    var path = r.path().attr({stroke: color, "stroke-width": 4, "stroke-linejoin": "round"}),
        bgp = r.path().attr({stroke: "none", opacity: .3, fill: color}),
        label = r.set(),
        is_label_visible = false,
        leave_timer,
        blanket = r.set();

    var p, bgpp;
    for (var i = 0, ii = labels.length; i < ii; i++) {
        var y = Math.round(height - bottomgutter - Y * data[i]),
            x = Math.round(leftgutter + X * (i + .5)),
            t = r.text(x, height - 6, labels[i]).attr(txt2).toBack();
        if (!i) {
            p = ["M", x, y, "C", x, y];
            bgpp = ["M", leftgutter + X * .5, height - bottomgutter, "L", x, y, "C", x, y];
        }
        if (i && i < ii - 1) {
            var Y0 = Math.round(height - bottomgutter - Y * data[i - 1]),
                X0 = Math.round(leftgutter + X * (i - .5)),
                Y2 = Math.round(height - bottomgutter - Y * data[i + 1]),
                X2 = Math.round(leftgutter + X * (i + 1.5));
            var a = getAnchors(X0, Y0, x, y, X2, Y2);
            p = p.concat([a.x1, a.y1, x, y, a.x2, a.y2]);
            bgpp = bgpp.concat([a.x1, a.y1, x, y, a.x2, a.y2]);
        }
        var dot = r.circle(x, y, 4).attr({fill: "#F2F2F2", stroke: color, "stroke-width": 2});
        blanket.push(r.rect(leftgutter + X * i, 0, X, height - bottomgutter).attr({stroke: "none", fill: "#fff", opacity: 0}));
        var rect = blanket[blanket.length - 1];
        /*
        (function (x, y, data, lbl, dot) {
            var timer, i = 0;
            rect.hover(function () {
                clearTimeout(leave_timer);
                var side = "right";
                if (x + frame.getBBox().width > width) {
                    side = "left";
                }
                var ppp = r.popup(x, y, label, side, 1);
                frame.show().stop().animate({path: ppp.path}, 200 * is_label_visible);
                label[0].attr({text: data + " event" + (data == 1 ? "" : "s")}).show().stop().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);
                label[1].attr({text: lbl }).show().stop().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);
                dot.attr("r", 6);
                is_label_visible = true;
            }, function () {
                dot.attr("r", 4);
                leave_timer = setTimeout(function () {
                    frame.hide();
                    label[0].hide();
                    label[1].hide();
                    is_label_visible = false;
                }, 1);
            });
        })(x, y, data[i], labels[i], dot);
        */
        y1.push(y); dot1.push(dot);
    }
    p = p.concat([x, y, x, y]);
    bgpp = bgpp.concat([x, y, x, y, "L", x, height - bottomgutter, "z"]);
    path.attr({path: p});
    bgp.attr({path: bgpp});
    blanket.toFront();
    
    color = '#BBC6D0';
    var path = r.path().attr({stroke: color, "stroke-width": 4, "stroke-linejoin": "round"}),
        bgp = r.path().attr({stroke: "none", opacity: .3, fill: color}),
        label2 = r.set(),
        label3 = r.set(),
        is_label_visible2 = false,
        leave_timer2,
        blanket2 = r.set();

    label.push(r.text(60, 12, "XXXXXX Security events").attr(txt));
    label.push(r.text(60, 27, "31 January 2011").attr(txt1).attr({fill: color}));
    label.hide();
    var frame = r.popup(100, 100, label, "right").attr({fill: "#EEEEEE", stroke: "#CCC", "stroke-width": 2, "fill-opacity": .85}).hide();

    label2.push(r.text(60, 12, "XXXXXX Log events").attr(txt));
    label2.push(r.text(60, 27, "31 January 2011").attr(txt1).attr({fill: color}));
    label2.hide();
    var frame2 = r.popup(100, 100, label2, "right").attr({fill: "#EEEEEE", stroke: "#CCC", "stroke-width": 2, "fill-opacity": .85}).hide();
        
    label3.push(r.text(60, 12, "XXXXXX Security events").attr(txt));
    label3.push(r.text(60, 25, "XXXXXX Log events").attr(txt));
    label3.push(r.text(60, 39, "31 January 2011").attr(txt1).attr({fill: '#338E05'}));
    label3.hide();
    var frame3 = r.popup(100, 100, label3, "right").attr({fill: "#EEEEEE", stroke: "#CCC", "stroke-width": 2, "fill-opacity": .85}).hide();
    
    var p, bgpp;
    for (var i = 0, ii = labels.length; i < ii; i++) {
        var y = Math.round(height - bottomgutter - Y * data2[i]),
            x = Math.round(leftgutter + X * (i + .5)),
            t = r.text(x, height - 6, labels[i]).attr(txt2).toBack();
        if (!i) {
            p = ["M", x, y, "C", x, y];
            bgpp = ["M", leftgutter + X * .5, height - bottomgutter, "L", x, y, "C", x, y];
        }
        if (i && i < ii - 1) {
            var Y0 = Math.round(height - bottomgutter - Y * data2[i - 1]),
                X0 = Math.round(leftgutter + X * (i - .5)),
                Y2 = Math.round(height - bottomgutter - Y * data2[i + 1]),
                X2 = Math.round(leftgutter + X * (i + 1.5));
            var a = getAnchors(X0, Y0, x, y, X2, Y2);
            p = p.concat([a.x1, a.y1, x, y, a.x2, a.y2]);
            bgpp = bgpp.concat([a.x1, a.y1, x, y, a.x2, a.y2]);
        }
        var dot = r.circle(x, y, 4).attr({fill: "#F2F2F2", stroke: color, "stroke-width": 2});
        blanket2.push(r.rect(leftgutter + X * i, 0, X, height - bottomgutter).attr({stroke: "none", fill: "#fff", opacity: 0}));
        var rect = blanket2[blanket2.length - 1];
        (function (x, y, y1, data, data1, lbl, dot, dot1) {
            var timer, i = 0;
            rect.hover(function () {
                clearTimeout(leave_timer2);
                var side = "right";
                if (x + frame2.getBBox().width > width) {
                    side = "left";
                }
                var dify = Math.abs(y1-y);
                if (dify<=25) { // so closer
	                var ppp = r.popup(x, y, label3, side, 1);
	                frame3.show().stop().animate({path: ppp.path}, 200 * is_label_visible2);
	                label3[0].attr({text: format_dot_number(data) + " Security event" + (data == 1 ? "" : "s")}).show().stop().animateWith(frame2, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible2);
	                label3[1].attr({text: format_dot_number(data1) + " Log event" + (data1 == 1 ? "" : "s")}).show().stop().animateWith(frame2, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible2);
	                label3[2].attr({text: lbl }).show().stop().animateWith(frame2, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible2);
                    frame2.hide();
                    frame.hide();
                    label2[0].hide();
                    label2[1].hide();
                    label[0].hide();
                    label[1].hide();	                
                } else {
	                var ppp = r.popup(x, y, label2, side, 1);
	                frame2.show().stop().animate({path: ppp.path}, 200 * is_label_visible2);
	                label2[0].attr({text: format_dot_number(data1) + " Log event" + (data1 == 1 ? "" : "s")}).show().stop().animateWith(frame2, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible2);
	                label2[1].attr({text: lbl }).show().stop().animateWith(frame2, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible2);
	
	                var ppp1 = r.popup(x, y1, label, side, 1);
	                frame.show().stop().animate({path: ppp1.path}, 200 * is_label_visible2);
	                label[0].attr({text: format_dot_number(data) + " Security event" + (data == 1 ? "" : "s")}).show().stop().animateWith(frame, {translation: [ppp1.dx, ppp1.dy]}, 200 * is_label_visible2);
	                label[1].attr({text: lbl }).show().stop().animateWith(frame, {translation: [ppp1.dx, ppp1.dy]}, 200 * is_label_visible2);
                    frame3.hide();
                    label3[0].hide();
                    label3[1].hide();
                    label3[2].hide();
	            }

                dot.attr("r", 6); dot1.attr("r", 6);
                //console.log(dot); console.log(dot1);
                is_label_visible2 = true;
            }, function () {
                dot.attr("r", 4); dot1.attr("r", 4);
                leave_timer2 = setTimeout(function () {
                    frame2.hide();
                    frame3.hide();
                    frame.hide();
                    label3[0].hide();
                    label3[1].hide();
                    label3[2].hide();
                    label2[0].hide();
                    label2[1].hide();
                    label[0].hide();
                    label[1].hide();
                    is_label_visible2 = false;
                }, 1);
            });
            rect.click(function (event) {
            	var h = lbl.replace(/h$/,'');
            	if (h<10) h='0'+h;
                if (clickNear(event,dot,12)) {
                	refurl = (h>h_now) ? logger_url_y : logger_url;
                    url = refurl.replace(/HH/,h).replace(/HH/,h);
                    //console.log(url);
                    if (url!='') top.frames['main'].location.href = url;
                }
                if (clickNear(event,dot1,12)) {
                	refurl = (h>h_now) ? siem_url_y : siem_url;
                    url = refurl.replace(/HH/,h).replace(/HH/,h);
                    //console.log(url);
                    if (url!='') top.frames['main'].location.href = url;
                }
            });            
        })(x, y, y1[i], data[i], data2[i], labels[i], dot, dot1[i]);
    }
    
    p = p.concat([x, y, x, y]);
    bgpp = bgpp.concat([x, y, x, y, "L", x, height - bottomgutter, "z"]);
    path.attr({path: p});
    bgp.attr({path: bgpp});
    frame.toFront();
    label[0].toFront();
    label[1].toFront();
    frame2.toFront();
    label2[0].toFront();
    label2[1].toFront();
    frame3.toFront();
    label3[0].toFront();
    label3[1].toFront();
    label3[2].toFront();
    blanket2.toFront();
};