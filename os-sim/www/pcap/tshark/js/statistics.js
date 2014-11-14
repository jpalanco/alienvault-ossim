statistics1 = function (div) {
    $("buttomlink").context.activeElement.childNodes[0].children[0].className = 'buttonlinksel';
    parent.GB_changetitle("Graphs: All Traffic");
    document.getElementById(div).innerHTML="";
    var r = Raphael(div, 790, 300),
        e = [],
        clr = [],
        color = ["#8cc221"],
        values = [],
        now = 0,
        grid = r.drawGrid(10, 40, 724, 210, 10, 8, "#EEE"),
        c = r.path("M0,0").attr({fill: "none", "stroke-width": 3}),
        bg = r.path("M0,0").attr({stroke: "none", opacity: 0.3}),
        txt = {font: '12px Helvetica, Arial', fill: "#666"},
        txt1 = {font: '12px Helvetica, Arial', fill: "#666"},
        width = 750,
        blanket = r.set(),
        label = r.set(),
        is_label_visible = false,
        leave_timer,
        dotsy = [];
        
        label.push(r.text(60, 12, "XXXXXX bytes").attr(txt1).attr({fill: color[0]}));
        label.hide();
        
        var frame = r.popup(100, 100, label, "right").attr({fill: "#EEEEEE", stroke: "#CCC", "stroke-width": 2, "fill-opacity": .85}).hide();
        var x = 0 ;
        for (var i = 0; i < 30; i++) {
            x+=25;
            t = r.text(x, 272, labelx[i]).attr(txt).rotate(60).toBack();
        }
        
    function randomPath(length, j) {
        var path = "",
            x = 10,
            y = 0;
        dotsy[j] = dotsy[j] || [];
        for (var i = 0; i < length; i++) {
            dotsy[j][i] = Math.round(nvalues[j][i]);// * 20);
            if (i) {
                path += "C" + [x + 10, y, (x += 25) - 10, (y = 248 - dotsy[j][i]), x, y];
            } else {
                path += "M" + [10, (y = 248 - dotsy[j][i])];
            }
            
            
            var dot = r.circle(x, y, 0).attr({fill: "#F2F2F2", stroke: color[j], "stroke-width": 2});
            blanket.push(r.rect(25 * i, 245 - dotsy[j][i], 25, 300).attr({stroke: "none", fill: "#fff", opacity: 0}));
            var rect = blanket[blanket.length - 1];
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
                    label[0].attr({text: data + " bytes"}).show().stop().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);// + (data == 1 ? "" : "s")
                    dot.attr("r", 6);
                    is_label_visible = true;
                }, function () {
                    dot.attr("r", 0);
                    leave_timer = setTimeout(function () {
                        frame.hide();
                        label[0].hide();
                        is_label_visible = false;
                    }, 1);
                });
            })(x, y, realdata[0][i], label[i], dot);
        }
        return path;
    }
    var i = 0;
    values[i] = randomPath(30, i);
    clr[i] = color[i];
    
    c.attr({path: values[0], stroke: clr[0]});
    bg.attr({path: values[0] + "L735,250 10,250z", fill: clr[0]});
    
    var animation = function () {
        var time = 500;
        c.animate({path: values[0], stroke: clr[0]}, time, "<>");
        bg.animate({path: values[0] + "L735,250 10,250z", fill: clr[0]}, time, "<>");
    };
};

