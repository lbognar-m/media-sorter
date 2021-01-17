
(function( $ ) {
	"use strict";

	var html = '',
		Core = {};

	$(document).ready(function(){
		

		Core.init();
		Core.jstree.init();
		
		$('#themedo-mediasorter-folderTree').mCustomScrollbar({
			autoHideScrollbar: true,
			setHeight: $(window).height() - 300,
	    });
		
	});

	$(window).on("load",function(){
		Core.toolbar.init();
	});
 	
	Core = {
		init : function(){
			html = '';
			html += $("#mediasorter_sidebar").html();
			$("#mediasorter_sidebar").remove();
			if($('.update-nag').length){
				$("#wp-media-grid").before('<div class="clear"></div>');
				$("#wp-media-grid").css('margin-top', '10px');
			}
			
			$("#wpbody .wrap").before(html);
			
			var tempStopResize = true;
			var themedoMinWidth = 240;
			var themedoMaxWidth = 800;

			$(".panel-left").mediasorterResize({
				handleSelector: ".mediasorter_splitter",
				resizeHeight: false,
				onDrag: function (e, $el, newWidth, newHeight, opt) {
				 // limit box size
					
				 	var x = e.pageX - $el.offset().left;
				 	
				 	if (newWidth < themedoMinWidth){

				 		if(x > themedoMinWidth - 40 ){
				 			
				 			return false;
				 			
				 		}
						 
				 		$el.css('max-width', '0');

				 		$el.css('overflow', 'hidden');

						$('.mediasorter_sidebar_fixed').css('max-width', '0');
						$('.mediasorter_sidebar_fixed').css('overflow', 'hidden');
						var $wrapAll = $('.wrap-all');
				 		$( ".mediasorter_splitter").hide() 
				 		$wrapAll.addClass('show-expand');
				 		if(!$('.wrap-all >  span').length){
				 			$('.wrap-all').prepend("<span class='mediasorter_sidebar_expand_button'></span>");
				 		}
				 		setTimeout(function(){
				 			$('.mediasorter_sidebar_expand_button').show();
				 		}, 600);	
			 			

			 			$('.mediasorter_sidebar_expand_button').on('click', function(){
							
							$(this).hide();
							$('.mediasorter_sidebar').css({'max-width': '800px', 'width': themedoMinWidth + 5+ 'px'});
							$('.mediasorter_sidebar_fixed').css({'max-width': '800px', 'width': themedoMinWidth + 5+ 'px'});
							$('.mediasorter_splitter').show();
							$wrapAll.removeClass('show-expand');

						});
				 		
				 		return false;

				 	}else if(newWidth > themedoMinWidth && $el.width > 0){
				 	
				 		
				 		$el.css('overflow', 'initial');
				 	}
				 	
				 	if(newWidth >= themedoMaxWidth){
				 			
				 		return false;
				 	}

				 	
				 	
				 	//return false;

				}
			});

			$("#wpbody .wrap").addClass("appended");
			$('.mediasorter_sidebar, .mediasorter_splitter, #wpbody .wrap').wrapAll('<div class="wrap-all"></div>');
		},

		// Vakata Jstree
		jstree : {
			init: function(){
				Core.jstree.default();

				if(localStorage.getItem('current_folder') === 'all' || localStorage.getItem('current_folder') === 'undefined' || localStorage.getItem('current_folder') == null){
					$('#menu-item-all .menu-item-bar').trigger('click');
					
				}
			},
			// Init

			default: function(){
				if ($("#themedo-mediasorter-defaultTree").length){

					$("#themedo-mediasorter-defaultTree").jstree({
						'core' : {
							'themes' : {
								'responsive': false,
								"icons":false
							}
						},
					});

					$('#themedo-mediasorter-defaultTree').on("changed.jstree", function (e, data) {
						

						if(data.node){
							//only active selected node
							var catId = data.node.li_attr['data-id'];
//							
//							console.log("Hello - " + catId);

							localStorage.setItem('current_folder', catId);
							$(".jstree-anchor.jstree-clicked").removeClass('jstree-clicked');
							$(".jstree-node.current-dir").removeClass('current-dir');
							$(".jstree-node[data-id='" + catId + "']").addClass('current-dir');
					 		$(".jstree-node[data-id='" + catId + "']").children('.jstree-anchor').addClass('jstree-clicked');

					 		if($('.jstree-anchor.need-refresh').length){

								var $mediasorter_sidebar = $('.mediasorter_sidebar');

								var backbone = mediasorterWMC.mediasorterWMCgetBackboneOfMedia ($mediasorter_sidebar);
								
							    if (backbone.browser.length > 0 && typeof backbone.view == "object") {
							        // Refresh the backbone view
							        try {
							            backbone.view.collection.props.set({ignore: (+ new Date())});
							        }catch(e) {console.log(e);};
							    }else{
							    	
							        window.location.reload();
							    }
							    $('.jstree-anchor.need-refresh').removeClass('need-refresh');

							}


					 		//trigger category on topbar
						    $('.wpmediacategory-filter').val(catId);
							$('.wpmediacategory-filter').trigger('change');
						}
						
						if($('.menu-item.current_folder').length){
							if (!$('select[name="themedo_mediasorter_folder"]').length) {//grid list
								$('.menu-item.current_folder').removeClass('current_folder');
							}
						}
					 	
					});
				}
			},
			// Default			
		},
		//Jstree

		sweetAlert: {
			delete : function(node){
				
				var id = 0;
				if (Array.isArray(node)){
					id = node[0].data("id");;
				}else{
					id = node.data("id");;
				}
			

                var li = $('#menu-item-'+id);

                if($(li).next().find(".menu-item-data-parent-id").length && $(li).next().find(".menu-item-data-parent-id").val() == id)
				{
					Core.deleteFolderOops();
				}
				else
				{
					Core.deleteFolderConfirm(id);
				}
			}
		},
		//Sweet Alert
		
		deleteFolderOops: function(){
			
			var html = '<div id="mediasorter_be_confirm">';
					html += '<div class="confirm_inner">';
						html += '<div class="desc_holder">';
							html += '<h3>' + mediasorter_translate.oops + '</h3>';
							html += '<p>' + mediasorter_translate.folder_are_sub_directories + '</p>';
						html += '</div>';
						html += '<div class="links_holder">';
							html += '<a class="no" href="#">' + mediasorter_translate.cancel + '</a>';
						html += '</div>';
					html += '</div>';
				html += '</div>';
							
							
			
			$('#mediasorter_be_confirm').remove();
			$('body').prepend(html);
			
			var confirm 		= $('#mediasorter_be_confirm');
			confirm.addClass('opened folder_delete');
			var cancelActionBtn	= $('#mediasorter_be_confirm').find('a.no');

			
			cancelActionBtn.on('click', function () {
				confirm.removeClass();
				
				$('#mediasorter_be_confirm').remove();
				return false;
			});
		},
		
		deleteFolderConfirm: function(e_id){

			var html = '<div id="mediasorter_be_confirm">';
					html += '<div class="confirm_inner">';
						html += '<div class="desc_holder">';
							html += '<h3>' + mediasorter_translate.are_you_sure + '</h3>';
							html += '<p>' + mediasorter_translate.not_able_recover_folder + '</p>';
						html += '</div>';
						html += '<div class="links_holder">';
							html += '<a class="yes" href="#">' + mediasorter_translate.yes_delete_it + '</a>';
							html += '<a class="no" href="#">' + mediasorter_translate.cancel + '</a>';
						html += '</div>';
					html += '</div>';
				html += '</div>';
							
							
			$('#mediasorter_be_confirm').remove();
			$('body').prepend(html);
			
			var confirm 		= $('#mediasorter_be_confirm');
			confirm.addClass('opened folder_delete');
			var doActionBtn		= $('#mediasorter_be_confirm').find('a.yes');
			var cancelActionBtn	= $('#mediasorter_be_confirm').find('a.no');

			
			doActionBtn.off().on('click', function (e) {
				e.preventDefault();
				
				themedo_trigger_folder.delete(e_id);
				
				$('#mediasorter_be_confirm').remove();
				return false;
			});
			cancelActionBtn.on('click', function () {
				confirm.removeClass();
				
				$('#mediasorter_be_confirm').remove();
				return false;
			});
		},

		toolbar: {
			init: function(){
				Core.toolbar.create();
				Core.toolbar.delete();
			},
			//Init

			create: function(){
				if ($(".js_mediasorter_create").length){
					$(".js_mediasorter_create").on("click",function(){

						var ref = $('#themedo-mediasorter-folderTree').jstree(true),
								sel,
								type = $(this).data("type");
						sel = ref.create_node(null, {"type":type});

						if(sel) {
							ref.edit(sel);
						}
						
					});
				}				
			},
			//Create

			delete: function(){
				
				if ($(".js_mediasorter_delete").length){
					$(".js_mediasorter_delete").on("click",function(){
						var ref = $('#themedo-mediasorter-folderTree .current_folder');
								
						if(!ref.length) { return false; }
						Core.sweetAlert.delete(ref);				
					});
				}				
			},
			//Delete
			
		},
		//Toolbar

		// Tipped Plugin
		tooltip : { 
			init: function(){
				if ($(".js_mediasorter_tipped").length){
					Tipped.create(".js_mediasorter_tipped",function(element){
						return {
							title : $(element).data("title"),
							content : $(element).data("content"),
						};
					},{
						skin: 'blue',
						maxWidth: 250,
					});
				}
			}
		},
		//Tooltip
	}

})( jQuery );
