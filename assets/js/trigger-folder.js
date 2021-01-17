
var themedo_trigger_folder = {};


(function ($) {
	"use strict";
	
	var folderInContent = {
		
		render: function(data){
			
			
			var self = this;
			var html = '';

			if(data.length && html !== ''){

				var folder_container = '<div class="themedo-mediasorter-container"><ul></ul></div>';
				$('.attachments').before(folder_container); 
				data.forEach(function(item){

					html += '<li data-id="'+item.term_id+'"><div class="item jstree-anchor"><span class="icon"></span><span class="item-containt">'+
							'<span class="folder-name">' + item.name + '</span></span></div></li>';

				});
				$('.themedo-mediasorter-container ul').html(html);
				self.action();
			}
		},
		
		
		action: function(){
			$('.themedo-mediasorter-container .item').on('click', function(){
				$('.themedo-mediasorter-container .item').removeClass('active');
				$(this).addClass('active');
			});
			$('.themedo-mediasorter-container .item').on('dblclick', function(){
				var folder_id = $(this).parent().data('id');
				$('#menu-item-' + folder_id + ' .jstree-anchor').trigger('click');
			});

			$('.themedo-mediasorter-container .item').on({
				mouseenter: function() {
					var $this = $(this);
					var parentWidth = $this.find('.item-containt').innerWidth();
					var childWidth = $this.find('.folder-name').innerWidth();
					var title = 	$this.find('.folder-name').text();
					 if (parentWidth < (childWidth + 16) ) {
						 $this.tooltip({
							 title: title,
							 placement: "bottom",
						 });

						 $this.tooltip('show');
					 }
				},

				mouseleave: function() {
					var $this = $(this);
					$this.tooltip('hide');
				}
			});
		}
		
	};
	
	
	var Popup = {
		
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
		
		errorPopup: function(text){
			
			var html = '<div id="mediasorter_be_confirm">';
					html += '<div class="confirm_inner">';
						html += '<div class="desc_holder">';
							html += '<h3>' + mediasorter_translate.error + '</h3>';
							html += '<p>' + text + '</p>';
						html += '</div>';
						html += '<div class="links_holder">';
							html += '<a class="yes green" href="#">' + mediasorter_translate.reload + '</a>';
						html += '</div>';
					html += '</div>';
				html += '</div>';
							
							
			
			$('#mediasorter_be_confirm').remove();
			$('body').prepend(html);
			
			var confirm 		= $('#mediasorter_be_confirm');
			confirm.addClass('opened folder_delete');
			var cancelActionBtn	= $('#mediasorter_be_confirm').find('a.yes');

			
			cancelActionBtn.on('click', function () {
				confirm.removeClass();
				$('#mediasorter_be_confirm').remove();
				location.reload();
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
		}
	};

	

	themedo_trigger_folder.jQueryExtensions = function () {
		
		

		$.fn.extend({

			move_folder: function () {
				return this.each(function () {
					var item = $(this),
						depth = parseInt(item.menuItemDepth(), 10),
						parentDepth = depth - 1,
						parent = item.prevAll('.menu-item-depth-' + parentDepth).first();
					var new_parent = 0;
					if (0 !== depth) {
						new_parent = parent.find('.menu-item-data-db-id').val();
					}
					


					var current = item.find('.menu-item-data-db-id').val();
					themedo_trigger_folder.updateFolderList(current, new_parent, 'move');
				});
			}

		});
	}();


	themedo_trigger_folder.rename = function (current, new_name) {

		mediasorterWMC.mediasorter_begin_loading();
		
		

		var jdata = {
			'action': 'mediasorter_ajax_update_folder_list',
			'current': current,
			'new_name': new_name,
			'type': 'rename'
		};

		$.post(ajaxurl, jdata, function (response) {

			if (response == 'error') {

				Popup.errorPopup(mediasorter_translate.this_folder_is_already_exists);

			}

		}).fail(function () {
			
			Popup.errorPopup(mediasorter_translate.error_occurred);
			

		}).complete(function () {
			mediasorterWMC.mediasorter_finish_loading();
		});


	};

	themedo_trigger_folder.delete = function (current) {

		mediasorterWMC.mediasorter_begin_loading();

		var data = {
			'action': 'mediasorter_ajax_delete_folder_list',
			'current': current,
		};
		//2. Delete folder
		$.post(ajaxurl, data, function (response) {
			if (response == 'error') {
				
				Popup.errorPopup(mediasorter_translate.folder_cannot_be_delete);
			}


		}).fail(function () {
			
			Popup.errorPopup(mediasorter_translate.error_occurred);
			
		}).success(function (response) {

			mediasorterWMC.updateCountAfterDeleteFolder(response);
			$('.menu-item.uncategory .jstree-anchor').addClass('need-refresh');

			if (current == localStorage.getItem("current_folder")) {
				localStorage.removeItem("current_folder");
			}

			var parent_id = $('#menu-item-' + current).find('.menu-item-data-parent-id').val();

			if (parent_id) {

				if (!$(".menu-item .menu-item-data-parent-id").filter(function () {

					return ($(this).val() == parent_id);

				}).length) {

					$("#menu-item-" + parent_id + " .sub_opener").removeClass('open close');
				}

			}

			$('#menu-item-' + current).remove();

			mediasorterWMC.mediasorter_finish_loading();
		});


	};

	themedo_trigger_folder.new = function (name, parent) {

		mediasorterWMC.mediasorter_begin_loading();

		var data = {
			'action': 'mediasorter_ajax_update_folder_list',
			'new_name': name,
			'parent': parent,
			'folder_type': 'default',
			'type': 'new'
		};

		//2. Delete folder
		$.post(ajaxurl, data, function (response) {
			if (response == 'error') {
				
				Popup.errorPopup(mediasorter_translate.folder_cannot_be_delete);
			}



		}).fail(function () {
			
			Popup.errorPopup(mediasorter_translate.error_occurred);
			
		}).success(function (response) {

			mediasorter_taxonomies.folder.term_list.push({ term_id: response.data.term_id, term_name: "new tmp folder" });
			var $mediasorter_sidebar = $('.mediasorter_sidebar');
			var backbone = mediasorterWMC.mediasorterWMCgetBackboneOfMedia($mediasorter_sidebar);

			if (typeof backbone.view === "object") {
				var mediasorter_Filter = backbone.view.toolbar.get("folder-filter");
				if (typeof backbone.view === "object") {
					mediasorter_Filter.createFilters();
				}
			}

			var $new_option = $("<option></option>").attr("value", response.data.term_id).text('new tmp folder');
			$(".wpmediacategory-filter").append($new_option);
			$(".jstree-anchor.jstree-clicked").removeClass('jstree-clicked');
			


			themedo_trigger_folder.update_folder_position();
			mediasorterWMC.mediasorter_finish_loading();
		});


	};

	themedo_trigger_folder.updateFolderList = function (current, new_parent, type) {

		var jdata = {
			'action': 'mediasorter_ajax_update_folder_list',
			'current': current,
			'new_name': 0,
			'parent': new_parent,
			'type': type,
			'folder_type': 'folder'
		};

		$.post(ajaxurl, jdata, function (response) {

			if (response == 'error') {

				Popup.errorPopup(mediasorter_translate.this_folder_is_already_exists);
				

			} else {
				themedo_trigger_folder.update_folder_position();

				$('.need-refresh').trigger("click");
			}
		}).fail(function () {

			Popup.errorPopup(mediasorter_translate.error_occurred);
			
		});


	};

	themedo_trigger_folder.update_folder_position = function () {

		mediasorterWMC.mediasorter_begin_loading();
		var result = "";
		var str = '';
		$("#themedo-mediasorter-folderTree .menu-item-data-db-id").each(function () {
			str += '0'

			if (result != "") {
				result = result + "|";
			}
			result = result + $(this).val() + "," + str;

		});

		var data = {
			'action': 'mediasorter_ajax_update_folder_position',
			'result': result
		}

		// 3. Update position for folder order
		$.post(ajaxurl, data, function (response) {
			if (response == 'error') {
				
				var text = mediasorter_translate.something_not_correct + mediasorter_translate.this_page_will_reload;
				Popup.errorPopup(text);
				
			}
			mediasorterWMC.mediasorter_finish_loading();
		}).fail(function () {
			
			Popup.errorPopup(mediasorter_translate.error_occurred);
			
		}).success(function (response) {

			var current_folder_id = $('.wpmediacategory-filter').val();
			$('#menu-item-' + current_folder_id + ' .jstree-anchor').addClass('need-load-children');
			$('#menu-item-' + current_folder_id + ' .jstree-anchor').trigger('click');

		});
	};

	themedo_trigger_folder.filter_media = function ($element) {
		
		if ($element == null) {

		} else {

			
			var catId = $element.closest('.menu-item').data('id');
			if ($('.need-refresh').length) {

				var $mediasorter_sidebar = $('.mediasorter_sidebar');

				var backbone = mediasorterWMC.mediasorterWMCgetBackboneOfMedia($mediasorter_sidebar);

				if (backbone.browser.length > 0 && typeof backbone.view == "object") {
					// Refresh the backbone view
					try {
						backbone.view.collection.props.set({ ignore: (+ new Date()) });
					} catch (e) { console.log(e); };
				} else {
					
				}
				$('.need-refresh').removeClass('need-refresh');

			}
			//trigger category on topbar
			$('.wpmediacategory-filter').val(catId);
			$('.wpmediacategory-filter').trigger('change');
			$('.attachments').css('height', 'auto');


		}

	};

	themedo_trigger_folder.getChildFolder = function (folder_id) {

		if ($('.themedo-mediasorter-container').length) {

			$('.themedo-mediasorter-container').remove();

		}

		var data = {
			'action': 'mediasorter_ajax_get_child_folders',
			'folder_id': folder_id,
		};

		$.post(ajaxurl, data, function (response) {


		}).fail(function () {


		}).success(function (response) {

			folderInContent.render(response.data);
		});

	};

	$('#themedo-mediasorter-folderTree .jstree-anchor').dblclick(function (e) {
		e.preventDefault();
	});
	
	

	var THEMEDO_DELAY = 200, themedo_clicks = true, themedo_timer = null;
	//check truong hop click and double click
	$(document).on('click', '.mediasorter_sidebar .jstree-anchor', function () {

		var $this = $(this), folder_id = $this.closest('.menu-item').data('id');
		
		if (themedo_clicks !== false) {
			
			themedo_clicks = false;
			
			if ($('select[name="themedo_mediasorter_folder"]').length) {//list mode
				$('select[name="themedo_mediasorter_folder"]').val(folder_id);
				if ($('.mediasorter_be_loader').hasClass('loading')) {
					return;
				}
				mediasorterWMC.mediasorter_begin_loading();
				themedo_timer = setTimeout(function () {
					var form_data = $('#posts-filter').serialize();
					$.ajax({
						url: mediasorterConfig.upload_url,
						type: 'GET',
						data: form_data,
					})
						.done(function (html) {
							window.history.pushState({}, "", mediasorterConfig.upload_url + '?' + form_data);
							themedo_after_loading_media(html, folder_id);
							themedo_clicks = true;
						})
						.fail(function () {
							mediasorterWMC.mediasorter_finish_loading();
							console.log("error");
							themedo_clicks = true;
						});
					oldCurrentFolder = localStorage.getItem("current_folder");

				}, THEMEDO_DELAY);
			} else {
				themedo_timer = setTimeout(function () {
					themedo_trigger_folder.filter_media($this);

					if (oldCurrentFolder != localStorage.getItem("current_folder") || $this.hasClass('need-load-children')) {
						themedo_trigger_folder.getChildFolder(folder_id);
						$this.removeClass('need-load-children');
					}
					oldCurrentFolder = localStorage.getItem("current_folder");

					themedo_clicks = true;

				}, THEMEDO_DELAY);
			}
		}
//		else {
//			clearTimeout(themedo_timer);    //prevent single-click action
//			$('.js_mediasorter_rename').trigger('click');  //perform double-click action
//			themedo_clicks = 0;             //after action performed, reset counter
//		}
	});
	
	
	$(document).on('click', '.pagination-links a', function (event) {
		event.preventDefault();
		var $this = $(this);
		if ($('.mediasorter_be_loader').hasClass('loading')) {
			return;
		}
		mediasorterWMC.mediasorter_begin_loading();
		$.ajax({
			url: $this.attr('href'),
			type: 'GET',
			data: {},
		})
			.done(function (html) {
				window.history.pushState({}, "", $this.attr('href'));
				themedo_after_loading_media(html, $('select[name="themedo_mediasorter_folder"]').val());
			})
			.fail(function () {
				mediasorterWMC.mediasorter_finish_loading();
				console.log("error");
			});
		return false;
	});
	
	
	$(document).on('submit', '#posts-filter', function (event) {
		event.preventDefault();
		var $this = $(this);
		if ($('.mediasorter_be_loader').hasClass('loading')) {
			return;
		}
		mediasorterWMC.mediasorter_begin_loading();
		var form_data = $('#posts-filter').serialize();
		$.ajax({
			url: mediasorterConfig.upload_url,
			type: 'GET',
			data: form_data,
		})
			.done(function (html) {
				window.history.pushState({}, "", mediasorterConfig.upload_url + '?' + form_data);
				themedo_after_loading_media(html, $('select[name="themedo_mediasorter_folder"]').val());
			})
			.fail(function () {
				mediasorterWMC.mediasorter_finish_loading();
				console.log("error");
			});
		return false;
	});
	
	function themedo_after_loading_media(html, folder_id) {
		$('.wrap').html($(html).find('.wrap').html());
		$('#folders-to-edit li').removeClass('current_folder');
		$('ul.jstree-container-ul li').removeClass('current-dir current_folder');

		
		
		//set curret folder
		if (folder_id == '' || folder_id == null) {
			$('#menu-item-all').addClass('current-dir');
		} else if (folder_id == '-1') {
			$('#menu-item--1').addClass('current-dir');
		} else {
			$('#menu-item-' + folder_id).addClass('current_folder');
		}
		//set folder select
		$.each(mediasorter_taxonomies.folder.term_list, function (index, el) {
			$('.wpmediacategory-filter').append('<option value="' + el.term_id + '">' + el.term_name + '</option>');
		});
		$('.wpmediacategory-filter').val(folder_id);
		//add behavior
		var drag_item = $("#themedo-mediasorter-attachment");
		var text_drag = mediasorter_translate.move_1_file;
		$.each($('table.wp-list-table tr'), function (index, el) {
			$(el).drag("start", function () {
				var selected_files = $('.wp-list-table input[name="media[]"]:checked');
				if (selected_files.length > 0) {
					text_drag = mediasorter_translate.Move + ' ' + selected_files.length + ' ' + mediasorter_translate.files;
				}
				
				drag_item.html(text_drag);
				drag_item.show();
				$('body').addClass('themedo-draging');
			})
				.drag("end", function () {
					drag_item.hide();
					$('body').removeClass('themedo-draging');
					text_drag = mediasorter_translate.move_1_file;
				})
				.drag(function (ev, dd) {
					var id = $(this).attr("id");
					
					if(id != 'undefined'){
						id = id.match(/post-([\d]+)/);
						drag_item.data("id", id[1]);
						drag_item.css({
							"top": ev.clientY - 15,
							"left": ev.clientX - 15,
						});
					}
				});
		});
		//remove loading
		mediasorterWMC.mediasorter_finish_loading();
	}
})(jQuery);