statistics1f = function (div) {
    $("buttomlink").context.activeElement.childNodes[0].children[1].className = 'buttonlinksel';
    parent.GB_changetitle("Graphs: All Traffic Vs Filter Traffic");
    document.getElementById(div).innerHTML="";
    var r = Raphael("holder", 790, 300),
        e = [],
        clr = [],
        color = ["#8cc221","#123456"],
        values = [],
        now = 0,
        grid = r.drawGrid(10, 40, 724, 210, 10, 8, "#EEE"),
        c = r.path("M0,0").attr({fill: "none", "stroke-width": 3}),
        c2 = r.path("M0,0").attr({fill: "none", "stroke-width": 3}),
        bg = r.path("M0,0").attr({stroke: "none", opacity: 0.3}),
        bg2 = r.path("M0,0").attr({stroke: "none", opacity: 0.3}),
        txt = {font: '12px Helvetica, Arial', fill: "#666"},
        txt1 = {font: '12px Helvetica, Arial', fill: "#666"},
        width = 790,
        blanket = r.set(),
        label = r.set(),
        is_label_visible = false,
        leave_timer,
        labelx=labelxfilter,
        realdata=realdatafilter,
        nvalues=nvaluesfilter,
        dotsy = [];
        
        label.push(r.text(60, 12, "XXXXXX bytes").attr(txt1).attr({fill: color[0]}));
        label.push(r.text(60, 27, "XXXXXX Filter bytes").attr(txt).attr({fill: color[1]}));
        label.hide();
        var frame = r.popup(100, 100, label, "right").attr({fill: "#EEEEEE", stroke: "#CCC", "stroke-width": 2, "fill-opacity": .85}).hide();
        
        var x = 0 ;
        for (var i = 0; i < 30; i++) {
            x+=25;
            t = r.text(x, 272, labelx[i]).attr(txt).rotate(60).toBack();
        }
        
    function randomPath(length, j) {
        var path = "",
            x = 10,
            y = 0;
        dotsy[j] = dotsy[j] || [];
        for (var i = 0; i < length; i++) {
            dotsy[j][i] = Math.round(nvalues[j][i]);// * 20);
            if (i) {
                path += "C" + [x + 10, y, (x += 25) - 10, (y = 248 - dotsy[j][i]), x, y];
            } else {
                path += "M" + [10, (y = 248 - dotsy[j][i])];
            }
            
            
            var dot = r.circle(x, y, 0).attr({fill: "#F2F2F2", stroke: color[j], "stroke-width": 2});
            blanket.push(r.rect(25 * i, 245 - dotsy[j][i], 25, 300).attr({stroke: "none", fill: "#fff", opacity: 0}));
            var rect = blanket[blanket.length - 1];
            (function (x, y, data, lbl, dot, data2) {
                var timer, i = 0;
                rect.hover(function () {
                    clearTimeout(leave_timer);
                    var side = "right";
                    if (x + frame.getBBox().width > width) {
                        side = "left";
                    }
                    var ppp = r.popup(x, y, label, side, 1);
                    frame.show().stop().animate({path: ppp.path}, 200 * is_label_visible);
                    label[0].attr({text: data + " bytes"}).show().stop().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);// + (data == 1 ? "" : "s")
                    label[1].attr({text: data2 + " Filter bytes"}).show().stop().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);// + (data == 1 ? "" : "s")
                    dot.attr("r", 6);
                    is_label_visible = true;
                }, function () {
                    dot.attr("r", 0);
                    leave_timer = setTimeout(function () {
                        frame.hide();
                        label[0].hide();
                        label[1].hide();
                        is_label_visible = false;
                    }, 1);
                });
            })(x, y, realdata[0][i], label[i], dot, realdata[1][i]);
        }
        return path;
    }



    for (var i = 0; i < nvalues.length; i++) {
        values[i] = randomPath(30, i);
        clr[i] = color[i]; //Raphael.getColor(1);
    }

    c.attr({path: values[0], stroke: clr[0]});
    c2.attr({path: values[1], stroke: clr[1]});
    bg.attr({path: values[0] + "L735,250 10,250z", fill: clr[0]});
    bg2.attr({path: values[1] + "L735,250 10,250z", fill: clr[1]});

    var animation = function () {
        var time = 500;
        c.animate({path: values[0], stroke: clr[0]}, time, "<>");
        c2.animate({path: values[1], stroke: clr[1]}, time, "<>");
        bg.animate({path: values[0] + "L735,250 10,250z", fill: clr[0]}, time, "<>");
        bg2.animate({path: values[1] + "L735,250 10,250z", fill: clr[1]}, time, "<>");

    };
};

