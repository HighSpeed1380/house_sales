
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LIB.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2022 | All copyrights reserved.
 *  
 *  https://www.flynax.com/
 ******************************************************************************/

/* mouse wheel */
!function(a){"function"==typeof define&&define.amd?define(["jquery"],a):"object"==typeof exports?module.exports=a:a(jQuery)}(function(a){function b(b){var g=b||window.event,h=i.call(arguments,1),j=0,l=0,m=0,n=0,o=0,p=0;if(b=a.event.fix(g),b.type="mousewheel","detail"in g&&(m=-1*g.detail),"wheelDelta"in g&&(m=g.wheelDelta),"wheelDeltaY"in g&&(m=g.wheelDeltaY),"wheelDeltaX"in g&&(l=-1*g.wheelDeltaX),"axis"in g&&g.axis===g.HORIZONTAL_AXIS&&(l=-1*m,m=0),j=0===m?l:m,"deltaY"in g&&(m=-1*g.deltaY,j=m),"deltaX"in g&&(l=g.deltaX,0===m&&(j=-1*l)),0!==m||0!==l){if(1===g.deltaMode){var q=a.data(this,"mousewheel-line-height");j*=q,m*=q,l*=q}else if(2===g.deltaMode){var r=a.data(this,"mousewheel-page-height");j*=r,m*=r,l*=r}if(n=Math.max(Math.abs(m),Math.abs(l)),(!f||f>n)&&(f=n,d(g,n)&&(f/=40)),d(g,n)&&(j/=40,l/=40,m/=40),j=Math[j>=1?"floor":"ceil"](j/f),l=Math[l>=1?"floor":"ceil"](l/f),m=Math[m>=1?"floor":"ceil"](m/f),k.settings.normalizeOffset&&this.getBoundingClientRect){var s=this.getBoundingClientRect();o=b.clientX-s.left,p=b.clientY-s.top}return b.deltaX=l,b.deltaY=m,b.deltaFactor=f,b.offsetX=o,b.offsetY=p,b.deltaMode=0,h.unshift(b,j,l,m),e&&clearTimeout(e),e=setTimeout(c,200),(a.event.dispatch||a.event.handle).apply(this,h)}}function c(){f=null}function d(a,b){return k.settings.adjustOldDeltas&&"mousewheel"===a.type&&b%120===0}var e,f,g=["wheel","mousewheel","DOMMouseScroll","MozMousePixelScroll"],h="onwheel"in document||document.documentMode>=9?["wheel"]:["mousewheel","DomMouseScroll","MozMousePixelScroll"],i=Array.prototype.slice;if(a.event.fixHooks)for(var j=g.length;j;)a.event.fixHooks[g[--j]]=a.event.mouseHooks;var k=a.event.special.mousewheel={version:"3.1.12",setup:function(){if(this.addEventListener)for(var c=h.length;c;)this.addEventListener(h[--c],b,!1);else this.onmousewheel=b;a.data(this,"mousewheel-line-height",k.getLineHeight(this)),a.data(this,"mousewheel-page-height",k.getPageHeight(this))},teardown:function(){if(this.removeEventListener)for(var c=h.length;c;)this.removeEventListener(h[--c],b,!1);else this.onmousewheel=null;a.removeData(this,"mousewheel-line-height"),a.removeData(this,"mousewheel-page-height")},getLineHeight:function(b){var c=a(b),d=c["offsetParent"in a.fn?"offsetParent":"parent"]();return d.length||(d=a("body")),parseInt(d.css("fontSize"),10)||parseInt(c.css("fontSize"),10)||16},getPageHeight:function(b){return a(b).height()},settings:{adjustOldDeltas:!0,normalizeOffset:!0}};a.fn.extend({mousewheel:function(a){return a?this.bind("mousewheel",a):this.trigger("mousewheel")},unmousewheel:function(a){return this.unbind("mousewheel",a)}})});

