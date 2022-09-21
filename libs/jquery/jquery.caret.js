/*
 * jQuery plugin: fieldSelection - v0.1.1 - last change: 2006-12-16
 * (c) 2006 Alex Brem <alex@0xab.cd> - http://blog.0xab.cd
 */
(function() {
    var fieldSelection = {
        getSelection: function() {
            var e = this.jquery ? this[0] : this;

            return (
                /* mozilla / dom 3.0 */
                ('selectionStart' in e && function() {
                    var l = e.selectionEnd - e.selectionStart;
                    return { start: e.selectionStart, end: e.selectionEnd, length: l, text: e.value.substr(e.selectionStart, l) };
                }) ||

                /* exploder */
                (document.selection && function() {
                    e.focus();
                    var r = document.selection.createRange();
                    if (r == null) {
                        return { start: 0, end: e.value.length, length: 0 }
                    }

                    var re = e.createTextRange();
                    var rc = re.duplicate();
                    re.moveToBookmark(r.getBookmark());
                    rc.setEndPoint('EndToStart', re);

                    /* vs hack */
                    var subtract = 0;

                    for (var vs = 0; vs < rc.text.length; vs++) {
                        if (rc.text.charCodeAt(vs).toString(16) == 'd') {
                            subtract += 1;
                        }
                    }
                    /* vs hack end */

                    return { start: rc.text.length-subtract, end: rc.text.length-subtract + r.text.length, length: r.text.length-subtract, text: r.text };
                }) ||

                /* browser not supported */
                function() {
                    return { start: 0, end: e.value.length, length: 0 };
                }
            )();
        },
        replaceSelection: function() {
            var e = (typeof this.id == 'function') ? this.get(0) : this;
            var text = arguments[0] || '';

            return (
                /* mozilla / dom 3.0 */
                ('selectionStart' in e && function() {
                    e.value = e.value.substr(0, e.selectionStart) + text + e.value.substr(e.selectionEnd, e.value.length);
                    return this;
                }) ||

                /* exploder */
                (document.selection && function() {
                    e.focus();
                    document.selection.createRange().text = text;
                    return this;
                }) ||

                /* browser not supported */
                function() {
                    e.value += text;
                    return jQuery(e);
                }
            )();
        }
    };

    jQuery.each(fieldSelection, function(i) { jQuery.fn[i] = this; });

})();

new function($) {
  $.fn.setCursorPosition = function(pos) {
    if ($(this).get(0).setSelectionRange) {
      $(this).get(0).setSelectionRange(pos, pos);
    } else if ($(this).get(0).createTextRange) {
      var range = $(this).get(0).createTextRange();
      range.collapse(true);
      range.moveEnd('character', pos);
          range.moveStart('character', pos);
          range.select();
    }
  }
}(jQuery);
