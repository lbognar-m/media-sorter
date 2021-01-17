(function( $ ) {
    "use strict";
    var mediasorter_media = {};
    // For add new media
    mediasorter_media.addMedia = function(){

        if (!$("body").hasClass("media-new-php")) {
            return;
        }
        setTimeout(function() {
            if (uploader) {
                uploader.bind('BeforeUpload', function(uploader, file) {
                    var params = uploader.settings.multipart_params;
                    params.themedoWMCFolder = $('.themedo-mediasorter-editcategory-filter').val();
                    var mediaRowFilename = $('#media-item-' + file.id).find(".filename");
                });
            }
        }.bind(this), 500);
    }

    $(document).ready(function(){
        var wp = window.wp;
        mediasorter_media.addMedia();

    });
})( jQuery );