/* == malihu jquery custom scrollbar plugin == Version: 3.1.5, License: MIT License (MIT) */
!function(e){"function"==typeof define&&define.amd?define(["jquery"],e):"undefined"!=typeof module&&module.exports?module.exports=e:e(jQuery,window,document)}(function(e){!function(t){var o="function"==typeof define&&define.amd,a="undefined"!=typeof module&&module.exports,n="https:"==document.location.protocol?"https:":"http:",i="cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.13/jquery.mousewheel.min.js";o||(a?require("jquery-mousewheel")(e):e.event.special.mousewheel||e("head").append(decodeURI("%3Cscript src="+n+"//"+i+"%3E%3C/script%3E"))),t()}(function(){var t,o="mCustomScrollbar",a="mCS",n=".mCustomScrollbar",i={setTop:0,setLeft:0,axis:"y",scrollbarPosition:"inside",scrollInertia:950,autoDraggerLength:!0,alwaysShowScrollbar:0,snapOffset:0,mouseWheel:{enable:!0,scrollAmount:"auto",axis:"y",deltaFactor:"auto",disableOver:["select","option","keygen","datalist","textarea"]},scrollButtons:{scrollType:"stepless",scrollAmount:"auto"},keyboard:{enable:!0,scrollType:"stepless",scrollAmount:"auto"},contentTouchScroll:25,documentTouchScroll:!0,advanced:{autoScrollOnFocus:"input,textarea,select,button,datalist,keygen,a[tabindex],area,object,[contenteditable='true']",updateOnContentResize:!0,updateOnImageLoad:"auto",autoUpdateTimeout:60},theme:"light",callbacks:{onTotalScrollOffset:0,onTotalScrollBackOffset:0,alwaysTriggerOffsets:!0}},r=0,l={},s=window.attachEvent&&!window.addEventListener?1:0,c=!1,d=["mCSB_dragger_onDrag","mCSB_scrollTools_onDrag","mCS_img_loaded","mCS_disabled","mCS_destroyed","mCS_no_scrollbar","mCS-autoHide","mCS-dir-rtl","mCS_no_scrollbar_y","mCS_no_scrollbar_x","mCS_y_hidden","mCS_x_hidden","mCSB_draggerContainer","mCSB_buttonUp","mCSB_buttonDown","mCSB_buttonLeft","mCSB_buttonRight"],u={init:function(t){var t=e.extend(!0,{},i,t),o=f.call(this);if(t.live){var s=t.liveSelector||this.selector||n,c=e(s);if("off"===t.live)return void m(s);l[s]=setTimeout(function(){c.mCustomScrollbar(t),"once"===t.live&&c.length&&m(s)},500)}else m(s);return t.setWidth=t.set_width?t.set_width:t.setWidth,t.setHeight=t.set_height?t.set_height:t.setHeight,t.axis=t.horizontalScroll?"x":p(t.axis),t.scrollInertia=t.scrollInertia>0&&t.scrollInertia<17?17:t.scrollInertia,"object"!=typeof t.mouseWheel&&1==t.mouseWheel&&(t.mouseWheel={enable:!0,scrollAmount:"auto",axis:"y",preventDefault:!1,deltaFactor:"auto",normalizeDelta:!1,invert:!1}),t.mouseWheel.scrollAmount=t.mouseWheelPixels?t.mouseWheelPixels:t.mouseWheel.scrollAmount,t.mouseWheel.normalizeDelta=t.advanced.normalizeMouseWheelDelta?t.advanced.normalizeMouseWheelDelta:t.mouseWheel.normalizeDelta,t.scrollButtons.scrollType=g(t.scrollButtons.scrollType),h(t),e(o).each(function(){var o=e(this);if(!o.data(a)){o.data(a,{idx:++r,opt:t,scrollRatio:{y:null,x:null},overflowed:null,contentReset:{y:null,x:null},bindEvents:!1,tweenRunning:!1,sequential:{},langDir:o.css("direction"),cbOffsets:null,trigger:null,poll:{size:{o:0,n:0},img:{o:0,n:0},change:{o:0,n:0}}});var n=o.data(a),i=n.opt,l=o.data("mcs-axis"),s=o.data("mcs-scrollbar-position"),c=o.data("mcs-theme");l&&(i.axis=l),s&&(i.scrollbarPosition=s),c&&(i.theme=c,h(i)),v.call(this),n&&i.callbacks.onCreate&&"function"==typeof i.callbacks.onCreate&&i.callbacks.onCreate.call(this),e("#mCSB_"+n.idx+"_container img:not(."+d[2]+")").addClass(d[2]),u.update.call(null,o)}})},update:function(t,o){var n=t||f.call(this);return e(n).each(function(){var t=e(this);if(t.data(a)){var n=t.data(a),i=n.opt,r=e("#mCSB_"+n.idx+"_container"),l=e("#mCSB_"+n.idx),s=[e("#mCSB_"+n.idx+"_dragger_vertical"),e("#mCSB_"+n.idx+"_dragger_horizontal")];if(!r.length)return;n.tweenRunning&&Q(t),o&&n&&i.callbacks.onBeforeUpdate&&"function"==typeof i.callbacks.onBeforeUpdate&&i.callbacks.onBeforeUpdate.call(this),t.hasClass(d[3])&&t.removeClass(d[3]),t.hasClass(d[4])&&t.removeClass(d[4]),l.css("max-height","none"),l.height()!==t.height()&&l.css("max-height",t.height()),_.call(this),"y"===i.axis||i.advanced.autoExpandHorizontalScroll||r.css("width",x(r)),n.overflowed=y.call(this),M.call(this),i.autoDraggerLength&&S.call(this),b.call(this),T.call(this);var c=[Math.abs(r[0].offsetTop),Math.abs(r[0].offsetLeft)];"x"!==i.axis&&(n.overflowed[0]?s[0].height()>s[0].parent().height()?B.call(this):(G(t,c[0].toString(),{dir:"y",dur:0,overwrite:"none"}),n.contentReset.y=null):(B.call(this),"y"===i.axis?k.call(this):"yx"===i.axis&&n.overflowed[1]&&G(t,c[1].toString(),{dir:"x",dur:0,overwrite:"none"}))),"y"!==i.axis&&(n.overflowed[1]?s[1].width()>s[1].parent().width()?B.call(this):(G(t,c[1].toString(),{dir:"x",dur:0,overwrite:"none"}),n.contentReset.x=null):(B.call(this),"x"===i.axis?k.call(this):"yx"===i.axis&&n.overflowed[0]&&G(t,c[0].toString(),{dir:"y",dur:0,overwrite:"none"}))),o&&n&&(2===o&&i.callbacks.onImageLoad&&"function"==typeof i.callbacks.onImageLoad?i.callbacks.onImageLoad.call(this):3===o&&i.callbacks.onSelectorChange&&"function"==typeof i.callbacks.onSelectorChange?i.callbacks.onSelectorChange.call(this):i.callbacks.onUpdate&&"function"==typeof i.callbacks.onUpdate&&i.callbacks.onUpdate.call(this)),N.call(this)}})},scrollTo:function(t,o){if("undefined"!=typeof t&&null!=t){var n=f.call(this);return e(n).each(function(){var n=e(this);if(n.data(a)){var i=n.data(a),r=i.opt,l={trigger:"external",scrollInertia:r.scrollInertia,scrollEasing:"mcsEaseInOut",moveDragger:!1,timeout:60,callbacks:!0,onStart:!0,onUpdate:!0,onComplete:!0},s=e.extend(!0,{},l,o),c=Y.call(this,t),d=s.scrollInertia>0&&s.scrollInertia<17?17:s.scrollInertia;c[0]=X.call(this,c[0],"y"),c[1]=X.call(this,c[1],"x"),s.moveDragger&&(c[0]*=i.scrollRatio.y,c[1]*=i.scrollRatio.x),s.dur=ne()?0:d,setTimeout(function(){null!==c[0]&&"undefined"!=typeof c[0]&&"x"!==r.axis&&i.overflowed[0]&&(s.dir="y",s.overwrite="all",G(n,c[0].toString(),s)),null!==c[1]&&"undefined"!=typeof c[1]&&"y"!==r.axis&&i.overflowed[1]&&(s.dir="x",s.overwrite="none",G(n,c[1].toString(),s))},s.timeout)}})}},stop:function(){var t=f.call(this);return e(t).each(function(){var t=e(this);t.data(a)&&Q(t)})},disable:function(t){var o=f.call(this);return e(o).each(function(){var o=e(this);if(o.data(a)){o.data(a);N.call(this,"remove"),k.call(this),t&&B.call(this),M.call(this,!0),o.addClass(d[3])}})},destroy:function(){var t=f.call(this);return e(t).each(function(){var n=e(this);if(n.data(a)){var i=n.data(a),r=i.opt,l=e("#mCSB_"+i.idx),s=e("#mCSB_"+i.idx+"_container"),c=e(".mCSB_"+i.idx+"_scrollbar");r.live&&m(r.liveSelector||e(t).selector),N.call(this,"remove"),k.call(this),B.call(this),n.removeData(a),$(this,"mcs"),c.remove(),s.find("img."+d[2]).removeClass(d[2]),l.replaceWith(s.contents()),n.removeClass(o+" _"+a+"_"+i.idx+" "+d[6]+" "+d[7]+" "+d[5]+" "+d[3]).addClass(d[4])}})}},f=function(){return"object"!=typeof e(this)||e(this).length<1?n:this},h=function(t){var o=["rounded","rounded-dark","rounded-dots","rounded-dots-dark"],a=["rounded-dots","rounded-dots-dark","3d","3d-dark","3d-thick","3d-thick-dark","inset","inset-dark","inset-2","inset-2-dark","inset-3","inset-3-dark"],n=["minimal","minimal-dark"],i=["minimal","minimal-dark"],r=["minimal","minimal-dark"];t.autoDraggerLength=e.inArray(t.theme,o)>-1?!1:t.autoDraggerLength,t.autoExpandScrollbar=e.inArray(t.theme,a)>-1?!1:t.autoExpandScrollbar,t.scrollButtons.enable=e.inArray(t.theme,n)>-1?!1:t.scrollButtons.enable,t.autoHideScrollbar=e.inArray(t.theme,i)>-1?!0:t.autoHideScrollbar,t.scrollbarPosition=e.inArray(t.theme,r)>-1?"outside":t.scrollbarPosition},m=function(e){l[e]&&(clearTimeout(l[e]),$(l,e))},p=function(e){return"yx"===e||"xy"===e||"auto"===e?"yx":"x"===e||"horizontal"===e?"x":"y"},g=function(e){return"stepped"===e||"pixels"===e||"step"===e||"click"===e?"stepped":"stepless"},v=function(){var t=e(this),n=t.data(a),i=n.opt,r=i.autoExpandScrollbar?" "+d[1]+"_expand":"",l=["<div id='mCSB_"+n.idx+"_scrollbar_vertical' class='mCSB_scrollTools mCSB_"+n.idx+"_scrollbar mCS-"+i.theme+" mCSB_scrollTools_vertical"+r+"'><div class='"+d[12]+"'><div id='mCSB_"+n.idx+"_dragger_vertical' class='mCSB_dragger' style='position:absolute;'><div class='mCSB_dragger_bar' /></div><div class='mCSB_draggerRail' /></div></div>","<div id='mCSB_"+n.idx+"_scrollbar_horizontal' class='mCSB_scrollTools mCSB_"+n.idx+"_scrollbar mCS-"+i.theme+" mCSB_scrollTools_horizontal"+r+"'><div class='"+d[12]+"'><div id='mCSB_"+n.idx+"_dragger_horizontal' class='mCSB_dragger' style='position:absolute;'><div class='mCSB_dragger_bar' /></div><div class='mCSB_draggerRail' /></div></div>"],s="yx"===i.axis?"mCSB_vertical_horizontal":"x"===i.axis?"mCSB_horizontal":"mCSB_vertical",c="yx"===i.axis?l[0]+l[1]:"x"===i.axis?l[1]:l[0],u="yx"===i.axis?"<div id='mCSB_"+n.idx+"_container_wrapper' class='mCSB_container_wrapper' />":"",f=i.autoHideScrollbar?" "+d[6]:"",h="x"!==i.axis&&"rtl"===n.langDir?" "+d[7]:"";i.setWidth&&t.css("width",i.setWidth),i.setHeight&&t.css("height",i.setHeight),i.setLeft="y"!==i.axis&&"rtl"===n.langDir?"989999px":i.setLeft,t.addClass(o+" _"+a+"_"+n.idx+f+h).wrapInner("<div id='mCSB_"+n.idx+"' class='mCustomScrollBox mCS-"+i.theme+" "+s+"'><div id='mCSB_"+n.idx+"_container' class='mCSB_container' style='position:relative; top:"+i.setTop+"; left:"+i.setLeft+";' dir='"+n.langDir+"' /></div>");var m=e("#mCSB_"+n.idx),p=e("#mCSB_"+n.idx+"_container");"y"===i.axis||i.advanced.autoExpandHorizontalScroll||p.css("width",x(p)),"outside"===i.scrollbarPosition?("static"===t.css("position")&&t.css("position","relative"),t.css("overflow","visible"),m.addClass("mCSB_outside").after(c)):(m.addClass("mCSB_inside").append(c),p.wrap(u)),w.call(this);var g=[e("#mCSB_"+n.idx+"_dragger_vertical"),e("#mCSB_"+n.idx+"_dragger_horizontal")];g[0].css("min-height",g[0].height()),g[1].css("min-width",g[1].width())},x=function(t){var o=[t[0].scrollWidth,Math.max.apply(Math,t.children().map(function(){return e(this).outerWidth(!0)}).get())],a=t.parent().width();return o[0]>a?o[0]:o[1]>a?o[1]:"100%"},_=function(){var t=e(this),o=t.data(a),n=o.opt,i=e("#mCSB_"+o.idx+"_container");if(n.advanced.autoExpandHorizontalScroll&&"y"!==n.axis){i.css({width:"auto","min-width":0,"overflow-x":"scroll"});var r=Math.ceil(i[0].scrollWidth);3===n.advanced.autoExpandHorizontalScroll||2!==n.advanced.autoExpandHorizontalScroll&&r>i.parent().width()?i.css({width:r,"min-width":"100%","overflow-x":"inherit"}):i.css({"overflow-x":"inherit",position:"absolute"}).wrap("<div class='mCSB_h_wrapper' style='position:relative; left:0; width:999999px;' />").css({width:Math.ceil(i[0].getBoundingClientRect().right+.4)-Math.floor(i[0].getBoundingClientRect().left),"min-width":"100%",position:"relative"}).unwrap()}},w=function(){var t=e(this),o=t.data(a),n=o.opt,i=e(".mCSB_"+o.idx+"_scrollbar:first"),r=oe(n.scrollButtons.tabindex)?"tabindex='"+n.scrollButtons.tabindex+"'":"",l=["<a href='#' class='"+d[13]+"' "+r+" />","<a href='#' class='"+d[14]+"' "+r+" />","<a href='#' class='"+d[15]+"' "+r+" />","<a href='#' class='"+d[16]+"' "+r+" />"],s=["x"===n.axis?l[2]:l[0],"x"===n.axis?l[3]:l[1],l[2],l[3]];n.scrollButtons.enable&&i.prepend(s[0]).append(s[1]).next(".mCSB_scrollTools").prepend(s[2]).append(s[3])},S=function(){var t=e(this),o=t.data(a),n=e("#mCSB_"+o.idx),i=e("#mCSB_"+o.idx+"_container"),r=[e("#mCSB_"+o.idx+"_dragger_vertical"),e("#mCSB_"+o.idx+"_dragger_horizontal")],l=[n.height()/i.outerHeight(!1),n.width()/i.outerWidth(!1)],c=[parseInt(r[0].css("min-height")),Math.round(l[0]*r[0].parent().height()),parseInt(r[1].css("min-width")),Math.round(l[1]*r[1].parent().width())],d=s&&c[1]<c[0]?c[0]:c[1],u=s&&c[3]<c[2]?c[2]:c[3];r[0].css({height:d,"max-height":r[0].parent().height()-10}).find(".mCSB_dragger_bar").css({"line-height":c[0]+"px"}),r[1].css({width:u,"max-width":r[1].parent().width()-10})},b=function(){var t=e(this),o=t.data(a),n=e("#mCSB_"+o.idx),i=e("#mCSB_"+o.idx+"_container"),r=[e("#mCSB_"+o.idx+"_dragger_vertical"),e("#mCSB_"+o.idx+"_dragger_horizontal")],l=[i.outerHeight(!1)-n.height(),i.outerWidth(!1)-n.width()],s=[l[0]/(r[0].parent().height()-r[0].height()),l[1]/(r[1].parent().width()-r[1].width())];o.scrollRatio={y:s[0],x:s[1]}},C=function(e,t,o){var a=o?d[0]+"_expanded":"",n=e.closest(".mCSB_scrollTools");"active"===t?(e.toggleClass(d[0]+" "+a),n.toggleClass(d[1]),e[0]._draggable=e[0]._draggable?0:1):e[0]._draggable||("hide"===t?(e.removeClass(d[0]),n.removeClass(d[1])):(e.addClass(d[0]),n.addClass(d[1])))},y=function(){var t=e(this),o=t.data(a),n=e("#mCSB_"+o.idx),i=e("#mCSB_"+o.idx+"_container"),r=null==o.overflowed?i.height():i.outerHeight(!1),l=null==o.overflowed?i.width():i.outerWidth(!1),s=i[0].scrollHeight,c=i[0].scrollWidth;return s>r&&(r=s),c>l&&(l=c),[r>n.height(),l>n.width()]},B=function(){var t=e(this),o=t.data(a),n=o.opt,i=e("#mCSB_"+o.idx),r=e("#mCSB_"+o.idx+"_container"),l=[e("#mCSB_"+o.idx+"_dragger_vertical"),e("#mCSB_"+o.idx+"_dragger_horizontal")];if(Q(t),("x"!==n.axis&&!o.overflowed[0]||"y"===n.axis&&o.overflowed[0])&&(l[0].add(r).css("top",0),G(t,"_resetY")),"y"!==n.axis&&!o.overflowed[1]||"x"===n.axis&&o.overflowed[1]){var s=dx=0;"rtl"===o.langDir&&(s=i.width()-r.outerWidth(!1),dx=Math.abs(s/o.scrollRatio.x)),r.css("left",s),l[1].css("left",dx),G(t,"_resetX")}},T=function(){function t(){r=setTimeout(function(){e.event.special.mousewheel?(clearTimeout(r),W.call(o[0])):t()},100)}var o=e(this),n=o.data(a),i=n.opt;if(!n.bindEvents){if(I.call(this),i.contentTouchScroll&&D.call(this),E.call(this),i.mouseWheel.enable){var r;t()}P.call(this),U.call(this),i.advanced.autoScrollOnFocus&&H.call(this),i.scrollButtons.enable&&F.call(this),i.keyboard.enable&&q.call(this),n.bindEvents=!0}},k=function(){var t=e(this),o=t.data(a),n=o.opt,i=a+"_"+o.idx,r=".mCSB_"+o.idx+"_scrollbar",l=e("#mCSB_"+o.idx+",#mCSB_"+o.idx+"_container,#mCSB_"+o.idx+"_container_wrapper,"+r+" ."+d[12]+",#mCSB_"+o.idx+"_dragger_vertical,#mCSB_"+o.idx+"_dragger_horizontal,"+r+">a"),s=e("#mCSB_"+o.idx+"_container");n.advanced.releaseDraggableSelectors&&l.add(e(n.advanced.releaseDraggableSelectors)),n.advanced.extraDraggableSelectors&&l.add(e(n.advanced.extraDraggableSelectors)),o.bindEvents&&(e(document).add(e(!A()||top.document)).unbind("."+i),l.each(function(){e(this).unbind("."+i)}),clearTimeout(t[0]._focusTimeout),$(t[0],"_focusTimeout"),clearTimeout(o.sequential.step),$(o.sequential,"step"),clearTimeout(s[0].onCompleteTimeout),$(s[0],"onCompleteTimeout"),o.bindEvents=!1)},M=function(t){var o=e(this),n=o.data(a),i=n.opt,r=e("#mCSB_"+n.idx+"_container_wrapper"),l=r.length?r:e("#mCSB_"+n.idx+"_container"),s=[e("#mCSB_"+n.idx+"_scrollbar_vertical"),e("#mCSB_"+n.idx+"_scrollbar_horizontal")],c=[s[0].find(".mCSB_dragger"),s[1].find(".mCSB_dragger")];"x"!==i.axis&&(n.overflowed[0]&&!t?(s[0].add(c[0]).add(s[0].children("a")).css("display","block"),l.removeClass(d[8]+" "+d[10])):(i.alwaysShowScrollbar?(2!==i.alwaysShowScrollbar&&c[0].css("display","none"),l.removeClass(d[10])):(s[0].css("display","none"),l.addClass(d[10])),l.addClass(d[8]))),"y"!==i.axis&&(n.overflowed[1]&&!t?(s[1].add(c[1]).add(s[1].children("a")).css("display","block"),l.removeClass(d[9]+" "+d[11])):(i.alwaysShowScrollbar?(2!==i.alwaysShowScrollbar&&c[1].css("display","none"),l.removeClass(d[11])):(s[1].css("display","none"),l.addClass(d[11])),l.addClass(d[9]))),n.overflowed[0]||n.overflowed[1]?o.removeClass(d[5]):o.addClass(d[5])},O=function(t){var o=t.type,a=t.target.ownerDocument!==document&&null!==frameElement?[e(frameElement).offset().top,e(frameElement).offset().left]:null,n=A()&&t.target.ownerDocument!==top.document&&null!==frameElement?[e(t.view.frameElement).offset().top,e(t.view.frameElement).offset().left]:[0,0];switch(o){case"pointerdown":case"MSPointerDown":case"pointermove":case"MSPointerMove":case"pointerup":case"MSPointerUp":return a?[t.originalEvent.pageY-a[0]+n[0],t.originalEvent.pageX-a[1]+n[1],!1]:[t.originalEvent.pageY,t.originalEvent.pageX,!1];case"touchstart":case"touchmove":case"touchend":var i=t.originalEvent.touches[0]||t.originalEvent.changedTouches[0],r=t.originalEvent.touches.length||t.originalEvent.changedTouches.length;return t.target.ownerDocument!==document?[i.screenY,i.screenX,r>1]:[i.pageY,i.pageX,r>1];default:return a?[t.pageY-a[0]+n[0],t.pageX-a[1]+n[1],!1]:[t.pageY,t.pageX,!1]}},I=function(){function t(e,t,a,n){if(h[0].idleTimer=d.scrollInertia<233?250:0,o.attr("id")===f[1])var i="x",s=(o[0].offsetLeft-t+n)*l.scrollRatio.x;else var i="y",s=(o[0].offsetTop-e+a)*l.scrollRatio.y;G(r,s.toString(),{dir:i,drag:!0})}var o,n,i,r=e(this),l=r.data(a),d=l.opt,u=a+"_"+l.idx,f=["mCSB_"+l.idx+"_dragger_vertical","mCSB_"+l.idx+"_dragger_horizontal"],h=e("#mCSB_"+l.idx+"_container"),m=e("#"+f[0]+",#"+f[1]),p=d.advanced.releaseDraggableSelectors?m.add(e(d.advanced.releaseDraggableSelectors)):m,g=d.advanced.extraDraggableSelectors?e(!A()||top.document).add(e(d.advanced.extraDraggableSelectors)):e(!A()||top.document);m.bind("contextmenu."+u,function(e){e.preventDefault()}).bind("mousedown."+u+" touchstart."+u+" pointerdown."+u+" MSPointerDown."+u,function(t){if(t.stopImmediatePropagation(),t.preventDefault(),ee(t)){c=!0,s&&(document.onselectstart=function(){return!1}),L.call(h,!1),Q(r),o=e(this);var a=o.offset(),l=O(t)[0]-a.top,u=O(t)[1]-a.left,f=o.height()+a.top,m=o.width()+a.left;f>l&&l>0&&m>u&&u>0&&(n=l,i=u),C(o,"active",d.autoExpandScrollbar)}}).bind("touchmove."+u,function(e){e.stopImmediatePropagation(),e.preventDefault();var a=o.offset(),r=O(e)[0]-a.top,l=O(e)[1]-a.left;t(n,i,r,l)}),e(document).add(g).bind("mousemove."+u+" pointermove."+u+" MSPointerMove."+u,function(e){if(o){var a=o.offset(),r=O(e)[0]-a.top,l=O(e)[1]-a.left;if(n===r&&i===l)return;t(n,i,r,l)}}).add(p).bind("mouseup."+u+" touchend."+u+" pointerup."+u+" MSPointerUp."+u,function(){o&&(C(o,"active",d.autoExpandScrollbar),o=null),c=!1,s&&(document.onselectstart=null),L.call(h,!0)})},D=function(){function o(e){if(!te(e)||c||O(e)[2])return void(t=0);t=1,b=0,C=0,d=1,y.removeClass("mCS_touch_action");var o=I.offset();u=O(e)[0]-o.top,f=O(e)[1]-o.left,z=[O(e)[0],O(e)[1]]}function n(e){if(te(e)&&!c&&!O(e)[2]&&(T.documentTouchScroll||e.preventDefault(),e.stopImmediatePropagation(),(!C||b)&&d)){g=K();var t=M.offset(),o=O(e)[0]-t.top,a=O(e)[1]-t.left,n="mcsLinearOut";if(E.push(o),W.push(a),z[2]=Math.abs(O(e)[0]-z[0]),z[3]=Math.abs(O(e)[1]-z[1]),B.overflowed[0])var i=D[0].parent().height()-D[0].height(),r=u-o>0&&o-u>-(i*B.scrollRatio.y)&&(2*z[3]<z[2]||"yx"===T.axis);if(B.overflowed[1])var l=D[1].parent().width()-D[1].width(),h=f-a>0&&a-f>-(l*B.scrollRatio.x)&&(2*z[2]<z[3]||"yx"===T.axis);r||h?(U||e.preventDefault(),b=1):(C=1,y.addClass("mCS_touch_action")),U&&e.preventDefault(),w="yx"===T.axis?[u-o,f-a]:"x"===T.axis?[null,f-a]:[u-o,null],I[0].idleTimer=250,B.overflowed[0]&&s(w[0],R,n,"y","all",!0),B.overflowed[1]&&s(w[1],R,n,"x",L,!0)}}function i(e){if(!te(e)||c||O(e)[2])return void(t=0);t=1,e.stopImmediatePropagation(),Q(y),p=K();var o=M.offset();h=O(e)[0]-o.top,m=O(e)[1]-o.left,E=[],W=[]}function r(e){if(te(e)&&!c&&!O(e)[2]){d=0,e.stopImmediatePropagation(),b=0,C=0,v=K();var t=M.offset(),o=O(e)[0]-t.top,a=O(e)[1]-t.left;if(!(v-g>30)){_=1e3/(v-p);var n="mcsEaseOut",i=2.5>_,r=i?[E[E.length-2],W[W.length-2]]:[0,0];x=i?[o-r[0],a-r[1]]:[o-h,a-m];var u=[Math.abs(x[0]),Math.abs(x[1])];_=i?[Math.abs(x[0]/4),Math.abs(x[1]/4)]:[_,_];var f=[Math.abs(I[0].offsetTop)-x[0]*l(u[0]/_[0],_[0]),Math.abs(I[0].offsetLeft)-x[1]*l(u[1]/_[1],_[1])];w="yx"===T.axis?[f[0],f[1]]:"x"===T.axis?[null,f[1]]:[f[0],null],S=[4*u[0]+T.scrollInertia,4*u[1]+T.scrollInertia];var y=parseInt(T.contentTouchScroll)||0;w[0]=u[0]>y?w[0]:0,w[1]=u[1]>y?w[1]:0,B.overflowed[0]&&s(w[0],S[0],n,"y",L,!1),B.overflowed[1]&&s(w[1],S[1],n,"x",L,!1)}}}function l(e,t){var o=[1.5*t,2*t,t/1.5,t/2];return e>90?t>4?o[0]:o[3]:e>60?t>3?o[3]:o[2]:e>30?t>8?o[1]:t>6?o[0]:t>4?t:o[2]:t>8?t:o[3]}function s(e,t,o,a,n,i){e&&G(y,e.toString(),{dur:t,scrollEasing:o,dir:a,overwrite:n,drag:i})}var d,u,f,h,m,p,g,v,x,_,w,S,b,C,y=e(this),B=y.data(a),T=B.opt,k=a+"_"+B.idx,M=e("#mCSB_"+B.idx),I=e("#mCSB_"+B.idx+"_container"),D=[e("#mCSB_"+B.idx+"_dragger_vertical"),e("#mCSB_"+B.idx+"_dragger_horizontal")],E=[],W=[],R=0,L="yx"===T.axis?"none":"all",z=[],P=I.find("iframe"),H=["touchstart."+k+" pointerdown."+k+" MSPointerDown."+k,"touchmove."+k+" pointermove."+k+" MSPointerMove."+k,"touchend."+k+" pointerup."+k+" MSPointerUp."+k],U=void 0!==document.body.style.touchAction&&""!==document.body.style.touchAction;I.bind(H[0],function(e){o(e)}).bind(H[1],function(e){n(e)}),M.bind(H[0],function(e){i(e)}).bind(H[2],function(e){r(e)}),P.length&&P.each(function(){e(this).bind("load",function(){A(this)&&e(this.contentDocument||this.contentWindow.document).bind(H[0],function(e){o(e),i(e)}).bind(H[1],function(e){n(e)}).bind(H[2],function(e){r(e)})})})},E=function(){function o(){return window.getSelection?window.getSelection().toString():document.selection&&"Control"!=document.selection.type?document.selection.createRange().text:0}function n(e,t,o){d.type=o&&i?"stepped":"stepless",d.scrollAmount=10,j(r,e,t,"mcsLinearOut",o?60:null)}var i,r=e(this),l=r.data(a),s=l.opt,d=l.sequential,u=a+"_"+l.idx,f=e("#mCSB_"+l.idx+"_container"),h=f.parent();f.bind("mousedown."+u,function(){t||i||(i=1,c=!0)}).add(document).bind("mousemove."+u,function(e){if(!t&&i&&o()){var a=f.offset(),r=O(e)[0]-a.top+f[0].offsetTop,c=O(e)[1]-a.left+f[0].offsetLeft;r>0&&r<h.height()&&c>0&&c<h.width()?d.step&&n("off",null,"stepped"):("x"!==s.axis&&l.overflowed[0]&&(0>r?n("on",38):r>h.height()&&n("on",40)),"y"!==s.axis&&l.overflowed[1]&&(0>c?n("on",37):c>h.width()&&n("on",39)))}}).bind("mouseup."+u+" dragend."+u,function(){t||(i&&(i=0,n("off",null)),c=!1)})},W=function(){function t(t,a){if(Q(o),!z(o,t.target)){var r="auto"!==i.mouseWheel.deltaFactor?parseInt(i.mouseWheel.deltaFactor):s&&t.deltaFactor<100?100:t.deltaFactor||100,d=i.scrollInertia;if("x"===i.axis||"x"===i.mouseWheel.axis)var u="x",f=[Math.round(r*n.scrollRatio.x),parseInt(i.mouseWheel.scrollAmount)],h="auto"!==i.mouseWheel.scrollAmount?f[1]:f[0]>=l.width()?.9*l.width():f[0],m=Math.abs(e("#mCSB_"+n.idx+"_container")[0].offsetLeft),p=c[1][0].offsetLeft,g=c[1].parent().width()-c[1].width(),v="y"===i.mouseWheel.axis?t.deltaY||a:t.deltaX;else var u="y",f=[Math.round(r*n.scrollRatio.y),parseInt(i.mouseWheel.scrollAmount)],h="auto"!==i.mouseWheel.scrollAmount?f[1]:f[0]>=l.height()?.9*l.height():f[0],m=Math.abs(e("#mCSB_"+n.idx+"_container")[0].offsetTop),p=c[0][0].offsetTop,g=c[0].parent().height()-c[0].height(),v=t.deltaY||a;"y"===u&&!n.overflowed[0]||"x"===u&&!n.overflowed[1]||((i.mouseWheel.invert||t.webkitDirectionInvertedFromDevice)&&(v=-v),i.mouseWheel.normalizeDelta&&(v=0>v?-1:1),(v>0&&0!==p||0>v&&p!==g||i.mouseWheel.preventDefault)&&(t.stopImmediatePropagation(),t.preventDefault()),t.deltaFactor<5&&!i.mouseWheel.normalizeDelta&&(h=t.deltaFactor,d=17),G(o,(m-v*h).toString(),{dir:u,dur:d}))}}if(e(this).data(a)){var o=e(this),n=o.data(a),i=n.opt,r=a+"_"+n.idx,l=e("#mCSB_"+n.idx),c=[e("#mCSB_"+n.idx+"_dragger_vertical"),e("#mCSB_"+n.idx+"_dragger_horizontal")],d=e("#mCSB_"+n.idx+"_container").find("iframe");d.length&&d.each(function(){e(this).bind("load",function(){A(this)&&e(this.contentDocument||this.contentWindow.document).bind("mousewheel."+r,function(e,o){t(e,o)})})}),l.bind("mousewheel."+r,function(e,o){t(e,o)})}},R=new Object,A=function(t){var o=!1,a=!1,n=null;if(void 0===t?a="#empty":void 0!==e(t).attr("id")&&(a=e(t).attr("id")),a!==!1&&void 0!==R[a])return R[a];if(t){try{var i=t.contentDocument||t.contentWindow.document;n=i.body.innerHTML}catch(r){}o=null!==n}else{try{var i=top.document;n=i.body.innerHTML}catch(r){}o=null!==n}return a!==!1&&(R[a]=o),o},L=function(e){var t=this.find("iframe");if(t.length){var o=e?"auto":"none";t.css("pointer-events",o)}},z=function(t,o){var n=o.nodeName.toLowerCase(),i=t.data(a).opt.mouseWheel.disableOver,r=["select","textarea"];return e.inArray(n,i)>-1&&!(e.inArray(n,r)>-1&&!e(o).is(":focus"))},P=function(){var t,o=e(this),n=o.data(a),i=a+"_"+n.idx,r=e("#mCSB_"+n.idx+"_container"),l=r.parent(),s=e(".mCSB_"+n.idx+"_scrollbar ."+d[12]);s.bind("mousedown."+i+" touchstart."+i+" pointerdown."+i+" MSPointerDown."+i,function(o){c=!0,e(o.target).hasClass("mCSB_dragger")||(t=1)}).bind("touchend."+i+" pointerup."+i+" MSPointerUp."+i,function(){c=!1}).bind("click."+i,function(a){if(t&&(t=0,e(a.target).hasClass(d[12])||e(a.target).hasClass("mCSB_draggerRail"))){Q(o);var i=e(this),s=i.find(".mCSB_dragger");if(i.parent(".mCSB_scrollTools_horizontal").length>0){if(!n.overflowed[1])return;var c="x",u=a.pageX>s.offset().left?-1:1,f=Math.abs(r[0].offsetLeft)-u*(.9*l.width())}else{if(!n.overflowed[0])return;var c="y",u=a.pageY>s.offset().top?-1:1,f=Math.abs(r[0].offsetTop)-u*(.9*l.height())}G(o,f.toString(),{dir:c,scrollEasing:"mcsEaseInOut"})}})},H=function(){var t=e(this),o=t.data(a),n=o.opt,i=a+"_"+o.idx,r=e("#mCSB_"+o.idx+"_container"),l=r.parent();r.bind("focusin."+i,function(){var o=e(document.activeElement),a=r.find(".mCustomScrollBox").length,i=0;o.is(n.advanced.autoScrollOnFocus)&&(Q(t),clearTimeout(t[0]._focusTimeout),t[0]._focusTimer=a?(i+17)*a:0,t[0]._focusTimeout=setTimeout(function(){var e=[ae(o)[0],ae(o)[1]],a=[r[0].offsetTop,r[0].offsetLeft],s=[a[0]+e[0]>=0&&a[0]+e[0]<l.height()-o.outerHeight(!1),a[1]+e[1]>=0&&a[0]+e[1]<l.width()-o.outerWidth(!1)],c="yx"!==n.axis||s[0]||s[1]?"all":"none";"x"===n.axis||s[0]||G(t,e[0].toString(),{dir:"y",scrollEasing:"mcsEaseInOut",overwrite:c,dur:i}),"y"===n.axis||s[1]||G(t,e[1].toString(),{dir:"x",scrollEasing:"mcsEaseInOut",overwrite:c,dur:i})},t[0]._focusTimer))})},U=function(){var t=e(this),o=t.data(a),n=a+"_"+o.idx,i=e("#mCSB_"+o.idx+"_container").parent();i.bind("scroll."+n,function(){0===i.scrollTop()&&0===i.scrollLeft()||e(".mCSB_"+o.idx+"_scrollbar").css("visibility","hidden")})},F=function(){var t=e(this),o=t.data(a),n=o.opt,i=o.sequential,r=a+"_"+o.idx,l=".mCSB_"+o.idx+"_scrollbar",s=e(l+">a");s.bind("contextmenu."+r,function(e){e.preventDefault()}).bind("mousedown."+r+" touchstart."+r+" pointerdown."+r+" MSPointerDown."+r+" mouseup."+r+" touchend."+r+" pointerup."+r+" MSPointerUp."+r+" mouseout."+r+" pointerout."+r+" MSPointerOut."+r+" click."+r,function(a){function r(e,o){i.scrollAmount=n.scrollButtons.scrollAmount,j(t,e,o)}if(a.preventDefault(),ee(a)){var l=e(this).attr("class");switch(i.type=n.scrollButtons.scrollType,a.type){case"mousedown":case"touchstart":case"pointerdown":case"MSPointerDown":if("stepped"===i.type)return;c=!0,o.tweenRunning=!1,r("on",l);break;case"mouseup":case"touchend":case"pointerup":case"MSPointerUp":case"mouseout":case"pointerout":case"MSPointerOut":if("stepped"===i.type)return;c=!1,i.dir&&r("off",l);break;case"click":if("stepped"!==i.type||o.tweenRunning)return;r("on",l)}}})},q=function(){function t(t){function a(e,t){r.type=i.keyboard.scrollType,r.scrollAmount=i.keyboard.scrollAmount,"stepped"===r.type&&n.tweenRunning||j(o,e,t)}switch(t.type){case"blur":n.tweenRunning&&r.dir&&a("off",null);break;case"keydown":case"keyup":var l=t.keyCode?t.keyCode:t.which,s="on";if("x"!==i.axis&&(38===l||40===l)||"y"!==i.axis&&(37===l||39===l)){if((38===l||40===l)&&!n.overflowed[0]||(37===l||39===l)&&!n.overflowed[1])return;"keyup"===t.type&&(s="off"),e(document.activeElement).is(u)||(t.preventDefault(),t.stopImmediatePropagation(),a(s,l))}else if(33===l||34===l){if((n.overflowed[0]||n.overflowed[1])&&(t.preventDefault(),t.stopImmediatePropagation()),"keyup"===t.type){Q(o);var f=34===l?-1:1;if("x"===i.axis||"yx"===i.axis&&n.overflowed[1]&&!n.overflowed[0])var h="x",m=Math.abs(c[0].offsetLeft)-f*(.9*d.width());else var h="y",m=Math.abs(c[0].offsetTop)-f*(.9*d.height());G(o,m.toString(),{dir:h,scrollEasing:"mcsEaseInOut"})}}else if((35===l||36===l)&&!e(document.activeElement).is(u)&&((n.overflowed[0]||n.overflowed[1])&&(t.preventDefault(),t.stopImmediatePropagation()),"keyup"===t.type)){if("x"===i.axis||"yx"===i.axis&&n.overflowed[1]&&!n.overflowed[0])var h="x",m=35===l?Math.abs(d.width()-c.outerWidth(!1)):0;else var h="y",m=35===l?Math.abs(d.height()-c.outerHeight(!1)):0;G(o,m.toString(),{dir:h,scrollEasing:"mcsEaseInOut"})}}}var o=e(this),n=o.data(a),i=n.opt,r=n.sequential,l=a+"_"+n.idx,s=e("#mCSB_"+n.idx),c=e("#mCSB_"+n.idx+"_container"),d=c.parent(),u="input,textarea,select,datalist,keygen,[contenteditable='true']",f=c.find("iframe"),h=["blur."+l+" keydown."+l+" keyup."+l];f.length&&f.each(function(){e(this).bind("load",function(){A(this)&&e(this.contentDocument||this.contentWindow.document).bind(h[0],function(e){t(e)})})}),s.attr("tabindex","0").bind(h[0],function(e){t(e)})},j=function(t,o,n,i,r){function l(e){u.snapAmount&&(f.scrollAmount=u.snapAmount instanceof Array?"x"===f.dir[0]?u.snapAmount[1]:u.snapAmount[0]:u.snapAmount);var o="stepped"!==f.type,a=r?r:e?o?p/1.5:g:1e3/60,n=e?o?7.5:40:2.5,s=[Math.abs(h[0].offsetTop),Math.abs(h[0].offsetLeft)],d=[c.scrollRatio.y>10?10:c.scrollRatio.y,c.scrollRatio.x>10?10:c.scrollRatio.x],m="x"===f.dir[0]?s[1]+f.dir[1]*(d[1]*n):s[0]+f.dir[1]*(d[0]*n),v="x"===f.dir[0]?s[1]+f.dir[1]*parseInt(f.scrollAmount):s[0]+f.dir[1]*parseInt(f.scrollAmount),x="auto"!==f.scrollAmount?v:m,_=i?i:e?o?"mcsLinearOut":"mcsEaseInOut":"mcsLinear",w=!!e;return e&&17>a&&(x="x"===f.dir[0]?s[1]:s[0]),G(t,x.toString(),{dir:f.dir[0],scrollEasing:_,dur:a,onComplete:w}),e?void(f.dir=!1):(clearTimeout(f.step),void(f.step=setTimeout(function(){l()},a)))}function s(){clearTimeout(f.step),$(f,"step"),Q(t)}var c=t.data(a),u=c.opt,f=c.sequential,h=e("#mCSB_"+c.idx+"_container"),m="stepped"===f.type,p=u.scrollInertia<26?26:u.scrollInertia,g=u.scrollInertia<1?17:u.scrollInertia;switch(o){case"on":if(f.dir=[n===d[16]||n===d[15]||39===n||37===n?"x":"y",n===d[13]||n===d[15]||38===n||37===n?-1:1],Q(t),oe(n)&&"stepped"===f.type)return;l(m);break;case"off":s(),(m||c.tweenRunning&&f.dir)&&l(!0)}},Y=function(t){var o=e(this).data(a).opt,n=[];return"function"==typeof t&&(t=t()),t instanceof Array?n=t.length>1?[t[0],t[1]]:"x"===o.axis?[null,t[0]]:[t[0],null]:(n[0]=t.y?t.y:t.x||"x"===o.axis?null:t,n[1]=t.x?t.x:t.y||"y"===o.axis?null:t),"function"==typeof n[0]&&(n[0]=n[0]()),"function"==typeof n[1]&&(n[1]=n[1]()),n},X=function(t,o){if(null!=t&&"undefined"!=typeof t){var n=e(this),i=n.data(a),r=i.opt,l=e("#mCSB_"+i.idx+"_container"),s=l.parent(),c=typeof t;o||(o="x"===r.axis?"x":"y");var d="x"===o?l.outerWidth(!1)-s.width():l.outerHeight(!1)-s.height(),f="x"===o?l[0].offsetLeft:l[0].offsetTop,h="x"===o?"left":"top";switch(c){case"function":return t();case"object":var m=t.jquery?t:e(t);if(!m.length)return;return"x"===o?ae(m)[1]:ae(m)[0];case"string":case"number":if(oe(t))return Math.abs(t);if(-1!==t.indexOf("%"))return Math.abs(d*parseInt(t)/100);if(-1!==t.indexOf("-="))return Math.abs(f-parseInt(t.split("-=")[1]));if(-1!==t.indexOf("+=")){var p=f+parseInt(t.split("+=")[1]);return p>=0?0:Math.abs(p)}if(-1!==t.indexOf("px")&&oe(t.split("px")[0]))return Math.abs(t.split("px")[0]);if("top"===t||"left"===t)return 0;if("bottom"===t)return Math.abs(s.height()-l.outerHeight(!1));if("right"===t)return Math.abs(s.width()-l.outerWidth(!1));if("first"===t||"last"===t){var m=l.find(":"+t);return"x"===o?ae(m)[1]:ae(m)[0]}return e(t).length?"x"===o?ae(e(t))[1]:ae(e(t))[0]:(l.css(h,t),void u.update.call(null,n[0]))}}},N=function(t){function o(){return clearTimeout(f[0].autoUpdate),0===l.parents("html").length?void(l=null):void(f[0].autoUpdate=setTimeout(function(){return c.advanced.updateOnSelectorChange&&(s.poll.change.n=i(),s.poll.change.n!==s.poll.change.o)?(s.poll.change.o=s.poll.change.n,void r(3)):c.advanced.updateOnContentResize&&(s.poll.size.n=l[0].scrollHeight+l[0].scrollWidth+f[0].offsetHeight+l[0].offsetHeight+l[0].offsetWidth,s.poll.size.n!==s.poll.size.o)?(s.poll.size.o=s.poll.size.n,void r(1)):!c.advanced.updateOnImageLoad||"auto"===c.advanced.updateOnImageLoad&&"y"===c.axis||(s.poll.img.n=f.find("img").length,s.poll.img.n===s.poll.img.o)?void((c.advanced.updateOnSelectorChange||c.advanced.updateOnContentResize||c.advanced.updateOnImageLoad)&&o()):(s.poll.img.o=s.poll.img.n,void f.find("img").each(function(){n(this)}))},c.advanced.autoUpdateTimeout))}function n(t){function o(e,t){return function(){
return t.apply(e,arguments)}}function a(){this.onload=null,e(t).addClass(d[2]),r(2)}if(e(t).hasClass(d[2]))return void r();var n=new Image;n.onload=o(n,a),n.src=t.src}function i(){c.advanced.updateOnSelectorChange===!0&&(c.advanced.updateOnSelectorChange="*");var e=0,t=f.find(c.advanced.updateOnSelectorChange);return c.advanced.updateOnSelectorChange&&t.length>0&&t.each(function(){e+=this.offsetHeight+this.offsetWidth}),e}function r(e){clearTimeout(f[0].autoUpdate),u.update.call(null,l[0],e)}var l=e(this),s=l.data(a),c=s.opt,f=e("#mCSB_"+s.idx+"_container");return t?(clearTimeout(f[0].autoUpdate),void $(f[0],"autoUpdate")):void o()},V=function(e,t,o){return Math.round(e/t)*t-o},Q=function(t){var o=t.data(a),n=e("#mCSB_"+o.idx+"_container,#mCSB_"+o.idx+"_container_wrapper,#mCSB_"+o.idx+"_dragger_vertical,#mCSB_"+o.idx+"_dragger_horizontal");n.each(function(){Z.call(this)})},G=function(t,o,n){function i(e){return s&&c.callbacks[e]&&"function"==typeof c.callbacks[e]}function r(){return[c.callbacks.alwaysTriggerOffsets||w>=S[0]+y,c.callbacks.alwaysTriggerOffsets||-B>=w]}function l(){var e=[h[0].offsetTop,h[0].offsetLeft],o=[x[0].offsetTop,x[0].offsetLeft],a=[h.outerHeight(!1),h.outerWidth(!1)],i=[f.height(),f.width()];t[0].mcs={content:h,top:e[0],left:e[1],draggerTop:o[0],draggerLeft:o[1],topPct:Math.round(100*Math.abs(e[0])/(Math.abs(a[0])-i[0])),leftPct:Math.round(100*Math.abs(e[1])/(Math.abs(a[1])-i[1])),direction:n.dir}}var s=t.data(a),c=s.opt,d={trigger:"internal",dir:"y",scrollEasing:"mcsEaseOut",drag:!1,dur:c.scrollInertia,overwrite:"all",callbacks:!0,onStart:!0,onUpdate:!0,onComplete:!0},n=e.extend(d,n),u=[n.dur,n.drag?0:n.dur],f=e("#mCSB_"+s.idx),h=e("#mCSB_"+s.idx+"_container"),m=h.parent(),p=c.callbacks.onTotalScrollOffset?Y.call(t,c.callbacks.onTotalScrollOffset):[0,0],g=c.callbacks.onTotalScrollBackOffset?Y.call(t,c.callbacks.onTotalScrollBackOffset):[0,0];if(s.trigger=n.trigger,0===m.scrollTop()&&0===m.scrollLeft()||(e(".mCSB_"+s.idx+"_scrollbar").css("visibility","visible"),m.scrollTop(0).scrollLeft(0)),"_resetY"!==o||s.contentReset.y||(i("onOverflowYNone")&&c.callbacks.onOverflowYNone.call(t[0]),s.contentReset.y=1),"_resetX"!==o||s.contentReset.x||(i("onOverflowXNone")&&c.callbacks.onOverflowXNone.call(t[0]),s.contentReset.x=1),"_resetY"!==o&&"_resetX"!==o){if(!s.contentReset.y&&t[0].mcs||!s.overflowed[0]||(i("onOverflowY")&&c.callbacks.onOverflowY.call(t[0]),s.contentReset.x=null),!s.contentReset.x&&t[0].mcs||!s.overflowed[1]||(i("onOverflowX")&&c.callbacks.onOverflowX.call(t[0]),s.contentReset.x=null),c.snapAmount){var v=c.snapAmount instanceof Array?"x"===n.dir?c.snapAmount[1]:c.snapAmount[0]:c.snapAmount;o=V(o,v,c.snapOffset)}switch(n.dir){case"x":var x=e("#mCSB_"+s.idx+"_dragger_horizontal"),_="left",w=h[0].offsetLeft,S=[f.width()-h.outerWidth(!1),x.parent().width()-x.width()],b=[o,0===o?0:o/s.scrollRatio.x],y=p[1],B=g[1],T=y>0?y/s.scrollRatio.x:0,k=B>0?B/s.scrollRatio.x:0;break;case"y":var x=e("#mCSB_"+s.idx+"_dragger_vertical"),_="top",w=h[0].offsetTop,S=[f.height()-h.outerHeight(!1),x.parent().height()-x.height()],b=[o,0===o?0:o/s.scrollRatio.y],y=p[0],B=g[0],T=y>0?y/s.scrollRatio.y:0,k=B>0?B/s.scrollRatio.y:0}b[1]<0||0===b[0]&&0===b[1]?b=[0,0]:b[1]>=S[1]?b=[S[0],S[1]]:b[0]=-b[0],t[0].mcs||(l(),i("onInit")&&c.callbacks.onInit.call(t[0])),clearTimeout(h[0].onCompleteTimeout),J(x[0],_,Math.round(b[1]),u[1],n.scrollEasing),!s.tweenRunning&&(0===w&&b[0]>=0||w===S[0]&&b[0]<=S[0])||J(h[0],_,Math.round(b[0]),u[0],n.scrollEasing,n.overwrite,{onStart:function(){n.callbacks&&n.onStart&&!s.tweenRunning&&(i("onScrollStart")&&(l(),c.callbacks.onScrollStart.call(t[0])),s.tweenRunning=!0,C(x),s.cbOffsets=r())},onUpdate:function(){n.callbacks&&n.onUpdate&&i("whileScrolling")&&(l(),c.callbacks.whileScrolling.call(t[0]))},onComplete:function(){if(n.callbacks&&n.onComplete){"yx"===c.axis&&clearTimeout(h[0].onCompleteTimeout);var e=h[0].idleTimer||0;h[0].onCompleteTimeout=setTimeout(function(){i("onScroll")&&(l(),c.callbacks.onScroll.call(t[0])),i("onTotalScroll")&&b[1]>=S[1]-T&&s.cbOffsets[0]&&(l(),c.callbacks.onTotalScroll.call(t[0])),i("onTotalScrollBack")&&b[1]<=k&&s.cbOffsets[1]&&(l(),c.callbacks.onTotalScrollBack.call(t[0])),s.tweenRunning=!1,h[0].idleTimer=0,C(x,"hide")},e)}}})}},J=function(e,t,o,a,n,i,r){function l(){S.stop||(x||m.call(),x=K()-v,s(),x>=S.time&&(S.time=x>S.time?x+f-(x-S.time):x+f-1,S.time<x+1&&(S.time=x+1)),S.time<a?S.id=h(l):g.call())}function s(){a>0?(S.currVal=u(S.time,_,b,a,n),w[t]=Math.round(S.currVal)+"px"):w[t]=o+"px",p.call()}function c(){f=1e3/60,S.time=x+f,h=window.requestAnimationFrame?window.requestAnimationFrame:function(e){return s(),setTimeout(e,.01)},S.id=h(l)}function d(){null!=S.id&&(window.requestAnimationFrame?window.cancelAnimationFrame(S.id):clearTimeout(S.id),S.id=null)}function u(e,t,o,a,n){switch(n){case"linear":case"mcsLinear":return o*e/a+t;case"mcsLinearOut":return e/=a,e--,o*Math.sqrt(1-e*e)+t;case"easeInOutSmooth":return e/=a/2,1>e?o/2*e*e+t:(e--,-o/2*(e*(e-2)-1)+t);case"easeInOutStrong":return e/=a/2,1>e?o/2*Math.pow(2,10*(e-1))+t:(e--,o/2*(-Math.pow(2,-10*e)+2)+t);case"easeInOut":case"mcsEaseInOut":return e/=a/2,1>e?o/2*e*e*e+t:(e-=2,o/2*(e*e*e+2)+t);case"easeOutSmooth":return e/=a,e--,-o*(e*e*e*e-1)+t;case"easeOutStrong":return o*(-Math.pow(2,-10*e/a)+1)+t;case"easeOut":case"mcsEaseOut":default:var i=(e/=a)*e,r=i*e;return t+o*(.499999999999997*r*i+-2.5*i*i+5.5*r+-6.5*i+4*e)}}e._mTween||(e._mTween={top:{},left:{}});var f,h,r=r||{},m=r.onStart||function(){},p=r.onUpdate||function(){},g=r.onComplete||function(){},v=K(),x=0,_=e.offsetTop,w=e.style,S=e._mTween[t];"left"===t&&(_=e.offsetLeft);var b=o-_;S.stop=0,"none"!==i&&d(),c()},K=function(){return window.performance&&window.performance.now?window.performance.now():window.performance&&window.performance.webkitNow?window.performance.webkitNow():Date.now?Date.now():(new Date).getTime()},Z=function(){var e=this;e._mTween||(e._mTween={top:{},left:{}});for(var t=["top","left"],o=0;o<t.length;o++){var a=t[o];e._mTween[a].id&&(window.requestAnimationFrame?window.cancelAnimationFrame(e._mTween[a].id):clearTimeout(e._mTween[a].id),e._mTween[a].id=null,e._mTween[a].stop=1)}},$=function(e,t){try{delete e[t]}catch(o){e[t]=null}},ee=function(e){return!(e.which&&1!==e.which)},te=function(e){var t=e.originalEvent.pointerType;return!(t&&"touch"!==t&&2!==t)},oe=function(e){return!isNaN(parseFloat(e))&&isFinite(e)},ae=function(e){var t=e.parents(".mCSB_container");return[e.offset().top-t.offset().top,e.offset().left-t.offset().left]},ne=function(){function e(){var e=["webkit","moz","ms","o"];if("hidden"in document)return"hidden";for(var t=0;t<e.length;t++)if(e[t]+"Hidden"in document)return e[t]+"Hidden";return null}var t=e();return t?document[t]:!1};e.fn[o]=function(t){return u[t]?u[t].apply(this,Array.prototype.slice.call(arguments,1)):"object"!=typeof t&&t?void e.error("Method "+t+" does not exist"):u.init.apply(this,arguments)},e[o]=function(t){return u[t]?u[t].apply(this,Array.prototype.slice.call(arguments,1)):"object"!=typeof t&&t?void e.error("Method "+t+" does not exist"):u.init.apply(this,arguments)},e[o].defaults=i,window[o]=!0,e(window).bind("load",function(){e(n)[o](),e.extend(e.expr[":"],{mcsInView:e.expr[":"].mcsInView||function(t){var o,a,n=e(t),i=n.parents(".mCSB_container");if(i.length)return o=i.parent(),a=[i[0].offsetTop,i[0].offsetLeft],a[0]+ae(n)[0]>=0&&a[0]+ae(n)[0]<o.height()-n.outerHeight(!1)&&a[1]+ae(n)[1]>=0&&a[1]+ae(n)[1]<o.width()-n.outerWidth(!1)},mcsInSight:e.expr[":"].mcsInSight||function(t,o,a){var n,i,r,l,s=e(t),c=s.parents(".mCSB_container"),d="exact"===a[3]?[[1,0],[1,0]]:[[.9,.1],[.6,.4]];if(c.length)return n=[s.outerHeight(!1),s.outerWidth(!1)],r=[c[0].offsetTop+ae(s)[0],c[0].offsetLeft+ae(s)[1]],i=[c.parent()[0].offsetHeight,c.parent()[0].offsetWidth],l=[n[0]<i[0]?d[0]:d[1],n[1]<i[1]?d[0]:d[1]],r[0]-i[0]*l[0][0]<0&&r[0]+n[0]-i[0]*l[0][1]>=0&&r[1]-i[1]*l[1][0]<0&&r[1]+n[1]-i[1]*l[1][1]>=0},mcsOverflow:e.expr[":"].mcsOverflow||function(t){var o=e(t).data(a);if(o)return o.overflowed[0]||o.overflowed[1]}})})})});