statisticsproto = function (div) {
    $("buttomlink").context.activeElement.childNodes[0].children[1].className = 'buttonlinksel';
    document.getElementById(div).innerHTML="";
    parent.GB_changetitle("Graphs: Protocol Traffic");
    var r = Raphael(div, 790, 300),
        e = [],
        clr = [],
        color = ["#8cc221","#123456","#895186"],
        values = [],
        now = 0,
        grid = r.drawGrid(10, 40, 724, 210, 10, 8, "#EEE"),
        c = r.path("M0,0").attr({fill: "none", "stroke-width": 3}),
        c2 = r.path("M0,0").attr({fill: "none", "stroke-width": 3}),
        c3 = r.path("M0,0").attr({fill: "none", "stroke-width": 3}),
        bg = r.path("M0,0").attr({stroke: "none", opacity: 0.3}),
        bg2 = r.path("M0,0").attr({stroke: "none", opacity: 0.3}),
        bg3 = r.path("M0,0").attr({stroke: "none", opacity: 0.3}),
        txt = {font: '12px Helvetica, Arial', fill: "#666"},
        txt1 = {font: '12px Helvetica, Arial', fill: "#666"},
        width = 790,
        blanket = r.set(),
        label = r.set(),
        is_label_visible = false,
        leave_timer,
        labelx=labelxproto,
        realdata=realdataproto,
        nvalues=nvaluesproto,
        dotsy = [];
        
        label.push(r.text(60, 12, "XXXXXX TCP bytes").attr(txt1).attr({fill: color[0]}));
        label.push(r.text(60, 27, "XXXXXX UDP bytes").attr(txt).attr({fill: color[1]}));
        label.push(r.text(60, 42, "XXXXXX Other bytes").attr(txt).attr({fill: color[2]}));
        label.hide();
        var frame = r.popup(100, 100, label, "right").attr({fill: "#EEEEEE", stroke: "#CCC", "stroke-width": 2, "fill-opacity": .85}).hide();
        
        var x = 0 ;
        for (var i = 0; i < 30; i++) {
            x+=25;
            t = r.text(x, 272, labelx[i]).attr(txt).rotate(60).toBack();
        }
        
    function randomPath(length, j) {
        var path = "",
            x = 10,
            y = 0;
        dotsy[j] = dotsy[j] || [];
        for (var i = 0; i < length; i++) {
            dotsy[j][i] = Math.round(nvalues[j][i]);// * 20);
            if (i) {
                path += "C" + [x + 10, y, (x += 25) - 10, (y = 248 - dotsy[j][i]), x, y];
            } else {
                path += "M" + [10, (y = 248 - dotsy[j][i])];
            }
            
            var dot = r.circle(x, y, 0).attr({fill: "#F2F2F2", stroke: color[j], "stroke-width": 2});
            blanket.push(r.rect(25 * i, 245 - dotsy[j][i], 25, 300).attr({stroke: "none", fill: "#fff", opacity: 0}));
            var rect = blanket[blanket.length - 1];
            (function (x, y, data, lbl, dot, data2, data3) {
                var timer, i = 0;
                rect.hover(function () {
                    clearTimeout(leave_timer);
                    var side = "right";
                    if (x + frame.getBBox().width > width) {
                        side = "left";
                    }
                    var ppp = r.popup(x, y, label, side, 1);
                    frame.show().stop().animate({path: ppp.path}, 200 * is_label_visible);
                    label[0].attr({text: data + " TCP bytes"}).show().stop().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);// + (data == 1 ? "" : "s")
                    label[1].attr({text: data2 + " UDP bytes"}).show().stop().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);// + (data == 1 ? "" : "s")
                    label[2].attr({text: data3 + " Other bytes"}).show().stop().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);// + (data == 1 ? "" : "s")
                    dot.attr("r", 6);
                    is_label_visible = true;
                }, function () {
                    dot.attr("r", 0);
                    leave_timer = setTimeout(function () {
                        frame.hide();
                        label[0].hide();
                        label[1].hide();
                        label[2].hide();
                        is_label_visible = false;
                    }, 1);
                });
            })(x, y, realdata[0][i], label[i], dot, realdata[1][i], realdata[2][i]);
        }
        return path;
    }



    for (var i = 0; i < nvalues.length; i++) {
        values[i] = randomPath(30, i);
        clr[i] = color[i]; //Raphael.getColor(1);
    }

    c.attr({path: values[0], stroke: clr[0]});
    c2.attr({path: values[1], stroke: clr[1]});
    c3.attr({path: values[2], stroke: clr[2]});
    bg.attr({path: values[0] + "L735,250 10,250z", fill: clr[0]});
    bg2.attr({path: values[1] + "L735,250 10,250z", fill: clr[1]});
    bg3.attr({path: values[2] + "L735,250 10,250z", fill: clr[2]});

    
};

