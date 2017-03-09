/**
 * $.notify by xiaohuilam
 */

(function(window, $, undefined){
    $.extend({
        notify: function(msg, style, ttl, data, callback_on_click, callback_on_ready, callback_on_close){
            var id = 'nty-' + parseInt(Math.random()*1000000);
            if ('undefined' === typeof ttl) ttl = 5;
            if ('undefined' === typeof style) style = 'success';
            if ('undefined' === typeof data) data = {};
            $('.alert-notify').remove();
            data_string = '';
            for(i in data){
                data_string += i+'="'+data[i]+'" ';
            }
            var dom = $([
            '<div ', data_string, ' id="', id, '" class="no-border no-corner alert-notify alert alert-', style, ' alert-dismissible col-md-6 col-md-offset-3" style="position: fixed; bottom: 0px; z-index:1099" role="alert">',
            '  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>',
            '  <span class="message">', msg, '</span>',
            '</div>'
            ].join(''));
            $('body').append(dom);
            if ('function' === typeof callback_on_ready) callback_on_ready();
            if ('function' === typeof callback_on_click) $('#'+id+' span.message').on('click', function(event) {
                if(callback_on_click(dom)){
                    $('#'+id+' button.close').click();
                };
            });
            if ('function' === typeof callback_on_close) $('#'+id+' button.close').on('click', function(event) {
                callback_on_close(dom);
            });
            setTimeout(function(){
                $('#'+id).remove();
            }, ttl*1000);
        }
    });
})(window, jQuery);