/*! jQuery Mobile v1.4.5 | Copyright 2010, 2014 jQuery Foundation, Inc. | jquery.org/license */
(function(e,t,n){typeof define=="function"&&define.amd?define(["jquery"],function(r){return n(r,e,t),r.mobile}):n(e.jQuery,e,t)})(this,document,function(e,t,n,r){(function(e,t,n,r){function T(e){while(e&&typeof e.originalEvent!="undefined")e=e.originalEvent;return e}function N(t,n){var i=t.type,s,o,a,l,c,h,p,d,v;t=e.Event(t),t.type=n,s=t.originalEvent,o=e.event.props,i.search(/^(mouse|click)/)>-1&&(o=f);if(s)for(p=o.length,l;p;)l=o[--p],t[l]=s[l];i.search(/mouse(down|up)|click/)>-1&&!t.which&&(t.which=1);if(i.search(/^touch/)!==-1){a=T(s),i=a.touches,c=a.changedTouches,h=i&&i.length?i[0]:c&&c.length?c[0]:r;if(h)for(d=0,v=u.length;d<v;d++)l=u[d],t[l]=h[l]}return t}function C(t){var n={},r,s;while(t){r=e.data(t,i);for(s in r)r[s]&&(n[s]=n.hasVirtualBinding=!0);t=t.parentNode}return n}function k(t,n){var r;while(t){r=e.data(t,i);if(r&&(!n||r[n]))return t;t=t.parentNode}return null}function L(){g=!1}function A(){g=!0}function O(){E=0,v.length=0,m=!1,A()}function M(){L()}function _(){D(),c=setTimeout(function(){c=0,O()},e.vmouse.resetTimerDuration)}function D(){c&&(clearTimeout(c),c=0)}function P(t,n,r){var i;if(r&&r[t]||!r&&k(n.target,t))i=N(n,t),e(n.target).trigger(i);return i}function H(t){var n=e.data(t.target,s),r;!m&&(!E||E!==n)&&(r=P("v"+t.type,t),r&&(r.isDefaultPrevented()&&t.preventDefault(),r.isPropagationStopped()&&t.stopPropagation(),r.isImmediatePropagationStopped()&&t.stopImmediatePropagation()))}function B(t){var n=T(t).touches,r,i,o;n&&n.length===1&&(r=t.target,i=C(r),i.hasVirtualBinding&&(E=w++,e.data(r,s,E),D(),M(),d=!1,o=T(t).touches[0],h=o.pageX,p=o.pageY,P("vmouseover",t,i),P("vmousedown",t,i)))}function j(e){if(g)return;d||P("vmousecancel",e,C(e.target)),d=!0,_()}function F(t){if(g)return;var n=T(t).touches[0],r=d,i=e.vmouse.moveDistanceThreshold,s=C(t.target);d=d||Math.abs(n.pageX-h)>i||Math.abs(n.pageY-p)>i,d&&!r&&P("vmousecancel",t,s),P("vmousemove",t,s),_()}function I(e){if(g)return;A();var t=C(e.target),n,r;P("vmouseup",e,t),d||(n=P("vclick",e,t),n&&n.isDefaultPrevented()&&(r=T(e).changedTouches[0],v.push({touchID:E,x:r.clientX,y:r.clientY}),m=!0)),P("vmouseout",e,t),d=!1,_()}function q(t){var n=e.data(t,i),r;if(n)for(r in n)if(n[r])return!0;return!1}function R(){}function U(t){var n=t.substr(1);return{setup:function(){q(this)||e.data(this,i,{});var r=e.data(this,i);r[t]=!0,l[t]=(l[t]||0)+1,l[t]===1&&b.bind(n,H),e(this).bind(n,R),y&&(l.touchstart=(l.touchstart||0)+1,l.touchstart===1&&b.bind("touchstart",B).bind("touchend",I).bind("touchmove",F).bind("scroll",j))},teardown:function(){--l[t],l[t]||b.unbind(n,H),y&&(--l.touchstart,l.touchstart||b.unbind("touchstart",B).unbind("touchmove",F).unbind("touchend",I).unbind("scroll",j));var r=e(this),s=e.data(this,i);s&&(s[t]=!1),r.unbind(n,R),q(this)||r.removeData(i)}}}var i="virtualMouseBindings",s="virtualTouchID",o="vmouseover vmousedown vmousemove vmouseup vclick vmouseout vmousecancel".split(" "),u="clientX clientY pageX pageY screenX screenY".split(" "),a=e.event.mouseHooks?e.event.mouseHooks.props:[],f=e.event.props.concat(a),l={},c=0,h=0,p=0,d=!1,v=[],m=!1,g=!1,y="addEventListener"in n,b=e(n),w=1,E=0,S,x;e.vmouse={moveDistanceThreshold:10,clickDistanceThreshold:10,resetTimerDuration:1500};for(x=0;x<o.length;x++)e.event.special[o[x]]=U(o[x]);y&&n.addEventListener("click",function(t){var n=v.length,r=t.target,i,o,u,a,f,l;if(n){i=t.clientX,o=t.clientY,S=e.vmouse.clickDistanceThreshold,u=r;while(u){for(a=0;a<n;a++){f=v[a],l=0;if(u===r&&Math.abs(f.x-i)<S&&Math.abs(f.y-o)<S||e.data(u,s)===f.touchID){t.preventDefault(),t.stopPropagation();return}}u=u.parentNode}}},!0)})(e,t,n),function(e){e.mobile={}}(e),function(e,t){var r={touch:"ontouchend"in n};e.mobile.support=e.mobile.support||{},e.extend(e.support,r),e.extend(e.mobile.support,r)}(e),function(e,t,r){function l(t,n,i,s){var o=i.type;i.type=n,s?e.event.trigger(i,r,t):e.event.dispatch.call(t,i),i.type=o}var i=e(n),s=e.mobile.support.touch,o="touchmove scroll",u=s?"touchstart":"mousedown",a=s?"touchend":"mouseup",f=s?"touchmove":"mousemove";e.each("touchstart touchmove touchend tap taphold swipe swipeleft swiperight scrollstart scrollstop".split(" "),function(t,n){e.fn[n]=function(e){return e?this.bind(n,e):this.trigger(n)},e.attrFn&&(e.attrFn[n]=!0)}),e.event.special.scrollstart={enabled:!0,setup:function(){function s(e,n){r=n,l(t,r?"scrollstart":"scrollstop",e)}var t=this,n=e(t),r,i;n.bind(o,function(t){if(!e.event.special.scrollstart.enabled)return;r||s(t,!0),clearTimeout(i),i=setTimeout(function(){s(t,!1)},50)})},teardown:function(){e(this).unbind(o)}},e.event.special.tap={tapholdThreshold:750,emitTapOnTaphold:!0,setup:function(){var t=this,n=e(t),r=!1;n.bind("vmousedown",function(s){function a(){clearTimeout(u)}function f(){a(),n.unbind("vclick",c).unbind("vmouseup",a),i.unbind("vmousecancel",f)}function c(e){f(),!r&&o===e.target?l(t,"tap",e):r&&e.preventDefault()}r=!1;if(s.which&&s.which!==1)return!1;var o=s.target,u;n.bind("vmouseup",a).bind("vclick",c),i.bind("vmousecancel",f),u=setTimeout(function(){e.event.special.tap.emitTapOnTaphold||(r=!0),l(t,"taphold",e.Event("taphold",{target:o}))},e.event.special.tap.tapholdThreshold)})},teardown:function(){e(this).unbind("vmousedown").unbind("vclick").unbind("vmouseup"),i.unbind("vmousecancel")}},e.event.special.swipe={scrollSupressionThreshold:30,durationThreshold:1e3,horizontalDistanceThreshold:30,verticalDistanceThreshold:30,getLocation:function(e){var n=t.pageXOffset,r=t.pageYOffset,i=e.clientX,s=e.clientY;if(e.pageY===0&&Math.floor(s)>Math.floor(e.pageY)||e.pageX===0&&Math.floor(i)>Math.floor(e.pageX))i-=n,s-=r;else if(s<e.pageY-r||i<e.pageX-n)i=e.pageX-n,s=e.pageY-r;return{x:i,y:s}},start:function(t){var n=t.originalEvent.touches?t.originalEvent.touches[0]:t,r=e.event.special.swipe.getLocation(n);return{time:(new Date).getTime(),coords:[r.x,r.y],origin:e(t.target)}},stop:function(t){var n=t.originalEvent.touches?t.originalEvent.touches[0]:t,r=e.event.special.swipe.getLocation(n);return{time:(new Date).getTime(),coords:[r.x,r.y]}},handleSwipe:function(t,n,r,i){if(n.time-t.time<e.event.special.swipe.durationThreshold&&Math.abs(t.coords[0]-n.coords[0])>e.event.special.swipe.horizontalDistanceThreshold&&Math.abs(t.coords[1]-n.coords[1])<e.event.special.swipe.verticalDistanceThreshold){var s=t.coords[0]>n.coords[0]?"swipeleft":"swiperight";return l(r,"swipe",e.Event("swipe",{target:i,swipestart:t,swipestop:n}),!0),l(r,s,e.Event(s,{target:i,swipestart:t,swipestop:n}),!0),!0}return!1},eventInProgress:!1,setup:function(){var t,n=this,r=e(n),s={};t=e.data(this,"mobile-events"),t||(t={length:0},e.data(this,"mobile-events",t)),t.length++,t.swipe=s,s.start=function(t){if(e.event.special.swipe.eventInProgress)return;e.event.special.swipe.eventInProgress=!0;var r,o=e.event.special.swipe.start(t),u=t.target,l=!1;s.move=function(t){if(!o||t.isDefaultPrevented())return;r=e.event.special.swipe.stop(t),l||(l=e.event.special.swipe.handleSwipe(o,r,n,u),l&&(e.event.special.swipe.eventInProgress=!1)),Math.abs(o.coords[0]-r.coords[0])>e.event.special.swipe.scrollSupressionThreshold&&t.preventDefault()},s.stop=function(){l=!0,e.event.special.swipe.eventInProgress=!1,i.off(f,s.move),s.move=null},i.on(f,s.move).one(a,s.stop)},r.on(u,s.start)},teardown:function(){var t,n;t=e.data(this,"mobile-events"),t&&(n=t.swipe,delete t.swipe,t.length--,t.length===0&&e.removeData(this,"mobile-events")),n&&(n.start&&e(this).off(u,n.start),n.move&&i.off(f,n.move),n.stop&&i.off(a,n.stop))}},e.each({scrollstop:"scrollstart",taphold:"tap",swipeleft:"swipe.left",swiperight:"swipe.right"},function(t,n){e.event.special[t]={setup:function(){e(this).bind(n,e.noop)},teardown:function(){e(this).unbind(n)}}})}(e,this)});

