
(function( $ ) {
    "use strict";
  
    $(document).ready(function(){
        var wp = window.wp;
      

        //Upload on page Media Library (upload.php)
        if (typeof wp !== 'undefined' && typeof wp.Uploader === 'function') {
            $.extend( wp.Uploader.prototype, {
                progress: function() {},
                init : function() {
                  
                    if (this.uploader) {
                      
                        
                        this.uploader.bind('FileFiltered', function( up, file ) {
                           
                        });
                       
                        this.uploader.bind('BeforeUpload', function(uploader, file) {
                        
                            var folder_id = Number($( ".wpmediacategory-filter" ).val());
                            var params = uploader.settings.multipart_params;
                            
                            if(Number.isInteger(folder_id) && folder_id > 0){
                                params.themedoWMCFolder = folder_id;
                            }
                            
                        })
                        this.uploader.bind('UploadProgress', function(up, file) {
                            $('.uploader-window').hide().css('opacity', 0);
                            mediasorterWMC.mediasorter_begin_loading ();
                            
                        });


                        this.uploader.bind('UploadComplete', function(up, files) {
                            mediasorterWMC.mediasorter_finish_loading ();
                            //run after FilesAdded
                            var current_folder_id =$( ".wpmediacategory-filter" ).val();
                            var $current_tree_anchor = jQuery('.menu-item[data-id="' + current_folder_id + '"] .jstree-anchor');
                            $current_tree_anchor.trigger( "click" );
                        });

                        this.uploader.bind('FilesAdded', function( up, files ) {
                           
                            var current_folder_id =$( ".wpmediacategory-filter" ).val();
                            var $current_tree_anchor = jQuery('.menu-item[data-id="' + current_folder_id + '"] .jstree-anchor');
                            var $uncategory_tree_anchor = jQuery('.menu-item[data-id=-1] .jstree-anchor');
                            var $all_tree_anchor = jQuery('.menu-item[data-id="all"] .jstree-anchor');
                            
                            files.forEach(function(file){
                                mediasorterWMC.updateCount(null, current_folder_id);
                                if(current_folder_id === 'all'){
                                    $uncategory_tree_anchor.addClass('need-refresh');
                                    mediasorterWMC.updateCount(null, -1);
                                }else if(Number(current_folder_id) === -1){
                                    $all_tree_anchor.addClass('need-refresh');
                                    mediasorterWMC.updateCount(null, 'all');
                                }else{
                                    $current_tree_anchor.addClass('need-refresh');
                                    mediasorterWMC.updateCount(null, 'all');
                                }

                            });
                           
                            
                        });

                    }

                }
            });
        }

        
    });

    //deleting
    jQuery(document).ajaxSend(function (e, xhs, req) {
        
        try {
            if (req.data.indexOf("action=delete-post") > -1) {
                var attachment_id = req.context.id;
                jQuery.ajax({
                  type : "POST",
                  dataType: 'json',
                  data : {id: attachment_id, action: 'mediasorterGetTermsByAttachment', nonce: mediasorterConfig1.nonce},
                  url : ajaxurl,
                  success: function (resp){
                        // get terms of attachment
                        var terms = Array.from(resp.data, v => v.term_id);
                      
                        if(terms.length){
                            $('#mediasorter_terms').val(terms.join());
                        }
                    }
                });
            }
        }catch(e) {}

    }.bind(this));


    //delete complete
    jQuery(document).ajaxComplete(function (e, xhs, req) {
        try {
            if (req.data.indexOf("action=delete-post") > -1) {
                var current_folder = $( ".wpmediacategory-filter" ).val();
                var terms_str = $('#mediasorter_terms').val();
                //if not in any terms (folder)
                if(terms_str){
                    var terms = terms_str.split(",");
                    $.each(terms, function(index, value){
                        
                        mediasorterWMC.updateCount(value, null);
                    });
                }else if(current_folder === 'all' && !terms_str){
                    mediasorterWMC.updateCount(-1, null);
                }

                if(Number(current_folder) === -1){
                    mediasorterWMC.updateCount(-1, null);
                }
                
                mediasorterWMC.updateCount('all', null);

            }
        }catch(e) {}
    }.bind(this));


})( jQuery );