statisticsprotofilter = function (div) {
    $("buttomlink").context.activeElement.childNodes[0].children[3].className = 'buttonlinksel';
    parent.GB_changetitle("Graphs: Protocol Filter Traffic");
    document.getElementById(div).innerHTML="";
    var r = Raphael(div, 790, 300),
        e = [],
        clr = [],
        color = ["#8cc221","#123456","#895186"],
        values = [],
        now = 0,
        grid = r.drawGrid(10, 40, 724, 210, 10, 8, "#EEE"),
        c = r.path("M0,0").attr({fill: "none", "stroke-width": 3}),
        c2 = r.path("M0,0").attr({fill: "none", "stroke-width": 3}),
        c3 = r.path("M0,0").attr({fill: "none", "stroke-width": 3}),
        bg = r.path("M0,0").attr({stroke: "none", opacity: 0.3}),
        bg2 = r.path("M0,0").attr({stroke: "none", opacity: 0.3}),
        bg3 = r.path("M0,0").attr({stroke: "none", opacity: 0.3}),
        txt = {font: '12px Helvetica, Arial', fill: "#666"},
        txt1 = {font: '12px Helvetica, Arial', fill: "#666"},
        width = 790,
        blanket = r.set(),
        label = r.set(),
        is_label_visible = false,
        leave_timer,
        labelx=labelxprotofilter,
        realdata=realdataprotofilter,
        nvalues=nvaluesprotofilter,
        dotsy = [];
        
        label.push(r.text(60, 12, "XXXXXX TCP bytes").attr(txt1).attr({fill: color[0]}));
        label.push(r.text(60, 27, "XXXXXX UDP bytes").attr(txt).attr({fill: color[1]}));
        label.push(r.text(60, 42, "XXXXXX Other bytes").attr(txt).attr({fill: color[2]}));
        label.hide();
        var frame = r.popup(100, 100, label, "right").attr({fill: "#EEEEEE", stroke: "#CCC", "stroke-width": 2, "fill-opacity": .85}).hide();
        
        var x = 0 ;
        for (var i = 0; i < 30; i++) {
            x+=25;
            t = r.text(x, 272, labelx[i]).attr(txt).rotate(60).toBack();
        }
        
    function randomPath(length, j) {
        var path = "",
            x = 10,
            y = 0;
        dotsy[j] = dotsy[j] || [];
        for (var i = 0; i < length; i++) {
            dotsy[j][i] = Math.round(nvalues[j][i]);// * 20);
            if (i) {
                path += "C" + [x + 10, y, (x += 25) - 10, (y = 248 - dotsy[j][i]), x, y];
            } else {
                path += "M" + [10, (y = 248 - dotsy[j][i])];
            }
            
            var dot = r.circle(x, y, 0).attr({fill: "#F2F2F2", stroke: color[j], "stroke-width": 2});
            blanket.push(r.rect(25 * i, 245 - dotsy[j][i], 25, 300).attr({stroke: "none", fill: "#fff", opacity: 0}));
            var rect = blanket[blanket.length - 1];
            (function (x, y, data, lbl, dot, data2, data3) {
                var timer, i = 0;
                rect.hover(function () {
                    clearTimeout(leave_timer);
                    var side = "right";
                    if (x + frame.getBBox().width > width) {
                        side = "left";
                    }
                    var ppp = r.popup(x, y, label, side, 1);
                    frame.show().stop().animate({path: ppp.path}, 200 * is_label_visible);
                    label[0].attr({text: data + " TCP bytes"}).show().stop().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);// + (data == 1 ? "" : "s")
                    label[1].attr({text: data2 + " UDP bytes"}).show().stop().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);// + (data == 1 ? "" : "s")
                    label[2].attr({text: data3 + " Other bytes"}).show().stop().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);// + (data == 1 ? "" : "s")
                    dot.attr("r", 6);
                    is_label_visible = true;
                }, function () {
                    dot.attr("r", 0);
                    leave_timer = setTimeout(function () {
                        frame.hide();
                        label[0].hide();
                        label[1].hide();
                        label[2].hide();
                        is_label_visible = false;
                    }, 1);
                });
            })(x, y, realdata[0][i], label[i], dot, realdata[1][i], realdata[2][i]);
        }
        return path;
    }



    for (var i = 0; i < nvalues.length; i++) {
        values[i] = randomPath(30, i);
        clr[i] = color[i]; //Raphael.getColor(1);
    }

    c.attr({path: values[0], stroke: clr[0]});
    c2.attr({path: values[1], stroke: clr[1]});
    c3.attr({path: values[2], stroke: clr[2]});
    bg.attr({path: values[0] + "L735,250 10,250z", fill: clr[0]});
    bg2.attr({path: values[1] + "L735,250 10,250z", fill: clr[1]});
    bg3.attr({path: values[2] + "L735,250 10,250z", fill: clr[2]});

    
};