/*!
 * enquire.js v2.1.0 - Awesome Media Queries in JavaScript
 * Copyright (c) 2013 Nick Williams - http://wicky.nillia.ms/enquire.js
 * License: MIT (http://www.opensource.org/licenses/mit-license.php)
 */
(function(t,i,n){var e=i.matchMedia;"undefined"!=typeof module&&module.exports?module.exports=n(e):"function"==typeof define&&define.amd?define(function(){return i[t]=n(e)}):i[t]=n(e)})("enquire",this,function(t){"use strict";function i(t,i){var n,e=0,s=t.length;for(e;s>e&&(n=i(t[e],e),n!==!1);e++);}function n(t){return"[object Array]"===Object.prototype.toString.apply(t)}function e(t){return"function"==typeof t}function s(t){this.options=t,!t.deferSetup&&this.setup()}function o(i,n){this.query=i,this.isUnconditional=n,this.handlers=[],this.mql=t(i);var e=this;this.listener=function(t){e.mql=t,e.assess()},this.mql.addListener(this.listener)}function r(){if(!t)throw Error("matchMedia not present, legacy browsers require a polyfill");this.queries={},this.browserIsIncapable=!t("only all").matches}return s.prototype={setup:function(){this.options.setup&&this.options.setup(),this.initialised=!0},on:function(){!this.initialised&&this.setup(),this.options.match&&this.options.match()},off:function(){this.options.unmatch&&this.options.unmatch()},destroy:function(){this.options.destroy?this.options.destroy():this.off()},equals:function(t){return this.options===t||this.options.match===t}},o.prototype={addHandler:function(t){var i=new s(t);this.handlers.push(i),this.matches()&&i.on()},removeHandler:function(t){var n=this.handlers;i(n,function(i,e){return i.equals(t)?(i.destroy(),!n.splice(e,1)):void 0})},matches:function(){return this.mql.matches||this.isUnconditional},clear:function(){i(this.handlers,function(t){t.destroy()}),this.mql.removeListener(this.listener),this.handlers.length=0},assess:function(){var t=this.matches()?"on":"off";i(this.handlers,function(i){i[t]()})}},r.prototype={register:function(t,s,r){var h=this.queries,u=r&&this.browserIsIncapable;return h[t]||(h[t]=new o(t,u)),e(s)&&(s={match:s}),n(s)||(s=[s]),i(s,function(i){h[t].addHandler(i)}),this},unregister:function(t,i){var n=this.queries[t];return n&&(i?n.removeHandler(i):(n.clear(),delete this.queries[t])),this}},new r});

/*
 * jQuery Textarea Characters Counter Plugin v 2.0
 * Examples and documentation at: http://roy-jin.appspot.com/jsp/textareaCounter.jsp
 * Copyright (c) 2010 Roy Jin
 * Version: 2.0 (11-JUN-2010)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 * Requires: jQuery v1.4.2 or later
 */
(function(d){d.fn.textareaCount=function(b,q){function f(){g.html(t());"undefined"!=typeof q&&q.call(this,{input:e,max:h,left:m,words:k});return!0}function t(){var a=c.val(),n=a.length;if(0<b.maxCharacterSize){n>=b.maxCharacterSize&&(a=a.substring(0,b.maxCharacterSize));var l=r(a),d=b.maxCharacterSize-l;p()||(d=b.maxCharacterSize);if(n>d){var f=this.scrollTop;c.val(a.substring(0,d));this.scrollTop=f}g.removeClass(b.warningStyle);d-n<=b.warningNumber&&g.addClass(b.warningStyle);e=c.val().length+l;
    p()||(e=c.val().length);k=s(c.val()).length-1;m=h-e}else l=r(a),e=c.val().length+l,p()||(e=c.val().length),k=s(c.val()).length-1;a=b.displayFormat;a=a.replace("#input",e);a=a.replace("#words",k);0<h&&(a=a.replace("#max",h),a=a.replace("#left",m));return a}function p(){return-1!=navigator.appVersion.toLowerCase().indexOf("win")?!0:!1}function r(a){for(var b=0,c=0;c<a.length;c++)"\n"==a.charAt(c)&&b++;return b}function s(a){a=(a+" ").replace(/^[^A-Za-z0-9]+/gi,"");var b=rExp=/[^A-Za-z0-9]+/gi;return a.replace(b,
    " ").split(" ")}b=d.extend({maxCharacterSize:250,originalStyle:"textarea_counter_default",warningStyle:"textarea_counter_warning",warningNumber:20,displayFormat:"<b>#left</b> "+lang.characters_left},b);var c=d(this);d("<div class='charleft'>&nbsp;</div>").insertAfter(c);var g=c.next(".charleft");g.addClass(b.originalStyle);g.css({});var e=0,h=b.maxCharacterSize,m=0,k=0;c.bind("keyup",function(a){f()}).bind("mouseover",function(a){setTimeout(function(){f()},10)}).bind("paste",function(a){setTimeout(function(){f()},
    10)})}})(jQuery);

/*
 * Generated by CoffeeScript 1.4.0
 */
(function(){var e,n=[].indexOf||function(e){for(var d=0,g=this.length;d<g;d++)if(d in this&&this[d]===e)return d;return-1};e=jQuery;e.fn.validateCreditCard=function(x,d){var g,f,h,q,r,t,u,k,v,l,w,p;h=[{name:"amex",pattern:/^3[47]/,valid_length:[15]},{name:"diners_club_carte_blanche",pattern:/^30[0-5]/,valid_length:[14]},{name:"diners_club_international",pattern:/^36/,valid_length:[14]},{name:"jcb",pattern:/^35(2[89]|[3-8][0-9])/,valid_length:[16]},{name:"laser",pattern:/^(6304|670[69]|6771)/,valid_length:[16,17,18,19]},{name:"visa_electron",pattern:/^(4026|417500|4508|4844|491(3|7))/,valid_length:[16]},{name:"visa",pattern:/^4/,valid_length:[16]},{name:"mastercard",pattern:/^5[1-5]/,valid_length:[16]},{name:"maestro",pattern:/^(5018|5020|5038|6304|6759|676[1-3])/,valid_length:[12,13,14,15,16,17,18,19]},{name:"discover",pattern:/^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)/,valid_length:[16]}];null==d&&(d={});null==d.accept&&(d.accept=function(){var a,c,b;b=[];a=0;for(c=h.length;a<c;a++)g=h[a],b.push(g.name);return b}());p=d.accept;l=0;for(w=p.length;l<w;l++)if(f=p[l],0>n.call(function(){var a,c,b;b=[];a=0;for(c=h.length;a<c;a++)g=h[a],b.push(g.name);return b}(),f))throw"Credit card type '"+f+"' is not supported";q=function(a){var c,b,e,m;m=[];c=0;for(b=h.length;c<b;c++)g=h[c],(e=g.name,0<=n.call(d.accept,e))&&m.push(g);c=0;for(b=m.length;c<b;c++)if(f=m[c],a.match(f.pattern))return f;return null};t=function(a){var c,b,d,f,e;b=0;e=a.split("").reverse();c=d=0;for(f=e.length;d<f;c=++d)a=e[c],a=+a,c%2?(a*=2,b=10>a?b+a:b+(a-9)):b+=a;return 0===b%10};r=function(a,c){var b;return b=a.length,0<=n.call(c.valid_length,b)};v=function(a){var c,b;f=q(a);c=b=!1;null!=f&&(b=t(a),c=r(a,f));return x({card_type:f,luhn_valid:b,length_valid:c})};k=function(){var a;a=u(e(this).val());return v(a)};u=function(a){return a.replace(/[ -]/g,"")};this.bind("input",function(){e(this).unbind("keyup");return k.call(this)});this.bind("keyup",function(){return k.call(this)});0!==this.length&&k.call(this);return this}}).call(this);

