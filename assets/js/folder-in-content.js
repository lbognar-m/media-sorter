(function($){
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

})(jQuery);