/*
 * jQuery Templates Plugin 1.0.0pre
 * http://github.com/jquery/jquery-tmpl
 * Requires jQuery 1.4.2
 *
 * Copyright 2011, Software Freedom Conservancy, Inc.
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 */
(function(a){var r=a.fn.domManip,d="_tmplitem",q=/^[^<]*(<[\w\W]+>)[^>]*$|\{\{\! /,b={},f={},e,p={key:0,data:{}},i=0,c=0,l=[];function g(g,d,h,e){var c={data:e||(e===0||e===false)?e:d?d.data:{},_wrap:d?d._wrap:null,tmpl:null,parent:d||null,nodes:[],calls:u,nest:w,wrap:x,html:v,update:t};g&&a.extend(c,g,{nodes:[],parent:d});if(h){c.tmpl=h;c._ctnt=c._ctnt||c.tmpl(a,c);c.key=++i;(l.length?f:b)[i]=c}return c}a.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(f,d){a.fn[f]=function(n){var g=[],i=a(n),k,h,m,l,j=this.length===1&&this[0].parentNode;e=b||{};if(j&&j.nodeType===11&&j.childNodes.length===1&&i.length===1){i[d](this[0]);g=this}else{for(h=0,m=i.length;h<m;h++){c=h;k=(h>0?this.clone(true):this).get();a(i[h])[d](k);g=g.concat(k)}c=0;g=this.pushStack(g,f,i.selector)}l=e;e=null;a.tmpl.complete(l);return g}});a.fn.extend({tmpl:function(d,c,b){return a.tmpl(this[0],d,c,b)},tmplItem:function(){return a.tmplItem(this[0])},template:function(b){return a.template(b,this[0])},domManip:function(d,m,k){if(d[0]&&a.isArray(d[0])){var g=a.makeArray(arguments),h=d[0],j=h.length,i=0,f;while(i<j&&!(f=a.data(h[i++],"tmplItem")));if(f&&c)g[2]=function(b){a.tmpl.afterManip(this,b,k)};r.apply(this,g)}else r.apply(this,arguments);c=0;!e&&a.tmpl.complete(b);return this}});a.extend({tmpl:function(d,h,e,c){var i,k=!c;if(k){c=p;d=a.template[d]||a.template(null,d);f={}}else if(!d){d=c.tmpl;b[c.key]=c;c.nodes=[];c.wrapped&&n(c,c.wrapped);return a(j(c,null,c.tmpl(a,c)))}if(!d)return[];if(typeof h==="function")h=h.call(c||{});e&&e.wrapped&&n(e,e.wrapped);i=a.isArray(h)?a.map(h,function(a){return a?g(e,c,d,a):null}):[g(e,c,d,h)];return k?a(j(c,null,i)):i},tmplItem:function(b){var c;if(b instanceof a)b=b[0];while(b&&b.nodeType===1&&!(c=a.data(b,"tmplItem"))&&(b=b.parentNode));return c||p},template:function(c,b){if(b){if(typeof b==="string")b=o(b);else if(b instanceof a)b=b[0]||{};if(b.nodeType)b=a.data(b,"tmpl")||a.data(b,"tmpl",o(b.innerHTML));return typeof c==="string"?(a.template[c]=b):b}return c?typeof c!=="string"?a.template(null,c):a.template[c]||a.template(null,q.test(c)?c:a(c)):null},encode:function(a){return(""+a).split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;")}});a.extend(a.tmpl,{tag:{tmpl:{_default:{$2:"null"},open:"if($notnull_1){__=__.concat($item.nest($1,$2));}"},wrap:{_default:{$2:"null"},open:"$item.calls(__,$1,$2);__=[];",close:"call=$item.calls();__=call._.concat($item.wrap(call,__));"},each:{_default:{$2:"$index, $value"},open:"if($notnull_1){$.each($1a,function($2){with(this){",close:"}});}"},"if":{open:"if(($notnull_1) && $1a){",close:"}"},"else":{_default:{$1:"true"},open:"}else if(($notnull_1) && $1a){"},html:{open:"if($notnull_1){__.push($1a);}"},"=":{_default:{$1:"$data"},open:"if($notnull_1){__.push($.encode($1a));}"},"!":{open:""}},complete:function(){b={}},afterManip:function(f,b,d){var e=b.nodeType===11?a.makeArray(b.childNodes):b.nodeType===1?[b]:[];d.call(f,b);m(e);c++}});function j(e,g,f){var b,c=f?a.map(f,function(a){return typeof a==="string"?e.key?a.replace(/(<\w+)(?=[\s>])(?![^>]*_tmplitem)([^>]*)/g,"$1 "+d+'="'+e.key+'" $2'):a:j(a,e,a._ctnt)}):e;if(g)return c;c=c.join("");c.replace(/^\s*([^<\s][^<]*)?(<[\w\W]+>)([^>]*[^>\s])?\s*$/,function(f,c,e,d){b=a(e).get();m(b);if(c)b=k(c).concat(b);if(d)b=b.concat(k(d))});return b?b:k(c)}function k(c){var b=document.createElement("div");b.innerHTML=c;return a.makeArray(b.childNodes)}function o(b){return new Function("jQuery","$item","var $=jQuery,call,__=[],$data=$item.data;with($data){__.push('"+a.trim(b).replace(/([\\'])/g,"\\$1").replace(/[\r\t\n]/g," ").replace(/\$\{([^\}]*)\}/g,"{{= $1}}").replace(/\{\{(\/?)(\w+|.)(?:\(((?:[^\}]|\}(?!\}))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\}]|\}(?!\}))*?)\))?\s*\}\}/g,function(m,l,k,g,b,c,d){var j=a.tmpl.tag[k],i,e,f;if(!j)throw"Unknown template tag: "+k;i=j._default||[];if(c&&!/\w$/.test(b)){b+=c;c=""}if(b){b=h(b);d=d?","+h(d)+")":c?")":"";e=c?b.indexOf(".")>-1?b+h(c):"("+b+").call($item"+d:b;f=c?e:"(typeof("+b+")==='function'?("+b+").call($item):("+b+"))"}else f=e=i.$1||"null";g=h(g);return"');"+j[l?"close":"open"].split("$notnull_1").join(b?"typeof("+b+")!=='undefined' && ("+b+")!=null":"true").split("$1a").join(f).split("$1").join(e).split("$2").join(g||i.$2||"")+"__.push('"})+"');}return __;")}function n(c,b){c._wrap=j(c,true,a.isArray(b)?b:[q.test(b)?b:a(b).html()]).join("")}function h(a){return a?a.replace(/\\'/g,"'").replace(/\\\\/g,"\\"):null}function s(b){var a=document.createElement("div");a.appendChild(b.cloneNode(true));return a.innerHTML}function m(o){var n="_"+c,k,j,l={},e,p,h;for(e=0,p=o.length;e<p;e++){if((k=o[e]).nodeType!==1)continue;j=k.getElementsByTagName("*");for(h=j.length-1;h>=0;h--)m(j[h]);m(k)}function m(j){var p,h=j,k,e,m;if(m=j.getAttribute(d)){while(h.parentNode&&(h=h.parentNode).nodeType===1&&!(p=h.getAttribute(d)));if(p!==m){h=h.parentNode?h.nodeType===11?0:h.getAttribute(d)||0:0;if(!(e=b[m])){e=f[m];e=g(e,b[h]||f[h]);e.key=++i;b[i]=e}c&&o(m)}j.removeAttribute(d)}else if(c&&(e=a.data(j,"tmplItem"))){o(e.key);b[e.key]=e;h=a.data(j.parentNode,"tmplItem");h=h?h.key:0}if(e){k=e;while(k&&k.key!=h){k.nodes.push(j);k=k.parent}delete e._ctnt;delete e._wrap;a.data(j,"tmplItem",e)}function o(a){a=a+n;e=l[a]=l[a]||g(e,b[e.parent.key+n]||e.parent)}}}function u(a,d,c,b){if(!a)return l.pop();l.push({_:a,tmpl:d,item:this,data:c,options:b})}function w(d,c,b){return a.tmpl(a.template(d),c,b,this)}function x(b,d){var c=b.options||{};c.wrapped=d;return a.tmpl(a.template(b.tmpl),b.data,c,b.item)}function v(d,c){var b=this._wrap;return a.map(a(a.isArray(b)?b.join(""):b).filter(d||"*"),function(a){return c?a.innerText||a.textContent:a.outerHTML||s(a)})}function t(){var b=this.nodes;a.tmpl(null,null,null,this).insertBefore(b[0]);a(b).remove()}})(jQuery);

/*
 * jquery.qtip. The jQuery tooltip plugin
 *
 * Copyright (c) 2009 Craig Thompson
 * http://craigsworks.com
 *
 * Licensed under MIT
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Launch  : February 2009
 * Version : 1.0.0-rc3
 * Released: Tuesday 12th May, 2009 - 00:00
 * Debug: jquery.qtip.debug.js
 */
(function(f){f.fn.qtip=function(B,u){var y,t,A,s,x,w,v,z;if(typeof B=="string"){if(typeof f(this).data("qtip")!=="object"){f.fn.qtip.log.error.call(self,1,f.fn.qtip.constants.NO_TOOLTIP_PRESENT,false)}if(B=="api"){return f(this).data("qtip").interfaces[f(this).data("qtip").current]}else{if(B=="interfaces"){return f(this).data("qtip").interfaces}}}else{if(!B){B={}}if(typeof B.content!=="object"||(B.content.jquery&&B.content.length>0)){B.content={text:B.content}}if(typeof B.content.title!=="object"){B.content.title={text:B.content.title}}if(typeof B.position!=="object"){B.position={corner:B.position}}if(typeof B.position.corner!=="object"){B.position.corner={target:B.position.corner,tooltip:B.position.corner}}if(typeof B.show!=="object"){B.show={when:B.show}}if(typeof B.show.when!=="object"){B.show.when={event:B.show.when}}if(typeof B.show.effect!=="object"){B.show.effect={type:B.show.effect}}if(typeof B.hide!=="object"){B.hide={when:B.hide}}if(typeof B.hide.when!=="object"){B.hide.when={event:B.hide.when}}if(typeof B.hide.effect!=="object"){B.hide.effect={type:B.hide.effect}}if(typeof B.style!=="object"){B.style={name:B.style}}B.style=c(B.style);s=f.extend(true,{},f.fn.qtip.defaults,B);s.style=a.call({options:s},s.style);s.user=f.extend(true,{},B)}return f(this).each(function(){if(typeof B=="string"){w=B.toLowerCase();A=f(this).qtip("interfaces");if(typeof A=="object"){if(u===true&&w=="destroy"){while(A.length>0){A[A.length-1].destroy()}}else{if(u!==true){A=[f(this).qtip("api")]}for(y=0;y<A.length;y++){if(w=="destroy"){A[y].destroy()}else{if(A[y].status.rendered===true){if(w=="show"){A[y].show()}else{if(w=="hide"){A[y].hide()}else{if(w=="focus"){A[y].focus()}else{if(w=="disable"){A[y].disable(true)}else{if(w=="enable"){A[y].disable(false)}}}}}}}}}}}else{v=f.extend(true,{},s);v.hide.effect.length=s.hide.effect.length;v.show.effect.length=s.show.effect.length;if(v.position.container===false){v.position.container=f(document.body)}if(v.position.target===false){v.position.target=f(this)}if(v.show.when.target===false){v.show.when.target=f(this)}if(v.hide.when.target===false){v.hide.when.target=f(this)}t=f.fn.qtip.interfaces.length;for(y=0;y<t;y++){if(typeof f.fn.qtip.interfaces[y]=="undefined"){t=y;break}}x=new d(f(this),v,t);f.fn.qtip.interfaces[t]=x;if(typeof f(this).data("qtip")=="object"){if(typeof f(this).attr("qtip")==="undefined"){f(this).data("qtip").current=f(this).data("qtip").interfaces.length}f(this).data("qtip").interfaces.push(x)}else{f(this).data("qtip",{current:0,interfaces:[x]})}if(v.content.prerender===false&&v.show.when.event!==false&&v.show.ready!==true){v.show.when.target.bind(v.show.when.event+".qtip-"+t+"-create",{qtip:t},function(C){z=f.fn.qtip.interfaces[C.data.qtip];z.options.show.when.target.unbind(z.options.show.when.event+".qtip-"+C.data.qtip+"-create");z.cache.mouse={x:C.pageX,y:C.pageY};p.call(z);z.options.show.when.target.trigger(z.options.show.when.event)})}else{x.cache.mouse={x:v.show.when.target.offset().left,y:v.show.when.target.offset().top};p.call(x)}}})};function d(u,t,v){var s=this;s.id=v;s.options=t;s.status={animated:false,rendered:false,disabled:false,focused:false};s.elements={target:u.addClass(s.options.style.classes.target),tooltip:null,wrapper:null,content:null,contentWrapper:null,title:null,button:null,tip:null,bgiframe:null};s.cache={mouse:{},position:{},toggle:0};s.timers={};f.extend(s,s.options.api,{show:function(y){var x,z;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"show")}if(s.elements.tooltip.css("display")!=="none"){return s}s.elements.tooltip.stop(true,false);x=s.beforeShow.call(s,y);if(x===false){return s}function w(){if(s.options.position.type!=="static"){s.focus()}s.onShow.call(s,y);if(f.browser.msie){s.elements.tooltip.get(0).style.removeAttribute("filter")}}s.cache.toggle=1;if(s.options.position.type!=="static"){s.updatePosition(y,(s.options.show.effect.length>0))}if(typeof s.options.show.solo=="object"){z=f(s.options.show.solo)}else{if(s.options.show.solo===true){z=f("div.qtip").not(s.elements.tooltip)}}if(z){z.each(function(){if(f(this).qtip("api").status.rendered===true){f(this).qtip("api").hide()}})}if(typeof s.options.show.effect.type=="function"){s.options.show.effect.type.call(s.elements.tooltip,s.options.show.effect.length);s.elements.tooltip.queue(function(){w();f(this).dequeue()})}else{switch(s.options.show.effect.type.toLowerCase()){case"fade":s.elements.tooltip.fadeIn(s.options.show.effect.length,w);break;case"slide":s.elements.tooltip.slideDown(s.options.show.effect.length,function(){w();if(s.options.position.type!=="static"){s.updatePosition(y,true)}});break;case"grow":s.elements.tooltip.show(s.options.show.effect.length,w);break;default:s.elements.tooltip.show(null,w);break}s.elements.tooltip.addClass(s.options.style.classes.active)}return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_SHOWN,"show")},hide:function(y){var x;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"hide")}else{if(s.elements.tooltip.css("display")==="none"){return s}}clearTimeout(s.timers.show);s.elements.tooltip.stop(true,false);x=s.beforeHide.call(s,y);if(x===false){return s}function w(){s.onHide.call(s,y)}s.cache.toggle=0;if(typeof s.options.hide.effect.type=="function"){s.options.hide.effect.type.call(s.elements.tooltip,s.options.hide.effect.length);s.elements.tooltip.queue(function(){w();f(this).dequeue()})}else{switch(s.options.hide.effect.type.toLowerCase()){case"fade":s.elements.tooltip.fadeOut(s.options.hide.effect.length,w);break;case"slide":s.elements.tooltip.slideUp(s.options.hide.effect.length,w);break;case"grow":s.elements.tooltip.hide(s.options.hide.effect.length,w);break;default:s.elements.tooltip.hide(null,w);break}s.elements.tooltip.removeClass(s.options.style.classes.active)}return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_HIDDEN,"hide")},updatePosition:function(w,x){var C,G,L,J,H,E,y,I,B,D,K,A,F,z;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"updatePosition")}else{if(s.options.position.type=="static"){return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.CANNOT_POSITION_STATIC,"updatePosition")}}G={position:{left:0,top:0},dimensions:{height:0,width:0},corner:s.options.position.corner.target};L={position:s.getPosition(),dimensions:s.getDimensions(),corner:s.options.position.corner.tooltip};if(s.options.position.target!=="mouse"){if(s.options.position.target.get(0).nodeName.toLowerCase()=="area"){J=s.options.position.target.attr("coords").split(",");for(C=0;C<J.length;C++){J[C]=parseInt(J[C])}H=s.options.position.target.parent("map").attr("name");E=f('img[usemap="#'+H+'"]:first').offset();G.position={left:Math.floor(E.left+J[0]),top:Math.floor(E.top+J[1])};switch(s.options.position.target.attr("shape").toLowerCase()){case"rect":G.dimensions={width:Math.ceil(Math.abs(J[2]-J[0])),height:Math.ceil(Math.abs(J[3]-J[1]))};break;case"circle":G.dimensions={width:J[2]+1,height:J[2]+1};break;case"poly":G.dimensions={width:J[0],height:J[1]};for(C=0;C<J.length;C++){if(C%2==0){if(J[C]>G.dimensions.width){G.dimensions.width=J[C]}if(J[C]<J[0]){G.position.left=Math.floor(E.left+J[C])}}else{if(J[C]>G.dimensions.height){G.dimensions.height=J[C]}if(J[C]<J[1]){G.position.top=Math.floor(E.top+J[C])}}}G.dimensions.width=G.dimensions.width-(G.position.left-E.left);G.dimensions.height=G.dimensions.height-(G.position.top-E.top);break;default:return f.fn.qtip.log.error.call(s,4,f.fn.qtip.constants.INVALID_AREA_SHAPE,"updatePosition");break}G.dimensions.width-=2;G.dimensions.height-=2}else{if(s.options.position.target.add(document.body).length===1){G.position={left:f(document).scrollLeft(),top:f(document).scrollTop()};G.dimensions={height:f(window).height(),width:f(window).width()}}else{if(typeof s.options.position.target.attr("qtip")!=="undefined"){G.position=s.options.position.target.qtip("api").cache.position}else{G.position=s.options.position.target.offset()}G.dimensions={height:s.options.position.target.outerHeight(),width:s.options.position.target.outerWidth()}}}y=f.extend({},G.position);if(G.corner.search(/right/i)!==-1){y.left+=G.dimensions.width}if(G.corner.search(/bottom/i)!==-1){y.top+=G.dimensions.height}if(G.corner.search(/((top|bottom)Middle)|center/)!==-1){y.left+=(G.dimensions.width/2)}if(G.corner.search(/((left|right)Middle)|center/)!==-1){y.top+=(G.dimensions.height/2)}}else{G.position=y={left:s.cache.mouse.x,top:s.cache.mouse.y};G.dimensions={height:1,width:1}}if(L.corner.search(/right/i)!==-1){y.left-=L.dimensions.width}if(L.corner.search(/bottom/i)!==-1){y.top-=L.dimensions.height}if(L.corner.search(/((top|bottom)Middle)|center/)!==-1){y.left-=(L.dimensions.width/2)}if(L.corner.search(/((left|right)Middle)|center/)!==-1){y.top-=(L.dimensions.height/2)}I=(f.browser.msie)?1:0;B=(f.browser.msie&&parseInt(f.browser.version.charAt(0))===6)?1:0;if(s.options.style.border.radius>0){if(L.corner.search(/Left/)!==-1){y.left-=s.options.style.border.radius}else{if(L.corner.search(/Right/)!==-1){y.left+=s.options.style.border.radius}}if(L.corner.search(/Top/)!==-1){y.top-=s.options.style.border.radius}else{if(L.corner.search(/Bottom/)!==-1){y.top+=s.options.style.border.radius}}}if(I){if(L.corner.search(/top/)!==-1){y.top-=I}else{if(L.corner.search(/bottom/)!==-1){y.top+=I}}if(L.corner.search(/left/)!==-1){y.left-=I}else{if(L.corner.search(/right/)!==-1){y.left+=I}}if(L.corner.search(/leftMiddle|rightMiddle/)!==-1){y.top-=1}}if(s.options.position.adjust.screen===true){y=o.call(s,y,G,L)}if(s.options.position.target==="mouse"&&s.options.position.adjust.mouse===true){if(s.options.position.adjust.screen===true&&s.elements.tip){K=s.elements.tip.attr("rel")}else{K=s.options.position.corner.tooltip}y.left+=(K.search(/right/i)!==-1)?-6:6;y.top+=(K.search(/bottom/i)!==-1)?-6:6}if(!s.elements.bgiframe&&f.browser.msie&&parseInt(f.browser.version.charAt(0))==6){f("select, object").each(function(){A=f(this).offset();A.bottom=A.top+f(this).height();A.right=A.left+f(this).width();if(y.top+L.dimensions.height>=A.top&&y.left+L.dimensions.width>=A.left){k.call(s)}})}y.left+=s.options.position.adjust.x;y.top+=s.options.position.adjust.y;F=s.getPosition();if(y.left!=F.left||y.top!=F.top){z=s.beforePositionUpdate.call(s,w);if(z===false){return s}s.cache.position=y;if(x===true){s.status.animated=true;s.elements.tooltip.animate(y,200,"swing",function(){s.status.animated=false})}else{s.elements.tooltip.css(y)}s.onPositionUpdate.call(s,w);if(typeof w!=="undefined"&&w.type&&w.type!=="mousemove"){f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_POSITION_UPDATED,"updatePosition")}}return s},updateWidth:function(w){var x;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"updateWidth")}else{if(w&&typeof w!=="number"){return f.fn.qtip.log.error.call(s,2,"newWidth must be of type number","updateWidth")}}x=s.elements.contentWrapper.siblings().add(s.elements.tip).add(s.elements.button);if(!w){if(typeof s.options.style.width.value=="number"){w=s.options.style.width.value}else{s.elements.tooltip.css({width:"auto"});x.hide();if(f.browser.msie){s.elements.wrapper.add(s.elements.contentWrapper.children()).css({zoom:"normal"})}w=s.getDimensions().width+1;if(!s.options.style.width.value){if(w>s.options.style.width.max){w=s.options.style.width.max}if(w<s.options.style.width.min){w=s.options.style.width.min}}}}if(w%2!==0){w-=1}s.elements.tooltip.width(w);x.show();if(s.options.style.border.radius){s.elements.tooltip.find(".qtip-betweenCorners").each(function(y){f(this).width(w-(s.options.style.border.radius*2))})}if(f.browser.msie){s.elements.wrapper.add(s.elements.contentWrapper.children()).css({zoom:"1"});s.elements.wrapper.width(w);if(s.elements.bgiframe){s.elements.bgiframe.width(w).height(s.getDimensions.height)}}return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_WIDTH_UPDATED,"updateWidth")},updateStyle:function(w){var z,A,x,y,B;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"updateStyle")}else{if(typeof w!=="string"||!f.fn.qtip.styles[w]){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.STYLE_NOT_DEFINED,"updateStyle")}}s.options.style=a.call(s,f.fn.qtip.styles[w],s.options.user.style);s.elements.content.css(q(s.options.style));if(s.options.content.title.text!==false){s.elements.title.css(q(s.options.style.title,true))}s.elements.contentWrapper.css({borderColor:s.options.style.border.color});if(s.options.style.tip.corner!==false){if(f("<canvas>").get(0).getContext){z=s.elements.tooltip.find(".qtip-tip canvas:first");x=z.get(0).getContext("2d");x.clearRect(0,0,300,300);y=z.parent("div[rel]:first").attr("rel");B=b(y,s.options.style.tip.size.width,s.options.style.tip.size.height);h.call(s,z,B,s.options.style.tip.color||s.options.style.border.color)}else{if(f.browser.msie){z=s.elements.tooltip.find('.qtip-tip [nodeName="shape"]');z.attr("fillcolor",s.options.style.tip.color||s.options.style.border.color)}}}if(s.options.style.border.radius>0){s.elements.tooltip.find(".qtip-betweenCorners").css({backgroundColor:s.options.style.border.color});if(f("<canvas>").get(0).getContext){A=g(s.options.style.border.radius);s.elements.tooltip.find(".qtip-wrapper canvas").each(function(){x=f(this).get(0).getContext("2d");x.clearRect(0,0,300,300);y=f(this).parent("div[rel]:first").attr("rel");r.call(s,f(this),A[y],s.options.style.border.radius,s.options.style.border.color)})}else{if(f.browser.msie){s.elements.tooltip.find('.qtip-wrapper [nodeName="arc"]').each(function(){f(this).attr("fillcolor",s.options.style.border.color)})}}}return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_STYLE_UPDATED,"updateStyle")},updateContent:function(A,y){var z,x,w;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"updateContent")}else{if(!A){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.NO_CONTENT_PROVIDED,"updateContent")}}z=s.beforeContentUpdate.call(s,A);if(typeof z=="string"){A=z}else{if(z===false){return}}if(f.browser.msie){s.elements.contentWrapper.children().css({zoom:"normal"})}if(A.jquery&&A.length>0){A.clone(true).appendTo(s.elements.content).show()}else{s.elements.content.html(A)}x=s.elements.content.find("img[complete=false]");if(x.length>0){w=0;x.each(function(C){f('<img src="'+f(this).attr("src")+'" />').load(function(){if(++w==x.length){B()}})})}else{B()}function B(){s.updateWidth();if(y!==false){if(s.options.position.type!=="static"){s.updatePosition(s.elements.tooltip.is(":visible"),true)}if(s.options.style.tip.corner!==false){n.call(s)}}}s.onContentUpdate.call(s);return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_CONTENT_UPDATED,"loadContent")},loadContent:function(w,z,A){var y;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"loadContent")}y=s.beforeContentLoad.call(s);if(y===false){return s}if(A=="post"){f.post(w,z,x)}else{f.get(w,z,x)}function x(B){s.onContentLoad.call(s);f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_CONTENT_LOADED,"loadContent");s.updateContent(B)}return s},updateTitle:function(w){if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"updateTitle")}else{if(!w){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.NO_CONTENT_PROVIDED,"updateTitle")}}returned=s.beforeTitleUpdate.call(s);if(returned===false){return s}if(s.elements.button){s.elements.button=s.elements.button.clone(true)}s.elements.title.html(w);if(s.elements.button){s.elements.title.prepend(s.elements.button)}s.onTitleUpdate.call(s);return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_TITLE_UPDATED,"updateTitle")},focus:function(A){var y,x,w,z;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"focus")}else{if(s.options.position.type=="static"){return f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.CANNOT_FOCUS_STATIC,"focus")}}y=parseInt(s.elements.tooltip.css("z-index"));x=6000+f("div.qtip[qtip]").length-1;if(!s.status.focused&&y!==x){z=s.beforeFocus.call(s,A);if(z===false){return s}f("div.qtip[qtip]").not(s.elements.tooltip).each(function(){if(f(this).qtip("api").status.rendered===true){w=parseInt(f(this).css("z-index"));if(typeof w=="number"&&w>-1){f(this).css({zIndex:parseInt(f(this).css("z-index"))-1})}f(this).qtip("api").status.focused=false}});s.elements.tooltip.css({zIndex:x});s.status.focused=true;s.onFocus.call(s,A);f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_FOCUSED,"focus")}return s},disable:function(w){if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"disable")}if(w){if(!s.status.disabled){s.status.disabled=true;f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_DISABLED,"disable")}else{f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.TOOLTIP_ALREADY_DISABLED,"disable")}}else{if(s.status.disabled){s.status.disabled=false;f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_ENABLED,"disable")}else{f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.TOOLTIP_ALREADY_ENABLED,"disable")}}return s},destroy:function(){var w,x,y;x=s.beforeDestroy.call(s);if(x===false){return s}if(s.status.rendered){s.options.show.when.target.unbind("mousemove.qtip",s.updatePosition);s.options.show.when.target.unbind("mouseout.qtip",s.hide);s.options.show.when.target.unbind(s.options.show.when.event+".qtip");s.options.hide.when.target.unbind(s.options.hide.when.event+".qtip");s.elements.tooltip.unbind(s.options.hide.when.event+".qtip");s.elements.tooltip.unbind("mouseover.qtip",s.focus);s.elements.tooltip.remove()}else{s.options.show.when.target.unbind(s.options.show.when.event+".qtip-create")}if(typeof s.elements.target.data("qtip")=="object"){y=s.elements.target.data("qtip").interfaces;if(typeof y=="object"&&y.length>0){for(w=0;w<y.length-1;w++){if(y[w].id==s.id){y.splice(w,1)}}}}delete f.fn.qtip.interfaces[s.id];if(typeof y=="object"&&y.length>0){s.elements.target.data("qtip").current=y.length-1}else{s.elements.target.removeData("qtip")}s.onDestroy.call(s);f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_DESTROYED,"destroy");return s.elements.target},getPosition:function(){var w,x;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"getPosition")}w=(s.elements.tooltip.css("display")!=="none")?false:true;if(w){s.elements.tooltip.css({visiblity:"hidden"}).show()}x=s.elements.tooltip.offset();if(w){s.elements.tooltip.css({visiblity:"visible"}).hide()}return x},getDimensions:function(){var w,x;if(!s.status.rendered){return f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.TOOLTIP_NOT_RENDERED,"getDimensions")}w=(!s.elements.tooltip.is(":visible"))?true:false;if(w){s.elements.tooltip.css({visiblity:"hidden"}).show()}x={height:s.elements.tooltip.outerHeight(),width:s.elements.tooltip.outerWidth()};if(w){s.elements.tooltip.css({visiblity:"visible"}).hide()}return x}})}function p(){var s,w,u,t,v,y,x;s=this;s.beforeRender.call(s);s.status.rendered=true;s.elements.tooltip='<div qtip="'+s.id+'" class="qtip '+(s.options.style.classes.tooltip||s.options.style)+'"style="display:none; -moz-border-radius:0; -webkit-border-radius:0; border-radius:0;position:'+s.options.position.type+';">  <div class="qtip-wrapper" style="position:relative; overflow:hidden; text-align:left;">    <div class="qtip-contentWrapper" style="overflow:hidden;">       <div class="qtip-content '+s.options.style.classes.content+'"></div></div></div></div>';s.elements.tooltip=f(s.elements.tooltip);s.elements.tooltip.appendTo(s.options.position.container);s.elements.tooltip.data("qtip",{current:0,interfaces:[s]});s.elements.wrapper=s.elements.tooltip.children("div:first");s.elements.contentWrapper=s.elements.wrapper.children("div:first").css({background:s.options.style.background});s.elements.content=s.elements.contentWrapper.children("div:first").css(q(s.options.style));if(f.browser.msie){s.elements.wrapper.add(s.elements.content).css({zoom:1})}if(s.options.hide.when.event=="unfocus"){s.elements.tooltip.attr("unfocus",true)}if(typeof s.options.style.width.value=="number"){s.updateWidth()}if(f("<canvas>").get(0).getContext||f.browser.msie){if(s.options.style.border.radius>0){m.call(s)}else{s.elements.contentWrapper.css({border:s.options.style.border.width+"px solid "+s.options.style.border.color})}if(s.options.style.tip.corner!==false){e.call(s)}}else{s.elements.contentWrapper.css({border:s.options.style.border.width+"px solid "+s.options.style.border.color});s.options.style.border.radius=0;s.options.style.tip.corner=false;f.fn.qtip.log.error.call(s,2,f.fn.qtip.constants.CANVAS_VML_NOT_SUPPORTED,"render")}if((typeof s.options.content.text=="string"&&s.options.content.text.length>0)||(s.options.content.text.jquery&&s.options.content.text.length>0)){u=s.options.content.text}else{if(typeof s.elements.target.attr("title")=="string"&&s.elements.target.attr("title").length>0){u=s.elements.target.attr("title").replace("\\n","<br />");s.elements.target.attr("title","")}else{if(typeof s.elements.target.attr("alt")=="string"&&s.elements.target.attr("alt").length>0){u=s.elements.target.attr("alt").replace("\\n","<br />");s.elements.target.attr("alt","")}else{u=" ";f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.NO_VALID_CONTENT,"render")}}}if(s.options.content.title.text!==false){j.call(s)}s.updateContent(u);l.call(s);if(s.options.show.ready===true){s.show()}if(s.options.content.url!==false){t=s.options.content.url;v=s.options.content.data;y=s.options.content.method||"get";s.loadContent(t,v,y)}s.onRender.call(s);f.fn.qtip.log.error.call(s,1,f.fn.qtip.constants.EVENT_RENDERED,"render")}function m(){var F,z,t,B,x,E,u,G,D,y,w,C,A,s,v;F=this;F.elements.wrapper.find(".qtip-borderBottom, .qtip-borderTop").remove();t=F.options.style.border.width;B=F.options.style.border.radius;x=F.options.style.border.color||F.options.style.tip.color;E=g(B);u={};for(z in E){u[z]='<div rel="'+z+'" style="'+((z.search(/Left/)!==-1)?"left":"right")+":0; position:absolute; height:"+B+"px; width:"+B+'px; overflow:hidden; line-height:0.1px; font-size:1px">';if(f("<canvas>").get(0).getContext){u[z]+='<canvas height="'+B+'" width="'+B+'" style="vertical-align: top"></canvas>'}else{if(f.browser.msie){G=B*2+3;u[z]+='<v:arc stroked="false" fillcolor="'+x+'" startangle="'+E[z][0]+'" endangle="'+E[z][1]+'" style="width:'+G+"px; height:"+G+"px; margin-top:"+((z.search(/bottom/)!==-1)?-2:-1)+"px; margin-left:"+((z.search(/Right/)!==-1)?E[z][2]-3.5:-1)+'px; vertical-align:top; display:inline-block; behavior:url(#default#VML)"></v:arc>'}}u[z]+="</div>"}D=F.getDimensions().width-(Math.max(t,B)*2);y='<div class="qtip-betweenCorners" style="height:'+B+"px; width:"+D+"px; overflow:hidden; background-color:"+x+'; line-height:0.1px; font-size:1px;">';w='<div class="qtip-borderTop" dir="ltr" style="height:'+B+"px; margin-left:"+B+'px; line-height:0.1px; font-size:1px; padding:0;">'+u.topLeft+u.topRight+y;F.elements.wrapper.prepend(w);C='<div class="qtip-borderBottom" dir="ltr" style="height:'+B+"px; margin-left:"+B+'px; line-height:0.1px; font-size:1px; padding:0;">'+u.bottomLeft+u.bottomRight+y;F.elements.wrapper.append(C);if(f("<canvas>").get(0).getContext){F.elements.wrapper.find("canvas").each(function(){A=E[f(this).parent("[rel]:first").attr("rel")];r.call(F,f(this),A,B,x)})}else{if(f.browser.msie){F.elements.tooltip.append('<v:image style="behavior:url(#default#VML);"></v:image>')}}s=Math.max(B,(B+(t-B)));v=Math.max(t-B,0);F.elements.contentWrapper.css({border:"0px solid "+x,borderWidth:v+"px "+s+"px"})}function r(u,w,s,t){var v=u.get(0).getContext("2d");v.fillStyle=t;v.beginPath();v.arc(w[0],w[1],s,0,Math.PI*2,false);v.fill()}function e(v){var t,s,x,u,w;t=this;if(t.elements.tip!==null){t.elements.tip.remove()}s=t.options.style.tip.color||t.options.style.border.color;if(t.options.style.tip.corner===false){return}else{if(!v){v=t.options.style.tip.corner}}x=b(v,t.options.style.tip.size.width,t.options.style.tip.size.height);t.elements.tip='<div class="'+t.options.style.classes.tip+'" dir="ltr" rel="'+v+'" style="position:absolute; height:'+t.options.style.tip.size.height+"px; width:"+t.options.style.tip.size.width+'px; margin:0 auto; line-height:0.1px; font-size:1px;">';if(f("<canvas>").get(0).getContext){t.elements.tip+='<canvas height="'+t.options.style.tip.size.height+'" width="'+t.options.style.tip.size.width+'"></canvas>'}else{if(f.browser.msie){u=t.options.style.tip.size.width+","+t.options.style.tip.size.height;w="m"+x[0][0]+","+x[0][1];w+=" l"+x[1][0]+","+x[1][1];w+=" "+x[2][0]+","+x[2][1];w+=" xe";t.elements.tip+='<v:shape fillcolor="'+s+'" stroked="false" filled="true" path="'+w+'" coordsize="'+u+'" style="width:'+t.options.style.tip.size.width+"px; height:"+t.options.style.tip.size.height+"px; line-height:0.1px; display:inline-block; behavior:url(#default#VML); vertical-align:"+((v.search(/top/)!==-1)?"bottom":"top")+'"></v:shape>';t.elements.tip+='<v:image style="behavior:url(#default#VML);"></v:image>';t.elements.contentWrapper.css("position","relative")}}t.elements.tooltip.prepend(t.elements.tip+"</div>");t.elements.tip=t.elements.tooltip.find("."+t.options.style.classes.tip).eq(0);if(f("<canvas>").get(0).getContext){h.call(t,t.elements.tip.find("canvas:first"),x,s)}if(v.search(/top/)!==-1&&f.browser.msie&&parseInt(f.browser.version.charAt(0))===6){t.elements.tip.css({marginTop:-4})}n.call(t,v)}function h(t,v,s){var u=t.get(0).getContext("2d");u.fillStyle=s;u.beginPath();u.moveTo(v[0][0],v[0][1]);u.lineTo(v[1][0],v[1][1]);u.lineTo(v[2][0],v[2][1]);u.fill()}function n(u){var t,w,s,x,v;t=this;if(t.options.style.tip.corner===false||!t.elements.tip){return}if(!u){u=t.elements.tip.attr("rel")}w=positionAdjust=(f.browser.msie)?1:0;t.elements.tip.css(u.match(/left|right|top|bottom/)[0],0);if(u.search(/top|bottom/)!==-1){if(f.browser.msie){if(parseInt(f.browser.version.charAt(0))===6){positionAdjust=(u.search(/top/)!==-1)?-3:1}else{positionAdjust=(u.search(/top/)!==-1)?1:2}}if(u.search(/Middle/)!==-1){t.elements.tip.css({left:"50%",marginLeft:-(t.options.style.tip.size.width/2)})}else{if(u.search(/Left/)!==-1){t.elements.tip.css({left:t.options.style.border.radius-w})}else{if(u.search(/Right/)!==-1){t.elements.tip.css({right:t.options.style.border.radius+w})}}}if(u.search(/top/)!==-1){t.elements.tip.css({top:-positionAdjust})}else{t.elements.tip.css({bottom:positionAdjust})}}else{if(u.search(/left|right/)!==-1){if(f.browser.msie){positionAdjust=(parseInt(f.browser.version.charAt(0))===6)?1:((u.search(/left/)!==-1)?1:2)}if(u.search(/Middle/)!==-1){t.elements.tip.css({top:"50%",marginTop:-(t.options.style.tip.size.height/2)})}else{if(u.search(/Top/)!==-1){t.elements.tip.css({top:t.options.style.border.radius-w})}else{if(u.search(/Bottom/)!==-1){t.elements.tip.css({bottom:t.options.style.border.radius+w})}}}if(u.search(/left/)!==-1){t.elements.tip.css({left:-positionAdjust})}else{t.elements.tip.css({right:positionAdjust})}}}s="padding-"+u.match(/left|right|top|bottom/)[0];x=t.options.style.tip.size[(s.search(/left|right/)!==-1)?"width":"height"];t.elements.tooltip.css("padding",0);t.elements.tooltip.css(s,x);if(f.browser.msie&&parseInt(f.browser.version.charAt(0))==6){v=parseInt(t.elements.tip.css("margin-top"))||0;v+=parseInt(t.elements.content.css("margin-top"))||0;t.elements.tip.css({marginTop:v})}}function j(){var s=this;if(s.elements.title!==null){s.elements.title.remove()}s.elements.title=f('<div class="'+s.options.style.classes.title+'">').css(q(s.options.style.title,true)).css({zoom:(f.browser.msie)?1:0}).prependTo(s.elements.contentWrapper);if(s.options.content.title.text){s.updateTitle.call(s,s.options.content.title.text)}if(s.options.content.title.button!==false&&typeof s.options.content.title.button=="string"){s.elements.button=f('<a class="'+s.options.style.classes.button+'" style="float:right; position: relative"></a>').css(q(s.options.style.button,true)).html(s.options.content.title.button).prependTo(s.elements.title).click(function(t){if(!s.status.disabled){s.hide(t)}})}}function l(){var t,v,u,s;t=this;v=t.options.show.when.target;u=t.options.hide.when.target;if(t.options.hide.fixed){u=u.add(t.elements.tooltip)}if(t.options.hide.when.event=="inactive"){s=["click","dblclick","mousedown","mouseup","mousemove","mouseout","mouseenter","mouseleave","mouseover"];function y(z){if(t.status.disabled===true){return}clearTimeout(t.timers.inactive);t.timers.inactive=setTimeout(function(){f(s).each(function(){u.unbind(this+".qtip-inactive");t.elements.content.unbind(this+".qtip-inactive")});t.hide(z)},t.options.hide.delay)}}else{if(t.options.hide.fixed===true){t.elements.tooltip.bind("mouseover.qtip",function(){if(t.status.disabled===true){return}clearTimeout(t.timers.hide)})}}function x(z){if(t.status.disabled===true){return}if(t.options.hide.when.event=="inactive"){f(s).each(function(){u.bind(this+".qtip-inactive",y);t.elements.content.bind(this+".qtip-inactive",y)});y()}clearTimeout(t.timers.show);clearTimeout(t.timers.hide);t.timers.show=setTimeout(function(){t.show(z)},t.options.show.delay)}function w(z){if(t.status.disabled===true){return}if(t.options.hide.fixed===true&&t.options.hide.when.event.search(/mouse(out|leave)/i)!==-1&&f(z.relatedTarget).parents("div.qtip[qtip]").length>0){z.stopPropagation();z.preventDefault();clearTimeout(t.timers.hide);return false}clearTimeout(t.timers.show);clearTimeout(t.timers.hide);t.elements.tooltip.stop(true,true);t.timers.hide=setTimeout(function(){t.hide(z)},t.options.hide.delay)}if((t.options.show.when.target.add(t.options.hide.when.target).length===1&&t.options.show.when.event==t.options.hide.when.event&&t.options.hide.when.event!=="inactive")||t.options.hide.when.event=="unfocus"){t.cache.toggle=0;v.bind(t.options.show.when.event+".qtip",function(z){if(t.cache.toggle==0){x(z)}else{w(z)}})}else{v.bind(t.options.show.when.event+".qtip",x);if(t.options.hide.when.event!=="inactive"){u.bind(t.options.hide.when.event+".qtip",w)}}if(t.options.position.type.search(/(fixed|absolute)/)!==-1){t.elements.tooltip.bind("mouseover.qtip",t.focus)}if(t.options.position.target==="mouse"&&t.options.position.type!=="static"){v.bind("mousemove.qtip",function(z){t.cache.mouse={x:z.pageX,y:z.pageY};if(t.status.disabled===false&&t.options.position.adjust.mouse===true&&t.options.position.type!=="static"&&t.elements.tooltip.css("display")!=="none"){t.updatePosition(z)}})}}function o(u,v,A){var z,s,x,y,t,w;z=this;if(A.corner=="center"){return v.position}s=f.extend({},u);y={x:false,y:false};t={left:(s.left<f.fn.qtip.cache.screen.scroll.left),right:(s.left+A.dimensions.width+2>=f.fn.qtip.cache.screen.width+f.fn.qtip.cache.screen.scroll.left),top:(s.top<f.fn.qtip.cache.screen.scroll.top),bottom:(s.top+A.dimensions.height+2>=f.fn.qtip.cache.screen.height+f.fn.qtip.cache.screen.scroll.top)};x={left:(t.left&&(A.corner.search(/right/i)!=-1||(A.corner.search(/right/i)==-1&&!t.right))),right:(t.right&&(A.corner.search(/left/i)!=-1||(A.corner.search(/left/i)==-1&&!t.left))),top:(t.top&&A.corner.search(/top/i)==-1),bottom:(t.bottom&&A.corner.search(/bottom/i)==-1)};if(x.left){if(z.options.position.target!=="mouse"){s.left=v.position.left+v.dimensions.width}else{s.left=z.cache.mouse.x}y.x="Left"}else{if(x.right){if(z.options.position.target!=="mouse"){s.left=v.position.left-A.dimensions.width}else{s.left=z.cache.mouse.x-A.dimensions.width}y.x="Right"}}if(x.top){if(z.options.position.target!=="mouse"){s.top=v.position.top+v.dimensions.height}else{s.top=z.cache.mouse.y}y.y="top"}else{if(x.bottom){if(z.options.position.target!=="mouse"){s.top=v.position.top-A.dimensions.height}else{s.top=z.cache.mouse.y-A.dimensions.height}y.y="bottom"}}if(s.left<0){s.left=u.left;y.x=false}if(s.top<0){s.top=u.top;y.y=false}if(z.options.style.tip.corner!==false){s.corner=new String(A.corner);if(y.x!==false){s.corner=s.corner.replace(/Left|Right|Middle/,y.x)}if(y.y!==false){s.corner=s.corner.replace(/top|bottom/,y.y)}if(s.corner!==z.elements.tip.attr("rel")){e.call(z,s.corner)}}return s}function q(u,t){var v,s;v=f.extend(true,{},u);for(s in v){if(t===true&&s.search(/(tip|classes)/i)!==-1){delete v[s]}else{if(!t&&s.search(/(width|border|tip|title|classes|user)/i)!==-1){delete v[s]}}}return v}function c(s){if(typeof s.tip!=="object"){s.tip={corner:s.tip}}if(typeof s.tip.size!=="object"){s.tip.size={width:s.tip.size,height:s.tip.size}}if(typeof s.border!=="object"){s.border={width:s.border}}if(typeof s.width!=="object"){s.width={value:s.width}}if(typeof s.width.max=="string"){s.width.max=parseInt(s.width.max.replace(/([0-9]+)/i,"$1"))}if(typeof s.width.min=="string"){s.width.min=parseInt(s.width.min.replace(/([0-9]+)/i,"$1"))}if(typeof s.tip.size.x=="number"){s.tip.size.width=s.tip.size.x;delete s.tip.size.x}if(typeof s.tip.size.y=="number"){s.tip.size.height=s.tip.size.y;delete s.tip.size.y}return s}function a(){var s,t,u,x,v,w;s=this;u=[true,{}];for(t=0;t<arguments.length;t++){u.push(arguments[t])}x=[f.extend.apply(f,u)];while(typeof x[0].name=="string"){x.unshift(c(f.fn.qtip.styles[x[0].name]))}x.unshift(true,{classes:{tooltip:"qtip-"+(arguments[0].name||"defaults")}},f.fn.qtip.styles.defaults);v=f.extend.apply(f,x);w=(f.browser.msie)?1:0;v.tip.size.width+=w;v.tip.size.height+=w;if(v.tip.size.width%2>0){v.tip.size.width+=1}if(v.tip.size.height%2>0){v.tip.size.height+=1}if(v.tip.corner===true){v.tip.corner=(s.options.position.corner.tooltip==="center")?false:s.options.position.corner.tooltip}return v}function b(v,u,t){var s={bottomRight:[[0,0],[u,t],[u,0]],bottomLeft:[[0,0],[u,0],[0,t]],topRight:[[0,t],[u,0],[u,t]],topLeft:[[0,0],[0,t],[u,t]],topMiddle:[[0,t],[u/2,0],[u,t]],bottomMiddle:[[0,0],[u,0],[u/2,t]],rightMiddle:[[0,0],[u,t/2],[0,t]],leftMiddle:[[u,0],[u,t],[0,t/2]]};s.leftTop=s.bottomRight;s.rightTop=s.bottomLeft;s.leftBottom=s.topRight;s.rightBottom=s.topLeft;return s[v]}function g(s){var t;if(f("<canvas>").get(0).getContext){t={topLeft:[s,s],topRight:[0,s],bottomLeft:[s,0],bottomRight:[0,0]}}else{if(f.browser.msie){t={topLeft:[-90,90,0],topRight:[-90,90,-s],bottomLeft:[90,270,0],bottomRight:[90,270,-s]}}}return t}function k(){var s,t,u;s=this;u=s.getDimensions();t='<iframe class="qtip-bgiframe" frameborder="0" tabindex="-1" src="javascript:false" style="display:block; position:absolute; z-index:-1; filter:alpha(opacity=\'0\'); border: 1px solid red; height:'+u.height+"px; width:"+u.width+'px" />';s.elements.bgiframe=s.elements.wrapper.prepend(t).children(".qtip-bgiframe:first")}f(document).ready(function(){f.fn.qtip.cache={screen:{scroll:{left:f(window).scrollLeft(),top:f(window).scrollTop()},width:f(window).width(),height:f(window).height()}};var s;f(window).bind("resize scroll",function(t){clearTimeout(s);s=setTimeout(function(){if(t.type==="scroll"){f.fn.qtip.cache.screen.scroll={left:f(window).scrollLeft(),top:f(window).scrollTop()}}else{f.fn.qtip.cache.screen.width=f(window).width();f.fn.qtip.cache.screen.height=f(window).height()}for(i=0;i<f.fn.qtip.interfaces.length;i++){var u=f.fn.qtip.interfaces[i];if(u.status.rendered===true&&(u.options.position.type!=="static"||u.options.position.adjust.scroll&&t.type==="scroll"||u.options.position.adjust.resize&&t.type==="resize")){u.updatePosition(t,true)}}},100)});f(document).bind("mousedown.qtip",function(t){if(f(t.target).parents("div.qtip").length===0){f(".qtip[unfocus]").each(function(){var u=f(this).qtip("api");if(f(this).is(":visible")&&!u.status.disabled&&f(t.target).add(u.elements.target).length>1){u.hide(t)}})}})});f.fn.qtip.interfaces=[];f.fn.qtip.log={error:function(){return this}};f.fn.qtip.constants={};f.fn.qtip.defaults={content:{prerender:false,text:false,url:false,data:null,title:{text:false,button:false}},position:{target:false,corner:{target:"bottomRight",tooltip:"topLeft"},adjust:{x:0,y:0,mouse:true,screen:false,scroll:true,resize:true},type:"absolute",container:false},show:{when:{target:false,event:"mouseover"},effect:{type:"fade",length:100},delay:140,solo:false,ready:false},hide:{when:{target:false,event:"mouseout"},effect:{type:"fade",length:100},delay:0,fixed:false},api:{beforeRender:function(){},onRender:function(){},beforePositionUpdate:function(){},onPositionUpdate:function(){},beforeShow:function(){},onShow:function(){},beforeHide:function(){},onHide:function(){},beforeContentUpdate:function(){},onContentUpdate:function(){},beforeContentLoad:function(){},onContentLoad:function(){},beforeTitleUpdate:function(){},onTitleUpdate:function(){},beforeDestroy:function(){},onDestroy:function(){},beforeFocus:function(){},onFocus:function(){}}};f.fn.qtip.styles={defaults:{background:"white",color:"#111",overflow:"hidden",textAlign:"left",width:{min:0,max:250},padding:"5px 9px",border:{width:1,radius:0,color:"#d3d3d3"},tip:{corner:false,color:false,size:{width:13,height:13},opacity:1},title:{background:"#e1e1e1",fontWeight:"bold",padding:"7px 12px"},button:{cursor:"pointer"},classes:{target:"",tip:"qtip-tip",title:"qtip-title",button:"qtip-button",content:"qtip-content",active:"qtip-active"}},cream:{border:{width:3,radius:0,color:"#F9E98E"},title:{background:"#F0DE7D",color:"#A27D35"},background:"#FBF7AA",color:"#A27D35",classes:{tooltip:"qtip-cream"}},light:{border:{width:3,radius:0,color:"#E2E2E2"},title:{background:"#f1f1f1",color:"#454545"},background:"white",color:"#454545",classes:{tooltip:"qtip-light"}},dark:{border:{width:3,radius:0,color:"#303030"},title:{background:"#404040",color:"#f3f3f3"},background:"#505050",color:"#f3f3f3",classes:{tooltip:"qtip-dark"}},red:{border:{width:3,radius:0,color:"#CE6F6F"},title:{background:"#f28279",color:"#9C2F2F"},background:"#F79992",color:"#9C2F2F",classes:{tooltip:"qtip-red"}},green:{border:{width:3,radius:0,color:"#A9DB66"},title:{background:"#b9db8c",color:"#58792E"},background:"#CDE6AC",color:"#58792E",classes:{tooltip:"qtip-green"}},blue:{border:{width:3,radius:0,color:"#ADD9ED"},title:{background:"#D0E9F5",color:"#5E99BD"},background:"#E5F6FE",color:"#4D9FBF",classes:{tooltip:"qtip-blue"}}}})(jQuery);

var media_query = 'desktop';
var large_desktop = true;
var swipeLeft = rlLangDir == 'rtl' ? 'swiperight' : 'swipeleft';
var swipeRight = rlLangDir == 'rtl' ? 'swipeleft' : 'swiperight';
var fl_ratio = typeof window.devicePixelRatio != undefined ? window.devicePixelRatio : 1;

/**
 * document ready
 *
 **/
$(document).ready(function(){
    flynaxTpl.customInput();
    flynaxTpl.langSelector();
    flynaxTpl.userNavbar();
    flynaxTpl.categoryTree();
    flynaxTpl.urlHash();
    flynaxTpl.themeSwitcher();
    flynax.moreCategories();
    flynaxTpl.tabsMore();

    if ($.browser.msie && $.browser.version < 11) {
        $('body').addClass('ie-fallback');
    }

    $('div.sorting div.current').tplToggle({
        cont: $('div.sorting ul.fields'),
        parent: 'sorting'
    });

    $('section.side_block .expander').each(function(){
        $(this).parent().before($(this));
        $(this).tplToggle({
            cont: $(this).next(),
            id: 'cat_box_expander'
        });
    });

    $('#refine_keyword_opt').click(function(){
        $(this).closest('form').find('.options').slideToggle();
    });

    var plans_controllers = ['add_listing', 'upgrade_listing', 'my_packages', 'add_banner', 'registration', 'profile'];

    var desktop_match = function(){
        media_query = 'desktop';

        flynaxTpl.menu();
        flynaxTpl.sidebar('clear');

        if (!$('section.side_block > .expander').next().is(':visible')) {
            $('section.side_block > .expander').trigger('click');
        }

        $('div.categories').flCatSlider();

        if (plans_controllers.indexOf(rlPageInfo['controller']) >= 0) {
            if ($('div.plans-container ul.plans > li').length > 5) {
                $('div.plans-container').mCustomScrollbar('destroy');
                $('div.plans-container').mCustomScrollbar({horizontalScroll: true});

                if (rlLangDir == 'rtl') {
                    $('div.plans-container').mCustomScrollbar('scrollTo', 'right');
                }
            }
        }
    };

    // Move banner to appropriate position
    var moveBanner = function(back){
        var per_line = back ? 3 : 4;
        var $banner = $('#listings.grid .banner-in-grid');
        var $items  = $('#listings.grid > article.item');

        if ($banner.length) {
            $banner.each(function(index){
                var position = $('#listings.grid > *').index(this) - index;

                if (!$(this).data('position')) {
                    $(this).attr('data-position', position);
                }

                if (back) {
                    $items.eq($(this).data('position') - 1).after($(this));
                } else {
                    if (position % per_line !== 0) {
                        var offset = position / per_line;
                        offset = Math[offset < 1 ? 'ceil' : 'floor'](offset);
                        var to = offset * per_line;

                        $items.eq(to - 1).after($(this));
                    }
                }
            });
        }
    }

    /**
     * media queries handler
     *
     **/
    enquire.register("screen and (min-width: 1440px)", {
        match: function(){
            $('div.gallery > div.thumbs > div.slider > ul').attr('style', '');

            moveBanner();
        },
        unmatch: function(){
            $('div.gallery > div.thumbs > div.slider > ul').attr('style', '');

            moveBanner(true);
        }
    }).register("screen and (min-width: 1200px)", {
        match: function(){
            desktop_match();
            large_desktop = true;
        },
        unmatch: function(){}
    }).register("screen and (min-width: 992px) and (max-width: 1199px)", {
        match: function(){
            desktop_match();
            large_desktop = false;
        },
        unmatch: function(){}
    }).register("screen and (min-width: 768px) and (max-width: 991px)", {
        match: function(){
            media_query = 'tablet';

            flynaxTpl.mobileMenu();
            flynaxTpl.sidebar();

            $('div.categories').flCatSlider();

            if (plans_controllers.indexOf(rlPageInfo['controller']) >= 0) {
                if ($('div.plans-container ul.plans > li').length > 5) {
                    $('div.plans-container').mCustomScrollbar('destroy');
                    $('div.plans-container').mCustomScrollbar({horizontalScroll: true});

                    if (rlLangDir == 'rtl') {
                        $('div.plans-container').mCustomScrollbar('scrollTo', 'right');
                    }
                }
            }
        },
		unmatch: function(){}
    }).register("screen and (max-width: 767px)", {
        match: function(){
            media_query = 'mobile';

            flynaxTpl.moveLeftUserbarItems();
            flynaxTpl.mobileMenu();
            flynaxTpl.sidebar();
            flynaxTpl.picGallery();

            $('div.categories').flCatSlider();

            $('.grid_navbar .buttons .list').trigger('click');

            if ($('section.side_block > .expander').next().is(':visible')) {
                setTimeout(function(){
                    $('section.side_block > .expander').trigger('click');
                }, 5);
            }

            if (plans_controllers.indexOf(rlPageInfo['controller']) >= 0) {
                $('div.plans-container').mCustomScrollbar('destroy');
            }
        },
        unmatch: function(){
            flynaxTpl.picGallery();
            flynaxTpl.moveLeftUserbarItems(true);

            /* move home page map search */
            if (rlPageInfo['controller'] == 'home' && $('section.home-map > div.point1').length) {
                $('section.home-map div.controls > div.point1').append($('section.home-map > div.point1 > div#search_area'));
                $('section.home-map > div.point1').remove();
            }
        }
    });

    flFieldset();
    flynaxTpl.qtip();

    /* other */
    $('.numeric').numeric({
        decimal: rlConfig['price_separator']
    });

    $('footer .scroll-top').click(function(){
        $('body,html').animate({scrollTop: 0}, 'slow');
    });
});

/**
 * template related javascript handlers
 *
 **/
var flynaxTplClass = function(){

    /**
     * reference to it self object
     *
     **/
    var self = this;

    var prevHash = '';

    /**
     * url hash handler
     *
     **/
    this.urlHash = function(){
        $(window).on('hashchange', function(e){
            var hash = flynax.getHash();

            if (rlPageInfo['controller'] == 'listing_details' && hash != 'map-fullscreen') {
                var obj = 'ul.tabs li#tab_' + hash;
                tabsSwitcher(obj);
                if ($(obj).length > 0) {
                    $('html, body').animate({scrollTop: $(obj).offset().top - 58});
                }
            }
            if (prevHash == 'map-fullscreen' && !hash) {
                $('#modal_block div.inner > div.close').trigger('click');
            }

            prevHash = flynax.getHash();
        });
    };

    /**
     * Color theme switcher
     *
     * @since 4.9.0
     */
    this.themeSwitcher = function(){
        var $switcher = $('#theme-switcher');
        var $icon = $switcher.find('use');
        var $lightCss = $('link[href$="light.css"]');
        var $darkCss = $('link[href$="dark.css"]');

        $switcher.click(function(){
            var isDark = $(this).attr('data-theme') == 'dark';
            var setTheme = isDark ? 'light' : 'dark';

            $darkCss
                .attr('disabled', isDark)
                .attr('media', isDark ? 'not all' : 'all');

            $lightCss
                .attr('disabled', !isDark)
                .attr('media', isDark ? 'all' : 'not all');

            $(this).attr('data-theme', setTheme);
            createCookie('colorTheme', setTheme, 365);

            $icon.attr('xlink:href', setTheme == 'dark' ? '#theme-sun-icon' : '#theme-moon-icon');
        });

        if ((window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            $switcher.attr('data-theme', 'dark');
        }

        var cookieTheme = readCookie('colorTheme');

        if (cookieTheme) {
            $switcher.attr('data-theme', cookieTheme);
        }
    }

    /**
     * sidebar boxes arrangement handler
     *
     **/
    this.sidebar = function(mode){
		if (rlPageInfo['key'] == 'search_on_map')
			return;

        if (mode == 'clear') {
            if ($('aside.left.second-copy').length > 0) {
                $('#main_container > div.inside-container > div > aside.left').append($('aside.left.second-copy > *'));
                $('aside.left.second-copy, aside.left.first-copy').remove();
            }
        }
        else {
            if ($('aside.left.second-copy').length <= 0) {
                var target = $('#content aside.middle').length ? '#content aside.middle' : '#controller_area';
                $(target).after('<aside class="left second-copy clearfix"></div>');
                $('aside.left.second-copy').append($('#main_container > div.inside-container > div > aside.left > *:not(.stick)'));
            }
        }
    };

    /**
     * custom tips
     *
     **/
    this.qtip = function(){
        var tmp_style = jQuery.extend({}, qtip_style);
        tmp_style.tip = 'bottomMiddle';

        $('.hint').each(function(){
            $(this).qtip({
                content: $(this).attr('title') ? $(this).attr('title') : $(this).prev('div.qtip_cont').html(),
                show: 'mouseover',
                hide: 'mouseout',
                position: {
                    corner: {
                        target: 'topMiddle',
                        tooltip: 'bottomMiddle'
                    }
                },
                style: tmp_style
            }).attr('title', '');
        });
    };

    /**
     * main menu handler
     *
     **/
    this.menu = function(){
        var menu = $('.main-menu ul.menu');

        /* remove main menu header */
        $('section.main-menu ul.menu').before($('section.main-menu ul.menu > span'));

        /* move footer menu back to footer */
        $('nav.footer-menu ul').append($('section.main-menu ul.menu > li.mvd'));
        $('nav.footer-menu ul > li').removeClass('mvd hide');
        $('section.main-menu ul.menu').removeClass('mobile-menu');

        /* clear */
        $('#main_menu_more li').remove();
        menu.find('li:not(:visible)').show();
        menu.find('li.more').removeClass('more_active');
        menu.find('li:not(.more)').attr('style', '');
        $('ul#main_menu_more').hide();

        /* build menu */
        var width = menu.width(),
            buttonWidth = menu.find('li.more').outerWidth(),
            workWidth = width - buttonWidth,
            countWidth = 0,
            countItems = menu.find('li:not(:last)').length,
            border = false,
            margin = 0,
            effected = false;

        menu.find('li:not(:last)').each(function(index){
            countWidth += Math.ceil($(this)[0].getBoundingClientRect().width) + margin;

            if (index == 0) {
                countWidth -= margin / 2;
            }
            index++;

            var rest = countItems != index ? buttonWidth : 0;
            if (workWidth - countWidth < rest) {
                effected = true;

                if (!border && countItems != index) {
                    var newWidth = workWidth - (countWidth - $(this).width());
                    if (newWidth < $(this).width()) {
                        $(this).width(newWidth);
                    }
                    border = true;
                }
                else {
                    $('#main_menu_more').append('<li></li>');
                    $('#main_menu_more li:last').html($(this).find('a').parent().html()).addClass($(this).find('a').parent().attr('class'));
                    $(this).hide();
                }
            }
        });

        if (effected) {
            menu.find('li.more').show();
        }
        else {
            menu.find('li.more').hide();
        }

        /* set click handler */
        menu.find('li.more').unbind('click').click(function(){
            $(this).toggleClass('more_active');
            var width = menu.find('li.more > span').position(true).left - parseInt($('#main_menu_more').css('paddingLeft'));
            if (rlLangDir == 'rtl') {
                width -= ($('#main_menu_more').width() - parseInt($('#main_menu_more').css('paddingLeft')));
            }
            $('#main_menu_more').css('left', width).toggle();
        });

        /* document click handler */
        $(document).bind('click touchstart', function(event){
            var close = true;
            $(event.target).parents().each(function(){
                if ($(this).attr('id') == 'main_menu_more' || $(this).hasClass('more') || $(event.target).hasClass('more')) {
                    close = false;
                }
            });

            if (close) {
                $('#main_menu_more').hide();
                menu.find('li.more').removeClass('more_active');
            }
        });
    };

    /**
     * mobile menu handler
     *
     **/
    this.mobileMenu = function(){
        var $cont = $('nav.main-menu');
        var $menu = $cont.find('.menu > div');
        var $menuButton = $cont.find('.menu-button');
        var $header = $cont.find('.mobile-menu-header');
        var $closeButton = $header.find('svg');

        $menuButton.unbind('click').click(function(){
            $menu
                .removeClass('d-none')
                .addClass('mobile-menu');
        });
        $closeButton.unbind('click').click(function(){
            $menu
                .addClass('d-none')
                .removeClass('mobile-menu');
        });

        $(window).on('resize', function() {
            if ($(window).width() > 750) {
                $menu
                .addClass('d-none')
                .removeClass('mobile-menu');
            }
        });
    };

    /**
     * Left userbar items handler
     * Move items to mobile menu in mobile view and revert it in desktop views
     *
     * @param bool - Revert items to desktop position
     */
    this.moveLeftUserbarItems = function(revert){
        $desktopBar = $('#left-userbar');
        $mobileBar  = $('#mobile-left-usernav');

        if (revert) {
            $desktopBar.append($mobileBar.children());
        } else {
            $mobileBar.append($desktopBar.children());
        }
    };

    /**
     * highlight search results in grid
     **/
    this.highlightResults = function(query, details){
        if (!query)
            return;

        query = trim(query);
        var repeat = new RegExp('(\\s)\\1+', 'gm');
        query = query.replace(repeat, ' ');
        query = query.split(' ');

        var pattern = '';
        for (var i = 0; i < query.length; i++) {
            if (query[i].length > 2) {
                pattern += query[i] + '|'
            }
        }
        pattern = rtrim(pattern, '|');

        var pattern = new RegExp('(' + pattern + ')(?=[^>]*(<|$))', 'gi');
        var link_pattern = new RegExp('<a([^>]*)>(.*)</a>');
        var selectors = details ? '.table-container div.table-cell div.value, #area_listing div.table-cell div.value' : '#listings article ul.info li.title, #listings article ul.info li.fields';

        $(selectors).each(function(){
            var value = trim($(this).html());
            var href = false;
            if ($(this).find('a').length > 0) {
                value = trim($(this).find('a').html());
                href = $(this).find('a').attr('href');
                className = $(this).find('a').attr('class');
            }

            //value = value.replace(/(<([^>]+)>)/ig,"");
            value = value.replace(pattern, '<span class="ks-highlight">$1</span>');
            value = href ? '<a class="' + className + '" href="' + href + '">' + value + '</a>' : value;

            $(this).html(value);
        });
    };

    /**
     * custom checkboxes, radio handler
     *
     **/
    this.customInputIndex = 1;
    this.customInput = function(){
        $('body label > input[type=checkbox]:not(.default),body label > input[type=radio]:not(.default)').each(function(){
            var name = $(this).attr('id') ? $(this).attr('id') : 'css_' + $(this).prop('tagName') + '_' + self.customInputIndex;

            $(this).parent().prepend('<span></span>');
            $(this).attr('id', name);
            $(this).parent().attr('for', name);
            $(this).parent().before($(this));
            self.customInputIndex++;
        });
    };

    /**
     * languages selector handler
     *
     **/
    this.langSelector = function(){
        $('#lang-selector > .default').click(function(){
            $('span.circle_opened').not($(this).parent()).removeClass('circle_opened');
            $(this).parent().toggleClass('circle_opened');
        });

        $(document).bind('click touchstart', function(event){
            if (!$(event.target).parents().hasClass('circle_opened')) {
                $('#lang-selector').removeClass('circle_opened');
            }
        });
    };

    /**
     * user navigation bar
     *
     **/
    this.userNavbar = function(){
        $('#user-navbar > .default').click(function(){
            $('span.circle_opened').not($(this).parent()).removeClass('circle_opened');
            $(this).parent().toggleClass('circle_opened');
        });

        $(document).bind('click touchstart', function(event){
            if (!$(event.target).parents().hasClass('circle_opened') && event.target.type != 'password') {
                $('#user-navbar').removeClass('circle_opened');
            }
        });
    };

    /**
     * @deprecated 4.9.0
     **/
    this.shoppingCart = function(){}

    /**
     * category tree handler (available for boxes on listing type page only)
     *
     **/
    this.categoryTree = function(){
        $('.cat-tree-cont').each(function(){
            var count = $(this).find('ul.cat-tree > li').length;

            var desktop_limit_top = 10;
            var desktop_limit_bottom = 25;

            if (count <= 0)
                return;

            if ($(this).find('div.cat-toggle').attr('accesskey')) {
                desktop_limit_top = parseInt($(this).find('div.cat-toggle').attr('accesskey'));
            }

            $(this).find('ul.cat-tree > li span.toggle').click(function(){
                $(this).parent().find('ul').toggle();

                var parent = $(this).closest('.cat-tree-cont');
                if (parent.hasClass('mCustomScrollbar')) {
                    parent.addClass('limit-height').mCustomScrollbar('update');
                }

                $(this).text(trim($(this).text()) == '+' ? '-' : '+');
            });

            if ($(this).find('ul.cat-tree > li span.toggle:contains("+")').length == 0) {
                $(this).find('ul.cat-tree > li span.toggle').hide();
            }

            var current_media_query = media_query;
            $(window).resize(function(){
                if (media_query != current_media_query && $(this).hasClass('mCustomScrollbar')) {
                    $(this).addClass('limit-height').mCustomScrollbar('update');
                    current_media_query = media_query;
                }
            });

            if (count > desktop_limit_top && count <= desktop_limit_bottom) {
                var gt = desktop_limit_top - 1;
                $(this).find('ul.cat-tree > li:gt(' + gt + ')').addClass('rest');

                $(this).find('div.cat-toggle').removeClass('hide').click(function(){
                    $(this).prev().find('> li.rest').toggle();
                });
                $(this).removeClass('limit-height');
            }
            else if (count > desktop_limit_bottom) {
                $(this).mCustomScrollbar();
            }
            else {
                $(this).removeClass('limit-height');
            }
        });
    };

    /**
     * pictures gallery (on listing details page for example)
     *
     **/
    this.picGallery = function(){
        if (rlPageInfo['controller'] != 'listing_details') return;

        var gl = $('div.gallery');

        if (gl.length <= 0)
            return;

        var gls = gl.find('div.thumbs div.slider');

        /* unset all events */
        $(gl).find('span.nav-button.zoom').unbind('click');
        $(gl).find('div.thumbs div.next, div.thumbs div.prev').unbind('click');
        $(gl).find('div.thumbs div.slider').unbind(swipeRight);
        $(gl).find('div.thumbs div.slider').unbind(swipeLeft);
        $(gl).find('div.preview > img').unbind('click');
        $(gl).find('div.slider img, div.slider span.play').unbind('click')
        $(gl).find('div.slider > ul').unbind('animate');

        if (gls[0].swiper) {
            gls[0].swiper.destroy();
            gls[0].swiper = false;
            gls.removeClass('swiper-container-horizontal');
            gls.find('> ul').attr('style', '');
            gls.find('> ul > li').attr('style', '');
        }

        /* mobile version gallery */
        if (media_query == 'mobile') {
            if ($(gl).find('div.thumbs div.slider ul > li').length > 1) {
                flUtil.loadScript(rlConfig['tpl_base'] + 'js/swiper.jquery.min.js', function(){
                    var swiper = new Swiper('div.gallery div.thumbs div.slider', {
                        spaceBetween: 5,
                        slidesPerView: 1.1,
                        loop: true,
                        direction: 'horizontal',
                        lazyLoading: true,
                        onClick: function(e){
                            var index = (e.clickedIndex == 0
                                ? photos_source.length
                                : e.clickedIndex
                            ) - 1;
                            var preview = photos_source[index].href;
                            var iframe = '<iframe width="" height="" src="' + preview + '" frameborder="0" allowfullscreen=""></iframe><div></div><div></div>';

                            $('div.thumbs div.slider ul > li[data-swiper-slide-index=' + index + '] > span.play').replaceWith(iframe);
                        }
                    });
                });
            } else {
                if (photos_source[0].type == 'photo') {
                    $('div.gallery div.thumbs div.slider li.active img').css({backgroundImage: 'url(' + photos_source[0].href + ')'});
                } else {
                    var preview = photos_source[0].href;
                    var iframe = '<iframe width="" height="" src="' + preview + '" frameborder="0" allowfullscreen=""></iframe><div></div><div></div>';

                    $('div.thumbs div.slider ul > li:first > span.play').replaceWith(iframe);
                }
            }
        }
        /* tablet/desktop version gallery */
        else {
            flUtil.loadScript(rlConfig['tpl_base'] + 'js/jquery.fancybox.js', function(){
                $(gl).find('span.nav-button.zoom').click(function(){
                    if (media_query == 'mobile' || typeof window.Code != 'undefined') return;

                    var index = $(gl).find('div.thumbs div.slider ul > li').index($('div.thumbs div.slider ul > li.active'));

                    $.fancybox.open(photos_source, {
                        padding: 10,
                        index: index,
                        removeFirst: false,
                        customIndex: false,
                        mouseWheel: false,
                        autoSize: false,
                        closeBtn: true,
                        playSpeed: fb_slideshow_delay,
                        helpers: {
                            overlay: null,
                            title: {
                                type: 'over'
                            },
                            overlay: {
                                opacity: 0.5
                            },
                            buttons: fb_slideshow,
                            thumbs: {
                                width: 80,
                                height: 105,
                                source: function(item){
                                    return item.large;
                                }
                            }
                        }
                    });

                    return false;
                });
            });

            var current_image = 0;
            var current_slide = 0;
            var per_slide = 5;
            var picG = this;
            var item_width = 0;
            var items_count = 0;
            var prev_media_query = media_query;
            var prev_large_desktop = large_desktop;

            this.calc = function(){
                var width = $(gl).find('div.thumbs div.slider').width();
                item_width = $(gl).find('div.thumbs div.slider li:first').width();
                items_count = $(gl).find('div.thumbs div.slider li').length;
                per_slide = Math.floor(width / item_width);

                /* restore gallery state to default */
                if (current_image != 0) {
                    current_slide = per_slide;
                    picG.slide($(gl).find('div.thumbs div.prev'));
                }

                if (per_slide >= items_count) {
                    $(gl).find('div.thumbs div.next').addClass('disabled');
                }
            };

            picG.calc();

            /* thumbnail click handler */
            $(gl).find('div.slider img, div.slider span.play').click(function(){
                picG.loadImage(this);
            });

            /* navigation click handler */
            $(gl).find('div.thumbs div.next, div.thumbs div.prev').click(function(){
                picG.slide(this);
            });

            /* navigation click handler */
            $(gl).find('div.thumbs div.slider').bind(swipeRight, function(){
                picG.slide($(gl).find('div.thumbs div.prev'));
            }).bind(swipeLeft, function(){
                picG.slide($(gl).find('div.thumbs div.next'));
            });

            /* preview click handler */
            $('div.gallery div.preview > img').click(function(){
                var next_index = $(gl).find('div.thumbs div.slider ul > li').index($('div.thumbs div.slider ul > li.active'));
                next_index++;

                if (next_index + 1 > items_count) {
                    next_index = 0;
                    shift = 0;

                    self.slide($('div.gallery div.thumbs div.prev'));
                }

                if (next_index % per_slide == 0 && next_index != 0) {
                    self.slide($('div.gallery div.thumbs div.next'));
                } else {
                    picG.loadImage($(gl).find('div.thumbs div.slider li:eq(' + next_index + ') img'));
                }
            });

            $(window).resize(function(){
                if (media_query != prev_media_query || large_desktop != prev_large_desktop) {
                    picG.calc();
                    prev_media_query = media_query;
                    prev_large_desktop = large_desktop;
                }
            });

            this.loadImage = function(obj){
                if (media_query == 'mobile') {
                    return {};
                }

                var timer = false;
                var index = $(obj).closest('li').index('div.gallery div.slider ul > li');

                if (current_image == index)
                    return false;

                if (index >= (current_slide + per_slide)) {
                    $(gl).find('div.thumbs div.next').trigger('click');
                    return false;
                }

                $(gl).find('div.slider ul li.active').removeClass('active');
                $(gl).find('div.preview iframe').attr('src', '');

                current_image = index;

                // locked image mode
                if (photos_source[index].locked) {
                    var type_class = photos_source[index].type == 'video' ? 'fake-video' : 'picture';
                    $(gl).find('#media').attr('class', 'gallery locked ' + type_class);
                    $(obj).closest('li').addClass('active');
                }
                // default mode
                else {
                    $(gl).find('#media').attr('class', 'gallery');
                    $player = $('#player');

                    // video
                    if (photos_source[index].type == 'iframe') {
                        var preview = photos_source[index].href;

                        if (photos_source[index].local) {
                            $player.find('source').attr('src', preview);
                            $player.load();

                            $(gl).find('#media').attr('class', 'gallery video local');
                        } else {
                            $(gl).find('div.preview iframe').attr('src', preview);
                            $(gl).find('#media').attr('class', 'gallery video');
                        }

                        $(obj).closest('li').addClass('active');
                    }
                    // photos
                    else {
                        $(gl).find('div#media').removeClass('video local');
                        $player.get(0).pause();

                        timer = setTimeout(function(){
                            $(obj).after('<span class="img-loading"></span>');

                            var width = $(obj).width();
                            var height = $(obj).height();
                            var border = $(obj)[0].clientLeft;
                            $(obj).next().width(width).height(height).css({opacity: 0.5});
                        }, 10);

                        var photo_src = photos_source[index].href;

                        if (typeof $.fancybox != 'undefined') {
                            $.fancybox.setIndex(current_image);
                        }

                        var img = new Image();
                        img.onload = function(){
                            clearTimeout(timer);
                            $(gl).find('div.preview > img').attr('src', photo_src);

                            if ($(obj).next().hasClass('img-loading')) {
                                $(obj).next().fadeOut('fast', function(){
                                    $(this).remove();
                                    $(obj).closest('li').addClass('active');
                                });
                            } else {
                                $(obj).closest('li').addClass('active');
                            }
                        }
                        img.src = photo_src;
                    }
                }

                return false;
            };

            this.slide = function(button){
                if ($(button).hasClass('next')) {
                    if (current_slide + per_slide >= items_count)
                        return;

                    current_slide += per_slide;

                    if (current_slide + per_slide >= items_count) {
                        $(button).addClass('disabled');
                    }
                    $(button).prev().removeClass('disabled');
                }
                else {
                    if (current_slide <= 0)
                        return;

                    current_slide -= per_slide;

                    if (current_slide <= 0) {
                        $(button).addClass('disabled');
                    }
                    $(button).next().removeClass('disabled');
                }

                picG.loadImage($(gl).find('div.thumbs div.slider li:eq(' + current_slide + ') img'));

                var offset = (current_slide * item_width) * -1;

                if (rlLangDir == 'rtl') {
                    $(gl).find('div.slider > ul').animate({marginRight: offset});
                } else {
                    $(gl).find('div.slider > ul').animate({marginLeft: offset});
                }
            };

            // Run the first local video
            if (photos_source[0].type == 'iframe' && photos_source[0].local) {
                current_image = -1;
                picG.loadImage($(gl).find('div.slider ul > li:first span.play'));
            }
        }
    };

    /**
    * textarea counter setup
    *
    **/
    this.setupTextarea = function(selector){
        $('textarea[name=contact_message]').each(function(){
            $(this).next('.textarea_counter_default').remove();
            $(this).textareaCount({
                'maxCharacterSize': rlConfig['messages_length'],
                'warningNumber': 20
            });
        });
    }

    /**
     * @deprecated 4.8.0
     */
    this.listingDetails = function(){}

    /**
     * @deprecated 4.8.0
     */
    this.accountDetails = function(){}

    /**
     * location fields hamdler
     *
     **/
    this.locationHandler = function(){
        var region = $('input[name="f[region]"]');
        var states = $('select[name="f[b_states]"]');
        var country = $('select[name="f[b_country]"]');

        if (country.length <= 0)
            return;

        var locationHandlerAction = function(){
            if (country.val() == 'united_states') {
                region.closest('.submit-cell').hide();
                states.closest('.submit-cell').show();
            }
            else {
                region.closest('.submit-cell').show();
                states.closest('.submit-cell').hide();
            }
        };

        country.bind('change', locationHandlerAction);
        locationHandlerAction();
    };

    this.tabsMore = function(){
        $tabs = $('ul.tabs');

        $tabs.each(function(){
            var $tabsContainer = $(this);
            var tmSelf         = this; // reference to local method environment
            var width          = $tabsContainer.width();
            var height         = $tabsContainer.height();
            var count          = $tabsContainer.find('> li:not(.more,.overflowed)').length;
            var sum            = 0;
            var overflowed     = false;

            // Trigger resize for the hidden tabs
            if ($tabsContainer.is(':hidden')) setTimeout(function(){ $(window).trigger('resize'); }, 1);

            this.init = function(){
                $tabsContainer.find('> li:not(.more,.overflowed)').each(function(){
                    var index = $tabsContainer.find('> li').index(this) + 1;
                    var button = index == count ? 0 : height;

                    sum += $(this).outerWidth(true);

                    if (sum + button > width) {
                        if (!overflowed) {
                            tmSelf.more($(this).parent());
                        }
                        $(this).parent().find('li.overflowed > ul').append($(this));
                        overflowed = true;
                    }
                });
            };

            this.more = function(parent){
                parent.append('<li class="more"><span></span><span></span><span></span></li><li class="overflowed"><ul></ul></li>');
            };

            this.expand = function(){
                var tabsExpander = function(){
                    $tabsContainer.addClass('current');

                    $tabs.each(function(){
                        $(this).not('.current').find('> li.opened').removeClass('opened');
                    });

                    var $moreLi       = $tabsContainer.find('> li.more');
                    var $overflowedLi = $tabsContainer.find('> li.overflowed');
                    $moreLi[$moreLi.hasClass('opened') ? 'removeClass' : 'addClass']('opened');
                    $overflowedLi[$overflowedLi.hasClass('opened') ? 'removeClass' : 'addClass']('opened');

                    $tabsContainer.removeClass('current');
                };
                $tabsContainer.find('> li.more').unbind('click', tabsExpander).bind('click', tabsExpander);
            };

            this.tabsResize = function(){
                $tabsContainer.find('> li.more').remove();
                $tabsContainer.find('> li.overflowed > ul > li').each(function(){
                    $tabsContainer.append($(this));
                });
                $tabsContainer.find('> li.overflowed').remove();

                width      = $tabsContainer.width();
                sum        = 0;
                overflowed = false;

                tmSelf.init();
                tmSelf.expand();
            };

            this.init();
            this.expand();

            $(document).bind('mouseup touchstart', function(event){
                var container = $tabsContainer.find('> li.overflowed');

                // Trigger resize for the hidden tabs
                if ($tabsContainer.is(':hidden')) setTimeout(function(){ $(window).trigger('resize'); }, 1);

                if (!container.is(event.target)
                    && container.has(event.target).length === 0
                    && !$(event.target).hasClass('more')
                    && !$(event.target).parent().hasClass('more')
                ) {
                    $tabsContainer.find('> li.opened').removeClass('opened');
                }
            });

            $(window).bind('resize', this.tabsResize);
        });
    };

    this.afterListingsAjaxLoad = function(){
        flFavoritesHandler();
    };

    /**
     * @deprecated 4.9.0
     */
    this.contactOwnerSubmit = function() {};

    /**
     * @deprecated 4.9.0
     */
    this.realtyPropType = function(){}

    /**
     * @deprecated 4.9.0
     */
    this.searchFormPage = function(){}

    /**
     * @deprecated 4.8.2
     */
    this.searchFormBox = function() {};

    /**
     * @deprecated 4.7.0 - hisrc() library has been removed
     */
    this.hisrc = function(){};
    this.featured = function(){};
};

var flynaxTpl = new flynaxTplClass();

/**
 *
 * toggle container by clicking some element
 *
 **/
(function($){
    $.fn.tplToggle = function(options){
        var settings = $.extend({
            cont: false, // container to toggle
            parent: false, // parent element to detect on body click
            id: false // id to save state
        }, options);

        var self = this;

        if (settings.id) {
            if (parseInt(readCookie(settings.id))) {
                $(settings.cont).toggle();
                self.toggleClass('active');
            }
        }

        this.click(function(){
            if ($(this).hasClass('disabled'))
                return false;

            $(settings.cont).toggle();
            self.toggleClass('active');

            if (settings.id) {
                createCookie(settings.id, self.hasClass('active') ? 1 : 0, 365);
            }

            if ($(settings.cont).is(':visible') && settings.id == 'cat_box_expander' && $('div.cat-tree-cont').hasClass('mCustomScrollbar')) {
                $('div.cat-tree-cont').addClass('limit-height').mCustomScrollbar('update');
            }
        });
        // trigger click on box header (h3)
        this.parent().find('h3').click(function(){
            self.trigger('click');
        });

        if (settings.parent) {
            $(document).bind('click touchstart', function(event){
                if (!$(event.target).parents().hasClass(settings.parent)) {
                    $(settings.cont).hide();
                    self.removeClass('active');
                }
            });
        }
    };
}(jQuery));

/**
 *
 * jQuery file uploader plugin by Flynax
 *
 **/
(function($){
    $.flUpload = function(el, options){
        var base = this;

        base.validate = new Array();
        base.validate['image'] = ['image/jpeg', 'image/gif', 'image/png'];
        base.index = 0;
        base.files = new Array();

        // access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // add a reverse reference to the DOM object
        base.$el.data("flUpload", base);

        base.init = function(){
            base.options = $.extend({}, $.flUpload.defaultOptions, options);

            // initialize working object id
            if ($(base.el).attr('id')) {
                base.options.id = $(base.el).attr('id');
            }
            else {
                $(base.el).attr('id', base.options.id);
            }

            base.upload();
            base.deleteObj();
        };

        // upload
        base.upload = function(){
            $(base.el).change(function(){
                base.index = 0;

                if ($.browser.msie) {
                    var name;
                    eval("name = $(this).val().split('\\').reverse()[0];");
                    base.poorMode(false, name);

                    return;
                }

                var imgObj = this.files[0];
                var name = 'name' in imgObj ? imgObj.name : imgObj.fileName;
                var ext = name.split('.').reverse()[0];

                if (base.options.validate && base.validate[base.options.validate].indexOf(imgObj.type) < 0) {
                    printMessage('error', lang['notice_bad_file_ext'].replace('{ext}', '<b>' + ext + '</b>'));
                }
                else {
                    if (base.options.sampleFrame) {
                        var img = new Image();
                        if ('getAsDataURL' in imgObj) {
                            img.src = imgObj.getAsDataURL();
                            img.onload = function(){
                                var canvas = document.createElement('canvas');
                                var width = base.options.sampleMaxWidth;
                                var height = Math.floor((base.options.sampleMaxWidth * img.height) / img.width);

                                if (base.options.fixedSize && height < base.options.sampleMaxHeight) {
                                    width = width * base.options.sampleMaxHeight / height;
                                    height = base.options.sampleMaxHeight;
                                }
                                if (!canvas.getContext) {
                                    console.log('.getContext() doesn\'t work')
                                }
                                canvas.width = base.options.fixedSize ? base.options.sampleMaxWidth : width;
                                canvas.height = base.options.fixedSize ? base.options.sampleMaxHeight : height;
                                canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                                canvas.className = 'new';

                                $(base.options.sampleFrame).addClass('active').find('canvas').remove();
                                $(base.options.sampleFrame).prepend(canvas);
                                $(base.options.sampleFrame).find('.preview').hide();
                            };
                        }
                        else {
                            base.poorMode(imgObj, name, ext);
                        }
                    }
                    else {
                        console.log('Flynax Error: No sample/preview object specified')
                    }
                }
            });
        };

        base.poorMode = function(imgObj, name, ext){
            var html = '<div title="' + name + '" style="width: ' + base.options.sampleMaxWidth + 'px;height: ' + base.options.sampleMaxHeight + 'px;">' + name + '</div>';
            $(base.options.sampleFrame).find('img.preview,div').hide();
            $(base.options.sampleFrame).prepend(html);
            $(base.options.sampleFrame).addClass('active');
        };

        base.deleteObj = function(){
            $(base.options.sampleFrame).find('img.delete').unbind('click').click(function(){
                if ($(this).hasClass('ajax'))
                    return;

                $(base.options.sampleFrame).find('img.preview').show();
                $(base.options.sampleFrame).find('canvas, div').remove();
                $(base.options.sampleFrame).removeClass('active');
            });
        };

        // run initializer
        base.init();
    };

    $.flUpload.defaultOptions = {
        sampleFrame: false,
        sampleMaxWidth: 105,
        sampleMaxHeight: 105,
        validate: 'image',
        fixedSize: false,
        allowed: 3,
        unlimited: false
    };

    $.fn.flUpload = function(options){
        return this.each(function(){
            (new $.flUpload(this, options));
        });
    };

})(jQuery);

/**
 *
 * jQuery categoroes slider plugin by Flynax
 *
 **/
(function($){
    $.flCatSlider = function(el, options){
        var base = this;

        base.block_width = 0;
        base.work_width = 0;
        base.position = 0;
        base.areaMargin = 0;
        base.pages = 1;

        // access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // add a reverse reference to the DOM object
        base.$el.data("flCatSlider", base);

        base.init = function(){
            base.options = $.extend({}, $.flCatSlider.defaultOptions, options);

            // initialize working object id
            if ($(base.el).attr('id')) {
                base.options.id = $(base.el).attr('id');
            }
            else {
                $(base.el).attr('id', base.options.id);
            }

            if (!$(base.el).attr('id'))
                return;

            if (!$(base.el).next().hasClass('category-slider-bar')) {
                return;
            }

            base.clear();
            base.setSizes();
            base.eventsHandler();
            base.scroll();
        };

        base.clear = function(){
            $(base.el).attr('style', '');
            $(base.el).find('ul').attr('style', '')
            $(base.el).find('ul > li').attr('style', '');
            base.position = 0;
            $(base.el).next().find('.navigation > span').removeClass('active');
            $(base.el).next().find('.navigation > span:first-child').addClass('active');
            $(base.el).next().find('.prev').hide();
            $(base.el).next().find('.next').show();
        };

        base.setSizes = function(){
            base.pages = parseInt($(base.el).attr('id').split('_')[2]) || 1;

            if (base.pages < 2)
                return;

            base.block_width = 100;
            base.work_width = base.block_width - base.areaMargin
            //$(base.el).width(base.block_width);
            $(base.el).find('ul').css('width', (base.block_width * base.pages) + '%');
            $(base.el).find('ul li').css('width', (base.block_width / base.pages) + '%').show();

            $(base.el).next().find('.prev').hide();
        };

        base.eventsHandler = function(){
            if (base.pages < 0)
                return;

            $(base.el).next().find('.navigation span').unbind('click').click(function(){
                var point = parseInt($(this).attr('accesskey'));
                base.position = point - 1;
                base.slider();
            });


            var move_next = function(){
                var active = base.position + 1;
                if (active < base.pages) {
                    base.position++;
                    base.slider();
                }
            };

            $(base.el).next().find('.next').parent().unbind('click', move_next).bind('click', move_next);
            $(base.el).unbind(swipeLeft, move_next).bind(swipeLeft, move_next);

            var move_prev = function(){
                var active = base.position + 1;
                if (active > 1) {
                    base.position--;
                    base.slider();
                }
            };

            $(base.el).next().find('.prev').parent().unbind('click', move_prev).bind('click', move_prev);
            $(base.el).unbind(swipeRight, move_prev).bind(swipeRight, move_prev);
        };

        base.slider = function(){
            var pos = (base.block_width * base.position) * -1;
            var active = base.position + 1;
            if (rlLangDir == 'ltr') {
                $(base.el).find('ul').stop().animate({marginLeft: pos + '%'});
            }
            else {
                $(base.el).find('ul').stop().animate({marginRight: pos + '%'});
            }
            $(base.el).next().find('.navigation span').removeClass('active');
            $(base.el).next().find('.navigation span[accesskey=' + active + ']').addClass('active');

            if (active == 1) {
                $(base.el).next().find('.prev').fadeOut('normal');
            }
            else {
                $(base.el).next().find('.prev').fadeIn('normal');
            }

            if (active == base.pages) {
                $(base.el).next().find('.next').fadeOut('normal');
            }
            else {
                $(base.el).next().find('.next').fadeIn('normal');
            }
        };

        base.scroll = function(){
            if ($(base.el).next().hasClass('mousewheel')) {
                var last_time_stamp = 0;

                $(base.el).parent().unbind('mousewheel').bind('mousewheel', function(e, data){
                    // speed down the scroll
                    if (e.timeStamp - last_time_stamp < 500) {
                        e.preventDefault();
                        return;
                    }

                    last_time_stamp = e.timeStamp;

                    if (data < 0) {
                        var active = base.position + 1;
                        if (active < base.pages) {
                            base.position++;
                            base.slider();
                            e.preventDefault();
                        }
                    }
                    else {
                        var active = base.position + 1;
                        if (active > 1) {
                            base.position--;
                            base.slider();
                            e.preventDefault();
                        }
                    }
                });
            }
        };

        // run initializer
        base.init();
    };

    $.flCatSlider.defaultOptions = {
        scroll: 1
    };

    $.fn.flCatSlider = function(options){
        return this.each(function(){
            (new $.flCatSlider(this, options));
        });
    };

})(jQuery);

/**
 *
 * jQuery common slider plugin by Flynax
 *
 **/
(function($){
    $.flSlider = function(el, options){
        var base = this;

        base.items = new Array();
        base.loading = new Array();
        base.currentSlide = 0;
        base.parent = false;
        base.cont = false;
        base.slidesNumber = 0;
        base.itemsPerSlide = 0;
        base.workSide = 0;
        base.itemSide = 0;
        base.itemsCount = 0;

        // access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // add a reverse reference to the DOM object
        base.$el.data("flSlider", base);

        base.init = function(){
            base.options = $.extend({}, $.flSlider.defaultOptions, options);

            // initialize working object id
            if ($(base.el).attr('id')) {
                base.options.id = $(base.el).attr('id');
            }
            else {
                $(base.el).attr('id', base.options.id);
            }

            base.preLoader();
        };

        base.preLoader = function(){
            if (!base.options.height && base.options.vertical) {
                console.log('The HEIGHT parameter is required in VERTICAL slider mode');
                return;
            }

            $(base.el).css('opacity', 0);

            var index = 0;
            $(base.el).find('li').each(function(){
                eval(" \
				var obj" + index + " = this; \
				var src" + index + " = $(obj" + index + ").find('img').attr('src'); \
				if ( src" + index + " ) \
				{ \
					base.loading[" + index + "] = 'progress'; \
					var img" + index + " = new Image(); \
					img" + index + ".onload = function(){ \
						base.loading[" + index + "] = 'success'; \
						base.items[" + index + "] = new Array(src" + index + ", $(obj" + index + ").width(), $(obj" + index + ").height()); \
						if ( base.loading.indexOf('progress') < 0 ) \
						{ \
							base.setSizes(); \
						} \
					}; \
					img" + index + ".src = src" + index + "; \
				} \
				");
                index++;
            });
        };

        base.setSizes = function(){
            var add_class = base.options.vertical ? ' vertical' : ' horizontal';
            base.itemsCount = $(base.el).find('li').length;
            $(base.el).before('<div class="slider ' + add_class + '"><div class="prev"></div><div class="container"></div><div class="next"></div></div>');
            $(base.el).prev().children('div.container').html($(base.el));
            base.parent = $(base.el).parent().parent();
            base.cont = $(base.el).parent();

            if (base.options.vertical) {
                $(base.parent).height(base.options.height);
                $(base.cont).height(base.options.height - 40);// -container margin (top and bottom)
                base.workSide = base.options.height - 40;
                base.itemSide = $(base.el).find('li:first').height();
            }
            else {
                base.workSide = $(base.cont).width();
                $(base.cont).width(base.workSide);//fix the width

                var max_height = 0;
                var total_width = 0;

                for (var i = 0; i < base.items.length; i++) {
                    max_height = base.items[i][2] > max_height ? base.items[i][2] : max_height;
                    total_width += base.items[i][1];
                }
                $(base.parent).height(max_height);
                //$(base.el).width(total_width);

                base.itemSide = $(base.el).find('li:first').width();
            }

            if (!base.itemSide) {
                console.log('Can not detect slider work side length (width/height), probably ul located in hidden element.');
                return;
            }

            base.itemsPerSlide = Math.floor((base.workSide + base.options.clearance) / base.itemSide);
            base.slidesNumber = Math.ceil(base.itemsCount / base.itemsPerSlide);

            if (base.slidesNumber <= 1) {
                $(base.el).closest('div.slider').find('div.prev, div.next').remove();
                $(base.el).parent().css({width: 'auto', margin: 0});
                $(base.el).css({opacity: 1});
                return;
            }

            $(base.el).animate({opacity: 1});

            base.setMargin();
            base.eventsHandler();
        };

        base.setMargin = function(){
            var work_width = (base.itemsPerSlide * base.itemSide) - base.options.clearance;
            base.options.perSlide = base.itemsPerSlide < base.options.perSlide ? base.itemsPerSlide : base.options.perSlide;
            var rest = base.workSide - work_width;

            var margin = Math.floor(rest / (base.itemsPerSlide - 1));
            if (margin >= 1) {
                base.itemSide += margin;
                if (base.options.vertical) {
                    $(base.el).find('li').css('marginBottom', margin + 'px');
                }
                else {
                    $(base.el).find('li').css('marginRight', margin + 'px');
                }
            }
        };

        base.eventsHandler = function(){
            $(base.parent).find('div.navigation a').click(function(){
                var point = parseInt($(this).attr('accesskey'));
                currentSlide = point - 1;
                base.slider();
            });

            $(base.parent).find('div.next').click(function(){
                if (base.currentSlide + 1 < base.slidesNumber) {
                    base.currentSlide++;
                    base.slider();
                }
            });
            $(base.parent).find('div.prev').click(function(){
                if (base.currentSlide > 0) {
                    base.currentSlide--;
                    base.slider();
                }
            });
        };

        base.slider = function(){
            var pos = (base.itemSide * base.options.perSlide * base.currentSlide) * -1;

            if (base.options.vertical) {
                $(base.el).animate({marginTop: pos});
            }
            else {
                $(base.el).animate({marginLeft: pos});
            }
//			$(base.el).next().find('div.navigation a').removeClass('active');
//			$(base.el).next().find('div.navigation a[accesskey='+ active +']').addClass('active');
        };

        // run initializer
        base.init();
    };

    $.flSlider.defaultOptions = {
        vertical: false,
        height: false,//required in vertical mode,
        preload: true,
        perSlide: 1,
        clearance: 0
    };

    $.fn.flSlider = function(options){
        return this.each(function(){
            (new $.flSlider(this, options));
        });
    };

})(jQuery);

/**
 *
 * tabs click handler
 *
 * @param object obj - tab object referent
 *
 **/
$(document).ready(function(){
    $('.tabs:not(.tabs-hash) li:not(.more,.overflowed)').click(function(){
        tabsSwitcher(this);
    });

    hashTabs();
});

var tabsSwitcher = function(obj){
    if ($(obj).length <= 0) {
        return;
    }

    var tab_key = $(obj).attr('id').split('_').slice(1, $(obj).attr('id').split('_').length).join('_'),
        tab_instance = $(obj).closest('ul.tabs');

    $(tab_instance).find('li:not(.more, .overflowed)').each(function(){
        var key = $(this).attr('id').split('_').slice(1, $(this).attr('id').split('_').length),
            key_string = key.join('_');
        $('div#area_' + key_string).hide();
    });

    $(tab_instance).find('li.active').removeClass('active');

    $(obj).addClass('active');
    $('div#area_' + tab_key).show();
};

var hashTabs = function(){
    hashTabsSwitcher(window.location.hash);

    $('.tabs.tabs-hash li:not(.more,.overflowed) a').off().on('click', function(e){
        e.preventDefault();
        hashTabsSwitcher(this);
    });
}

var hashTabsSwitcher = function(obj){
    if (!obj) {
        return;
    }

    var $obj;
    var postfix = '_tab';

    if (typeof(obj) === 'string') {
        var key = obj.replace(postfix, '');
        $obj = $('ul.tabs-hash a[href="' + key + '"]');

        // prevent for second call in window hash
        if (!$obj.length || $obj.parent().hasClass('active')) {
            return;
        }
    } else {
        $obj = $(obj);
    }

    var tab_key = $obj.data('target');
    var tab_instance = $obj.closest('ul.tabs');

    $(tab_instance).find('li:not(.more, .overflowed) a').each(function(){
        var key = $(this).data('target');
        $(tab_instance).parent().find('div#area_' + key).hide();
    });

    $(tab_instance).find('li.active').removeClass('active');

    $obj.parent().addClass('active');
    $('div#area_' + tab_key).show();

    window.location.hash = tab_key + postfix;
};

/**
 *
 * favorites handler
 *
 **/
$(document).ready(function(){
    flFavoritesHandler();
});

var flFavoritesHandler = function(){

    /* favorites handler */
    $('.favorite').each(function(){
        var id = $(this).attr('id').split('_')[1];
        var ids = readCookie('favorites');

        if (ids) {
            ids = decodeURIComponent(ids);
            ids = ids.split(',');

            if (ids.indexOf(id) >= 0) {
                $(this).removeClass('add').addClass('remove');
                $(this).attr('title', lang['remove_from_favorites']).find('span.link').text(lang['remove_from_favorites']);
            }
        }
    });

    $('.favorite').unbind('click').click(function(){
        var id = $(this).attr('id').split('_')[1];
        var ids = readCookie('favorites');

        if (ids) {
            ids = decodeURIComponent(ids);
            ids = ids.split(',');

            if (ids.indexOf(id) >= 0) {
                ids.splice(ids.indexOf(id), 1);
                createCookie('favorites', ids.join(','), 93);

                $(this).removeClass('remove').addClass('add');
                $(this).attr('title', lang['add_to_favorites']).find('span.link').text(lang['add_to_favorites']);

                removeFromFavorites(id, true);

                if (rlPageInfo['key'] == 'my_favorites') {
                    $(this).closest('article').fadeOut(function(){
                        $(this).remove();
                        printMessage('notice', lang['notice_listing_removed_from_favorites']);

                        if ($('#listings > article').length < 1) {
                            if ($('ul.pagination').length > 0) {
                                var redirect = rlConfig['seo_url'];
                                redirect += rlConfig['mod_rewrite'] ? rlPageInfo['path'] + '.html' : 'index.php?page=' + rlPageInfo['path'];
                                location.href = redirect;
                            }
                            else {
                                var div = '<div class="info">' + lang['no_favorite'] + '</div>';
                                $('#controller_area').append(div);
                                $('.grid_navbar').remove();
                            }
                        }
                    });
                }

                return;
            }
            else {
                ids.push(id);
                addToFavorite(id);
            }
        }
        else {
            ids = new Array();
            ids.push(id);
            addToFavorite(id);
        }

        createCookie('favorites', ids.join(','), 93);

        $(this).removeClass('add').addClass('remove');
        $(this).attr('title', lang['remove_from_favorites']).find('span.link').text(lang['remove_from_favorites']);
    });
}

/**
 * Add listing to favorites list
 * @param {int} id
 */
var addToFavorite = function(id){
    ajaxFavorite(id);
};

/**
 * Remove listing from favorites list
 * @param {int} id
 */
var removeFromFavorites = function(id){
    ajaxFavorite(id, true);
};

/**
 * Adding/removing listing in favorites list (for logged users only)
 * @param {int}  id
 * @param {bool} delete_action
 */
var ajaxFavorite = function(id, delete_action){
    if (isLogin) {
        $.post(rlConfig['ajax_url'], {mode: 'ajaxFavorite', id: id, delete: delete_action});
    }
};

/**
 * notices/errors handler
 *
 * @param string type - message type: error, notice, warning
 * @param string/array message - message text
 * @param string/array fields - error fields names, array or comma separated
 * @param direct - DEPRECATED
 *
 **/
var PMtimer = false;
var printMessage = function(type, message, fields, direct){
    if (!message || !type)
        return;

    var self = this;
    var types = new Array('error', 'notice', 'warning');
    var height = 0;
    var from_top = false;

    var time = 20 //seconds
    time *= 1000;

    if (types.indexOf(type) < 0)
        return;

    this.isFixed = function(){
        //var selector = media_query == 'desktop' ? 'body > header > section.point1' : 'body > header';
        var header_height = $('body header.page-header').height() + $('.header-banner-cont').outerHeight();
        return $(document).scrollTop() > header_height ? true : false;
    };

    this.getHeight = function(){
        return media_query != 'mobile' ? $('body > header').height() : $('body > header > section.point1').height();
    };

    this.build = function(){
        this.closeMessage();

        var offset = 0;
        var addClass = this.isFixed() ? ' fixed' : '';

        if ($('body > div#modal_mask').length > 0) {
            addClass += ' top';
            this.from_top = true;
        }
        var html = ' \
			<div class="notification ' + type + addClass + ' hide"><div> \
				<div class="message">' + message + '</div> \
				<div class="close close-black" title="' + lang['close'] + '"></div> \
			</div></div> \
		';

        if ($('body > *:first').hasClass('tmp-hidden')) {
            $('body').append(html);
        } else {
            $('section#main_container').prepend(html);
        }
        height = $('.notification').height() - offset; //70 is shadow height

        if (this.from_top) {
            offset = 0;
        }

        $('.notification').css('top', '-' + height + 'px').show().animate({top: offset}, 'slow', function(){
            if (self.isFixed()) {
                $(this).addClass('done');
            }
        });

        PMtimer = setTimeout(self.closeMessage, time);
    };

    this.closeMessage = function(build){
        clearTimeout(PMtimer);

        var top = $('.notification').hasClass('fixed') && media_query == 'desktop' ? 58 : 0;

        $('.notification').animate({top: 50 + top, opacity: 0}, function(){
            $('.notification').remove();
            if (build) {
                self.build();
            }
        });
    };

    if (typeof(message) == 'object') {
        var tmp = '<ul>';
        for (var i = 0; i < message.length; i++) {
            tmp += '<li>' + message[i] + '</li>';
        }
        tmp += '</ul>';
        message = tmp;
    }

    $('input,select,textarea,table.error').removeClass('error');

    /* highlight error fields */
    if (fields) {
        if (typeof(fields) != 'object') {
            fields = fields.split(',');
        }

        for (var i = 0; i < fields.length; i++) {
            if (!fields[i])
                continue;

            if (trim(fields[i]) != '') {
                if (fields[i].charAt(0) == '#') {
                    $(fields[i]).addClass('error');
                }
                else {
                    var selector = 'input[name="' + fields[i] + '"],input[name^="' + fields[i] + '"]:last:not(.policy),select[name="' + fields[i] + '"],textarea[name="' + fields[i] + '"]';
                    if ($(selector).length > 0 && $(selector).attr('type') != 'radio' && $(selector).attr('type') != 'checkbox') {
                        $(selector).addClass('error');
                    }
                    else {
                        if ($(selector).attr('type') == 'radio' || $(selector).attr('type') == 'checkbox') {
                            $(selector).closest('div.field').addClass('error');
                        }
                        else {
                            $('input[name="' + fields[i] + '"],select[name="' + fields[i] + '"],textarea[name^="' + fields[i] + '"]').parent().addClass('error');
                        }
                    }
                }
            }
        }

        var removeHighlightFromParent = function () {
            $(this).closest('div.error').removeClass('error');
        };

        $('div.field.error input')
            .off('click', removeHighlightFromParent)
            .on('click', removeHighlightFromParent);

        var removeHighlightFromField = function () {
            $(this).removeClass('error');
        };

        $('input,select,textarea,table.error')
            .off('focus', removeHighlightFromField)
            .on('focus', removeHighlightFromField);
    }

    if ($('.notification').length > 0) {
        self.closeMessage(true);
    }
    else {
        self.build();
    }

    $('.notification div.close').live('click', function(){
        self.closeMessage();
    });

    $('.notification').mouseenter(function(){
        clearTimeout(PMtimer);
    }).mouseleave(function(){
        PMtimer = setTimeout(self.closeMessage, time);
    });

    var notificationScroll = function(){
        if (this.isFixed()) {
            $('.notification').addClass('fixed done');
        }
        else {
            $('.notification').removeClass('hide fixed done').removeAttr('style');
        }
    };
    $(window).bind('touchmove scroll', notificationScroll);
};

var flFieldset = function(){
    var open = function(fieldset, quick){
        if (quick) {
            fieldset.find('div.body').show();
            fieldset.find('span.arrow').removeClass('up');
        } else {
            fieldset.find('div.body').slideDown(function(){
                fieldset.find('span.arrow').removeClass('up');
            });
        }
    }

    var close = function(fieldset, quick){
        if (quick) {
            fieldset.find('div.body').hide();
            fieldset.find('span.arrow').addClass('up');
        } else {
            fieldset.find('div.body').slideUp(function(){
                fieldset.find('span.arrow').addClass('up');
            });
        }
    }

    var add = function(cookies, id){
        if (cookies) {
            if (cookies.indexOf(id) < 0) {
                cookies.push(id);
                id = cookies.join(',');
            }
        }
        createCookie('fieldset', id, 62);
    }

    var remove = function(cookies, id){
        cookies.splice(cookies.indexOf(id), 1);
        createCookie('fieldset', cookies.join(','), 62);
    }

    $('div.fieldset header .arrow').unbind('click').click(function(){
        var parent = $(this).closest('.fieldset');
        var id = $(parent).attr('id');
        var cookies = readCookie('fieldset') ? readCookie('fieldset').split(',') : Array();

        // close
        if (parent.find('div.body').is(':visible')) {
            close(parent);

            if (parent.hasClass('hidden-default')) {
                remove(cookies, id);
            } else {
                add(cookies, id);
            }
        }
        // open
        else {
            open(parent);

            if (parent.hasClass('hidden-default')) {
                add(cookies, id);
            } else {
                remove(cookies, id);
            }
        }
    });

    $('div.fieldset').each(function(){
        var id = $(this).attr('id');
        var cookies = readCookie('fieldset') ? readCookie('fieldset').split(',') : Array();

        // close quick
        if (($(this).hasClass('hidden-default') && cookies.indexOf(id) < 0) || (!$(this).hasClass('hidden-default') && cookies.indexOf(id) >= 0)) {
            close($(this), true);
        }
        // open quick
        else if (($(this).hasClass('hidden-default') && cookies.indexOf(id) >= 0) || (!$(this).hasClass('hidden-default') && cookies.indexOf(id) < 0)) {
            open($(this), true);
        }
    });
}

/**
 *
 * hide or show the object (via jQuery effect) by ID, and hide all objects by html path
 *
 * @param srting id - field id
 * @param srting path - html path
 *
 **/
function show(id, path) {
    if (path != undefined) {
        $(path).slideUp('fast');
    }

    if ($('#' + id).css('display') == 'block') {
        $('#' + id).slideUp('normal');
    }
    else {
        $('#' + id).slideDown('slow');
    }
}

/**
 *
 * trim string
 *
 * @param string str - string for trim
 * @param string chars - chars to be trimmed
 *
 * @return trimmed string
 *
 **/
function trim(str, chars) {
    return ltrim(rtrim(str, chars), chars);
}

/**
 *
 * left trim string
 *
 * @param string str - string for trim
 * @param string chars - chars to be trimmed
 *
 * @return trimmed string
 *
 **/
function ltrim(str, chars) {
    if (!str)
        return;

    chars = chars || "\\s";
    return str.replace(new RegExp("^[" + chars + "]+", "g"), "");
}

/**
 *
 * right trim string
 *
 * @param string str - string for trim
 * @param string chars - chars to be trimmed
 *
 * @return trimmed string
 *
 **/
function rtrim(str, chars) {
    if (!str)
        return;

    chars = chars || "\\s";
    return str.replace(new RegExp("[" + chars + "]+$", "g"), "");
}

/**
 *
 * escape or replace quotes
 *
 * @param string str - string for replacing
 * @param bool to - replace if true and escape if false
 *
 **/
function quote(str, to) {
    if (!to) {
        return str.replace(/'/g, "").replace(/"/g, "");
    }
    else {
        var to_single = '&rsquo;';
        var to_double = '&quot;';

        return str.replace(/'/g, to_single).replace(/"/g, to_double).replace(/\n/g, '<br />');
    }
}

/**
 * @deprecated 4.7.0 - hisrc() library has been removed
 */
var caroselCallback = function(){};
