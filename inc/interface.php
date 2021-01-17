<?php

class Mediamatic_Interface {


	private $version;
	private $mediasorter_free = true;
	
	public function __construct() 
	{
		$this->version 		= MEDIASORTER_PLUGIN_NAME;

		add_filter('restrict_manage_posts', array($this, 'restrictManagePosts'));
		add_filter('posts_clauses', array($this, 'postsClauses'), 10, 2);

		add_action( 'admin_enqueue_scripts', array($this,'enqueue_styles' ));
		add_action( 'admin_enqueue_scripts', array($this,'enqueue_scripts' ));
		add_action( 'load-upload.php', array($this,'scripts_for_media_upload' ));
		add_action( 'init', array($this,'mediasorterAddFolderToAttachments' ));
		add_action( 'admin_footer-upload.php', array($this,'mediasorterInitMediaManager'));
		add_action( 'wp_ajax_mediasorter_ajax_update_folder_list', array($this,'mediasorterAjaxUpdateFolderListCallback' ));
		add_action( 'wp_ajax_mediasorter_ajax_delete_folder_list', array($this,'mediasorterAjaxDeleteFolderListCallback' ));
		add_action( 'wp_ajax_mediasorter_ajax_update_folder_position', array($this,'mediasorterAjaxUpdateFolderPositionCallback' ));
		add_action( 'wp_ajax_mediasorter_ajax_get_child_folders', array($this,'mediasorterAjaxGetChildFoldersCallback' ));
		add_action( 'wp_ajax_mediasorterAjaxSaveSplitter', array($this,'mediasorterAjaxSaveSplitter' ));
		add_filter( 'pre-upload-ui', array($this, 'mediasorterPreUploadInterface'));
		
		
		//Support Elementor
        if (defined('ELEMENTOR_VERSION')) {
            add_action('elementor/editor/after_enqueue_scripts', [$this, 'mediasorterEnqueueMediaAction']);
        }
		
	}
	
	public function mediasorterEnqueueMediaAction() {

        $suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '';

		$taxonomy = MEDIASORTER_FOLDER;
		$taxonomy = apply_filters( 'mediasorter_taxonomy', $taxonomy );

		
		$dropdown_options = array(
			'taxonomy'        => $taxonomy,
			'hide_empty'      => false,
			'hierarchical'    => true,
			'orderby'         => 'name',
			'show_count'      => true,
			'walker'          => new Mediamatic_Walker_Category_Mediagridfilter(),
			'value'           => 'id',
			'echo'            => false
		);
		
		$attachment_terms 	= wp_dropdown_categories( $dropdown_options );
		$attachment_terms 	= preg_replace( array( "/<select([^>]*)>/", "/<\/select>/" ), "", $attachment_terms );

		echo '<script type="text/javascript">';
		echo '/* <![CDATA[ */';
		echo 'var mediasorter_folder = "'. MEDIASORTER_FOLDER .'";';
		echo 'var mediasorter_taxonomies = {"folder":{"list_title":"' . html_entity_decode( esc_html__( 'All categories' , MEDIASORTER_TEXT_DOMAIN ), ENT_QUOTES, 'UTF-8' ) . '","term_list":[{"term_id":"-1","term_name":"'.esc_html__( 'Uncategorized' , MEDIASORTER_TEXT_DOMAIN ).'"},' . substr( $attachment_terms, 2 ) . ']}};';
		echo '/* ]]> */';
		echo '</script>';

		wp_enqueue_script( 'mediasorter-admin-topbar', plugins_url( 'assets/js/mediasorter-admin-topbar' . $suffix . '.js', dirname(__FILE__) ), array( 'media-views' ), $this->plugin_version, true );
		wp_enqueue_style( 'mediasorter-admin', MEDIASORTER_ASSETS_URL . 'css/admin.css', array(), $this->version, 'all' );
    }
	
	
	public function postsClauses($clauses, $query)
	{
		global $wpdb;
		
		if (isset($_GET['themedo_mediasorter_folder'])) 
		{
			$folder = sanitize_text_field($_GET['themedo_mediasorter_folder']);
			if (!empty($folder) != '') 
			{
				$folder = (int)$folder;
				if ($folder > 0) 
				{
					$clauses['where'] .= ' AND ('.$wpdb->prefix.'term_relationships.term_taxonomy_id = '.$folder.')';
					$clauses['join'] .= ' LEFT JOIN '.$wpdb->prefix.'term_relationships ON ('.$wpdb->prefix.'posts.ID = '.$wpdb->prefix.'term_relationships.object_id)';
				} 
				else 
				{
					//to improve performance: set default folder for files when add new
					$folders = get_terms(MEDIASORTER_FOLDER, array(
						'hide_empty' => false
					));
					$folder_ids = array();
					foreach ($folders as $k => $folder) 
					{
						$folder_ids[] = $folder->term_id;
					}
					
					$folder_ids = esc_sql($folder_ids);
					
					$files_have_folder_query = "SELECT `ID` FROM ".$wpdb->prefix."posts LEFT JOIN ".$wpdb->prefix."term_relationships ON (".$wpdb->prefix."posts.ID = ".$wpdb->prefix."term_relationships.object_id) WHERE (".$wpdb->prefix."term_relationships.term_taxonomy_id IN (".implode(', ', $folder_ids)."))";
					$clauses['where'] .= " AND (".$wpdb->prefix."posts.ID NOT IN (".$files_have_folder_query."))";
				}
			}
		}
		
		return $clauses;
	}
	

	public function restrictManagePosts()
	{
	    $scr 	= get_current_screen();
	    if ($scr->base !== 'upload') 
		{
	        return;
	    }
	    echo '<select id="media-attachment-filters" class="wpmediacategory-filter attachment-filters" name="themedo_mediasorter_folder"></select>';
	}
	
	
	public function enqueue_styles() 
	{
		wp_enqueue_style( 'mediasorter-admin', MEDIASORTER_ASSETS_URL . 'css/admin.css', array(), $this->version, 'all' );
	}

	
	
	public function enqueue_scripts() 
	{
		wp_register_script( 'mediasorter-util', MEDIASORTER_ASSETS_URL . 'js/mediasorter-util.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( 'mediasorter-util', 'mediasorter_translate', $this->get_strings() );
		wp_enqueue_script( 'mediasorter-util' );
		
		wp_enqueue_script( 'mediasorter-admin', MEDIASORTER_ASSETS_URL . 'js/mediasorter-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'mediasorter-upload-event', MEDIASORTER_ASSETS_URL . 'js/hook-add-new-upload.js', array( 'jquery' ), $this->version, false );
		
		
	}
	

	public function scripts_for_media_upload() 
	{
		//Get mode
		$mode 	= get_user_option( 'media_library_mode', get_current_user_id() ) ? get_user_option( 'media_library_mode', get_current_user_id() ) : 'grid';
		$modes 	= array( 'grid', 'list' );

		if ( isset( $_GET['mode'] ) && in_array( $_GET['mode'], $modes ) ) {
			$mode = sanitize_text_field($_GET['mode']);
			update_user_option( get_current_user_id(), 'media_library_mode', $mode );
		}

		//Load Scripts And Styles for Media Upload		
		wp_enqueue_style( 'mCustomScrollbar', MEDIASORTER_ASSETS_URL . 'css/scrollbar.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'jstree', MEDIASORTER_ASSETS_URL . 'css/jstree.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'mediasorter-style', MEDIASORTER_ASSETS_URL . 'css/style.css', array(), $this->version, 'all' );
		
		
		// Javascript Codes
		// Libraries
		wp_enqueue_script( 'jstree', MEDIASORTER_ASSETS_URL . 'js/library/jstree.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'resizable',MEDIASORTER_ASSETS_URL . 'js/library/resizable.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'scrollbar', MEDIASORTER_ASSETS_URL . 'js/library/scrollbar.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'mediasorter-drag', MEDIASORTER_ASSETS_URL . 'js/library/drag.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'mediasorter-drop', MEDIASORTER_ASSETS_URL . 'js/library/drop.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'bootstrap', MEDIASORTER_ASSETS_URL . 'js/library/bootstrap.js', array( 'jquery' ), $this->version, false );
		
		wp_enqueue_script( 'mediasorter-trigger', MEDIASORTER_ASSETS_URL . 'js/trigger-folder.js', array( 'jquery' ), $this->version, false );
		
		wp_localize_script(
			'mediasorter-trigger',
			'mediasorterConfig',
			array(
				'upload_url' 		=> admin_url('upload.php'),
			)
		);
		
		wp_enqueue_script( 'mediasorter-upload', MEDIASORTER_ASSETS_URL . 'js/mediasorter-upload.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'mediasorter-folder', MEDIASORTER_ASSETS_URL . 'js/folder.js', array( 'jquery' ), $this->version, false );
		
		wp_localize_script(
			'mediasorter-folder',
			'mediasorterConfig',
			array(
				'pluginUrl' 		=> MEDIASORTER_URL,
				'upload_url' 		=> admin_url('upload.php'),
				'svgFolder' 		=> '<img src="'. MEDIASORTER_URL.'/assets/img/folder.svg" class="mediasorter_be_svg" />',
				
			)
		);
		
		
		
		if ($mode === 'grid')
		{
			wp_enqueue_script( 'mediasorter-upload-library', MEDIASORTER_ASSETS_URL . 'js/hook-library-upload.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'mediasorter-upload-grid', MEDIASORTER_ASSETS_URL . 'js/mediasorter-upload-grid.js', array( 'jquery' ), $this->version, false );
			
			wp_localize_script(
				'mediasorter-upload-library',
				'mediasorterConfig1',
				array(
					'nonce' 		=> wp_create_nonce('ajax-nonce')
				)
			);
			
			wp_localize_script(
				'mediasorter-upload-grid',
				'mediasorterConfig2',
				array(
					'nonce' 		=> wp_create_nonce('ajax-nonce')
				)
			);
		}
		else
		{
			wp_enqueue_script( 'mediasorter-upload-list', MEDIASORTER_ASSETS_URL . 'js/mediasorter-upload-list.js', array( 'jquery' ), $this->version, false );
			wp_localize_script(
				'mediasorter-upload-list',
				'mediasorterConfig3',
				array(
					'upload_url' 		=> admin_url('upload.php'),
					'current_folder' 	=> ((isset($_GET['themedo_mediasorter_folder'])) ? sanitize_text_field($_GET['themedo_mediasorter_folder']) : ''),
					'no_item_html' 		=> '<tr class="no-items"><td class="colspanchange" colspan="'.apply_filters('mediasorter_noitem_colspan', 6).'">'.esc_html__('No media files found.', MEDIASORTER_TEXT_DOMAIN).'</td></tr>',
					'item' 				=> esc_html__('item', MEDIASORTER_TEXT_DOMAIN),
					'items' 			=> esc_html__('items', MEDIASORTER_TEXT_DOMAIN),
					'nonce' 			=> wp_create_nonce('ajax-nonce'),
				)
			);
		}

	}
	

	public function mediasorterConvertTreeToFlatArray($array) 
	{
		$result = array();
		foreach($array as $key => $row) 
		{
			$item 			= new stdClass();
			$item->term_id	= $row->term_id;
			$item->name		= $row->name;
			$item->parent	= $row->parent;
			$item->count	= $row->count;
			$result[] 		= $item;
			
			if(count($row->children) > 0) 
			{
				$result = array_merge($result,$this->mediasorterConvertTreeToFlatArray($row->children));
			}
		}

		return $result;
	}
	
	
	public function mediasorterInitMediaManager($hook)
	{
		$all_count 					= wp_count_posts('attachment')->inherit;
		$uncategory_count 			= Mediamatic_Topbar::get_uncategories_attachment();
		$tree 						= $this->mediasorterTermTreeArray(MEDIASORTER_FOLDER, 0); 
		$folders 					= $this->mediasorterConvertTreeToFlatArray($tree);
		$sidebar_width 	= get_option('mediasorter_sidebar_width', 300);
		?>
			<div id="mediasorter_sidebar" style="display: none;">

				<div class="mediasorter_sidebar panel-left"
					<?php echo ($sidebar_width ? ' style="width: '. $sidebar_width .'px;"' : '') ?>
				>
					<div class="mediasorter_sidebar_fixed"
						<?php echo ($sidebar_width ? ' style="width: '. $sidebar_width .'px;"' : '') ?>
					>

						<input type="hidden" id="mediasorter_terms">
						
						<div class="mediasorter_sidebar_header">
							<h1 class="mediasorter_main_title"><?php esc_html_e('Folders', 'mediasorter');?></h1>
							<a class="mediasorter_main_add_new js_mediasorter_tipped new-folder">
								<img src="<?php echo MEDIASORTER_URL; ?>/assets/img/folder.svg" class="mediasorter_be_svg" />
								<?php esc_html_e('Add New', MEDIASORTER_TEXT_DOMAIN);?>
							</a>
						</div>
						
						
						
						<div class="mediasorter_toolbar">
							<button type="button" class="mediasorter_main_button_icon js_mediasorter_tipped js_mediasorter_rename button media-button" data-title="<?php esc_html_e('Rename', MEDIASORTER_TEXT_DOMAIN);?>">
							<svg class="a-s-fa-Ha-pa" x="0px" y="0px" width="24px" height="24px" viewBox="0 0 24 24" focusable="false" fill="#8f8f8f"><path d="M0 0h24v24H0z" fill="none"></path><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM6 17v-2.47l7.88-7.88c.2-.2.51-.2.71 0l1.77 1.77c.2.2.2.51 0 .71L8.47 17H6zm12 0h-7.5l2-2H18v2z"></path></svg>
							<span><?php esc_html_e('Rename', MEDIASORTER_TEXT_DOMAIN);?></span><span class="opacity0"><?php esc_html_e('Rename', MEDIASORTER_TEXT_DOMAIN);?></span></button>
							<button type="button" class="mediasorter_main_button_icon js_mediasorter_tipped js_mediasorter_delete button media-button"><svg width="24px" height="24px" viewBox="0 0 24 24" fill="#8f8f8f" focusable="false" class=""><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path><path d="M0 0h24v24H0z" fill="none"></path></svg><span><?php esc_html_e('Delete', MEDIASORTER_TEXT_DOMAIN);?></span><span class="opacity0"><?php esc_html_e('Delete', MEDIASORTER_TEXT_DOMAIN);?></span></button>

						</div>
						
						<div class="mediasorter_be_loader">
							<span class="loader_process">
								<span class="ball"></span>
								<span class="ball"></span>
								<span class="ball"></span>
							</span>
						</div>
						
						<div id="themedo-mediasorter-defaultTree" class="mediasorter_tree">
							<ul>
								<li id="menu-item-all" data-jstree='{"selected":true}' id="menu-item-all" data-id="all" data-number="<?php echo esc_attr($all_count); ?>" class="menu-item">
									<img src="<?php echo MEDIASORTER_URL; ?>/assets/img/folder.svg" class="mediasorter_be_svg" />
									<span class="item-title title"><?php esc_html_e('All files', MEDIASORTER_TEXT_DOMAIN);?></span>
								</li>
								
								<?php
								$cat_count = $uncategory_count? "data-number={$uncategory_count}" : '';
								?>
								
								<li id="menu-item--1" data-jstree='{"icon":"icon-archive"}' id="menu-item--1" data-id="-1" <?php echo esc_html($cat_count); ?> class="menu-item uncategory">
									<img src="<?php echo MEDIASORTER_URL; ?>/assets/img/folder.svg" class="mediasorter_be_svg" />
									<span class="item-title"><?php esc_html_e('Uncategorized', MEDIASORTER_TEXT_DOMAIN);?></span>
								</li>
								
								
							</ul>
						</div>
						<!-- /#themedo-mediasorter-defaultTree -->

						<div id="themedo-mediasorter-folderTree" class="mediasorter_tree jstree-default">
							<?php
							$this->buildFolder($folders);
							?>
						</div>
					</div>
					<!-- #themedo-mediasorter-folderTree -->
				</div>
				<div class="mediasorter_splitter">
					<span class="mm_holder">
						<span class="a1"></span>
						<span class="a2"></span>
						<span class="a3"></span>
					</span>
				</div>
				<!-- .mediasorter_sidebar -->
			</div>
		<?php
	}


 
	private function mediasorterFindDepth($folder, $folders, $depth = 0)
	{
	    if ($folder->parent != 0) 
		{
	        $depth 		= $depth + 1;
	        $parent 	= $folder->parent;
	        $find 		= array_filter($folders, function ($arr) use ($parent) 
							{
								if ($arr->term_id == $parent) 
								{
									return $arr;
								} 
								else 
								{
									return null;
								}
							});
			
	        if (is_null($find)) 
			{
	            return $depth;
	        } 
			else 
			{
	            foreach ($find as $k2 => $v2) 
				{
	                return $this->mediasorterFindDepth($v2, $folders, $depth);
	            }
	        }
	    } 
		else 
		{
	        return $depth;
	    }
	}
	

	private function buildFolder($folders)
	{
		$orders = array();	
	    foreach ($folders as $key => $row) 
		{
	        $orders[$key] = $key;
	    }
	    array_multisort($orders, SORT_ASC, $folders);

	    echo '<form action="javascript:void(0);" id="update-folders" enctype="multipart/form-data" method="POST"><ul id="folders-to-edit" class="menu">';
	    foreach ($folders as $k => $folder) {
	        $depth = $this->mediasorterFindDepth($folder, $folders);
			
			$folder_count = $folder->count?  "data-number={$folder->count}" : '';
			
	        ?>
	        <li id="menu-item-<?php echo esc_attr($folder->term_id); ?>" data-id="<?php echo esc_attr($folder->term_id); ?>" <?php echo esc_html($folder_count) ?> class="menu-item menu-item-depth-<?php echo esc_html($depth); ?>">
				<span class="sub_opener"><span></span></span>
	            <div class="menu-item-bar jstree-anchor">
	                <div class="menu-item-handle">
						<img src="<?php echo MEDIASORTER_URL; ?>/assets/img/folder.svg" class="mediasorter_be_svg" />
	                    <span class="item-title"><span class="menu-item-title"><?php echo esc_html($folder->name); ?></span>
						
	                </div>
	            </div>
				<span class="action_button">
					<span class="a1"></span>
					<span class="a2"></span>
					<span class="a3"></span>
				</span>
	            <ul class="menu-item-transport"></ul>
	            <input class="menu-item-data-db-id" type="hidden" name="menu-item-db-id[<?php echo esc_html($folder->term_id); ?>]" value="<?php echo esc_html($folder->term_id); ?>">
	            <input class="menu-item-data-parent-id" type="hidden" name="menu-item-parent-id[<?php echo esc_html($folder->term_id); ?>]" value="<?php echo esc_html($folder->parent); ?>" />
					
				
	        </li>
	        <?php
	    }

		
		echo '</ul></form>';
	}

	
	public function mediasorterAddFolderToAttachments()
	{
		register_taxonomy(	MEDIASORTER_FOLDER, 
			array( "attachment" ), 
			array(  "hierarchical" 		=> true, 
				    "labels"			=> array(
						'name'          		=> esc_html__('Folder', MEDIASORTER_TEXT_DOMAIN),
						'singular_name' 		=> esc_html__('Folder', MEDIASORTER_TEXT_DOMAIN),
						'add_new_item'			=> esc_html__('Add New Folder', MEDIASORTER_TEXT_DOMAIN),
						'edit_item' 			=> esc_html__('Edit Folder', MEDIASORTER_TEXT_DOMAIN),
						'new_item' 				=> esc_html__('Add New Folder', MEDIASORTER_TEXT_DOMAIN),
						'search_items' 			=> esc_html__('Search Folder', MEDIASORTER_TEXT_DOMAIN),
						'not_found' 			=> esc_html__('Folder not found', MEDIASORTER_TEXT_DOMAIN),
						'not_found_in_trash' 	=> esc_html__('Folder not found in trash', MEDIASORTER_TEXT_DOMAIN),
					), 
					'show_ui' 			=> true,
					'show_in_menu' 		=> false,
					'show_in_nav_menus'	=> false,
					'show_in_quick_edit'=> false,
					'update_count_callback' => '_update_generic_term_count',
					'show_admin_column'	=> false,
					"rewrite" 			=> false )
		);
	}

	
	public function mediasorterAjaxUpdateFolderPositionCallback()
	{
		$result 	= sanitize_text_field($_POST["result"]);
		$result 	= explode("|", $result);
		foreach ($result as $key) {
			$key 	= explode(",", $key);
			update_term_meta($key[0],'folder_position',$key[1]);
		}
		die();
	}


	public function mediasorterAjaxDeleteFolderListCallback()
	{
		$current 			= sanitize_text_field($_POST["current"]);
		$count_attachments 	= 0;
		$current_term 		= get_term($current , MEDIASORTER_FOLDER );
		$count_attachments 	= $current_term->count;
		$term 				= wp_delete_term( $current, MEDIASORTER_FOLDER );
		
		if (is_wp_error($term))
		{
			echo "error";
		}
		echo esc_html($count_attachments);
		die();
	}

	
	public static function mediasorterSetValidTermName($name, $parent)
	{
		if(!$parent)
		{
			$parent = 0;
		}
 		
		$terms 	= get_terms( MEDIASORTER_FOLDER, array('parent' => $parent, 'hide_empty' => false) );
		$check 	= true;

		if(count($terms))
		{
			foreach ($terms as $term) 
			{
				if($term->name === $name)
				{
					$check = false;
					break;
				}
			}
		}
		else
		{
			return $name;
		}

		
		if($check)
		{
			return $name;			
		}

		$arr = explode('_', $name);	

		if($arr && count($arr) > 1)
		{	
			$suffix = array_values(array_slice($arr, -1))[0];

			//remove end item (suffix) of array
			array_pop($arr);

			//get folder base name (no suffix)
			$origin_name = implode($arr);

			if(intval($suffix))
			{
				$name = $origin_name . '_' . (intval($suffix)+1);
			}

		}
		else
		{
			$name = $name . '_1';
		}		

		$name = self::mediasorterSetValidTermName($name, $parent);

		return $name;

	}
	
	private function slug_generator($string){
		$string = strtolower($string);
	   	$slug	= preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
	   	return $slug;
	}

	
	public function mediasorterAjaxUpdateFolderListCallback()
	{
		$current 	= sanitize_text_field($_POST["current"]);
		$new_name 	= sanitize_text_field($_POST["new_name"]);
		$parent 	= sanitize_text_field($_POST["parent"]);
		$type 		= sanitize_text_field($_POST["type"]);
		
		switch ($type) 
		{
			case 'new':
				
				
				$folders 	= get_terms(MEDIASORTER_FOLDER, array('hide_empty' => false));
				$count 		= count($folders) + 1;
				$name 		= self::mediasorterSetValidTermName($new_name, $parent);
				$term_new 	= wp_insert_term($name, MEDIASORTER_FOLDER ,array(
					'name' 		=> $name,
					'parent' 	=> $parent
				));
				if (is_wp_error($term_new))
				{
					echo "error";
				}
				else
				{
					add_term_meta( $term_new["term_id"], 'folder_type', sanitize_text_field($_POST["folder_type"]) );
					add_term_meta( $term_new["term_id"], 'folder_position', 9999 );
					wp_send_json_success( array('term_id' => $term_new["term_id"], 'term_name' => $name ) );
				}
				break;

			case 'rename':
				$check_error = wp_update_term($current, MEDIASORTER_FOLDER, array(
					'name' => $new_name
					//'slug' => $this->slug_generator($new_name)
				));
				if (is_wp_error($check_error))
				{
					echo "error";
				}
				break;
			case 'move':
				$check_error = wp_update_term($current, MEDIASORTER_FOLDER, array(
					'parent' => $parent
				));
				if (is_wp_error($check_error))
				{
					echo "error";
				}
				break;
		}
		die();
	}

	
	public function mediasorterAjaxGetChildFoldersCallback()
	{
		$term_id 	= sanitize_text_field($_POST['folder_id']);
		$terms 		= get_terms(MEDIASORTER_FOLDER, array(
			'hide_empty' 	=> false,
			'meta_key' 		=> 'folder_position',	//	BUG: does not show custom folders OR shows all of them alphabetically
			'orderby' 		=> 'meta_value',
			'parent' 		=> $term_id
		));
		$terms2 = get_terms($taxonomy, array( //	BUG: does not show custom folders OR shows all of them alphabetically
				'hide_empty' 	=> false,
				'orderby' 		=> 'meta_value',
				'parent' 		=> $parent 
		));
		$terms = array_unique(array_merge($terms,$terms2), SORT_REGULAR); //	BUG: does not show custom folders OR shows all of them alphabetically

		if (is_wp_error($terms))
		{
			echo "error";
		}

		wp_send_json_success( $terms );					
	}

	
	public function mediasorterAjaxSaveSplitter()
	{
		$width = sanitize_text_field($_POST['splitter_width']);
		
		if(update_option( 'mediasorter_sidebar_width', $width ))
		{
			wp_send_json_success();	
		} 
		else
		{
			wp_send_json_error();	
		}
	}

    public function mediasorterTermTreeOption($terms, $spaces = "-")
	{
		$html = '';
		
		if(!is_null($terms) && count($terms) > 0) 
		{
 			foreach($terms as $item) 
			{
                $html .= '<option value="' . $item->term_id . '" data-id="' . $item->term_id . '">' . $spaces . '&nbsp;' . $item->name . '</option>';
                
                if (is_array($item->children) && count($item->children) > 0) 
				{
                    $html .= $this->mediasorterTermTreeOption($item->children, str_repeat($spaces, 2));
                }
            }
		}
		return $html;
	}

	
    public function mediasorterTermTreeArray($taxonomy, $parent)
	{
		$terms = get_terms($taxonomy, array(
				'hide_empty' 	=> false,
				'meta_key' 		=> 'folder_position',
				'orderby' 		=> 'meta_value',
				'parent' 		=> $parent 
		));
		$terms2 = get_terms($taxonomy, array( //	BUG: does not show custom folders OR shows all of them alphabetically
				'hide_empty' 	=> false,
				'orderby' 		=> 'meta_value',
				'parent' 		=> $parent 
		));
		$terms = array_unique(array_merge($terms,$terms2), SORT_REGULAR); //	BUG: does not show custom folders OR shows all of them alphabetically
		$children = array();
		
		// go through all the direct decendants of $parent, and gather their children
		foreach ( $terms as $term ){
			// recurse to get the direct decendants of "this" term
			$term->children = $this->mediasorterTermTreeArray( $taxonomy, $term->term_id );
			// add the term to our new array
			$children[] 	= $term;
		}
		// send the results back to the caller
		return $children;
	}
	
	
	
	// show in upload file when add Media on all page
	public function mediasorterPreUploadInterface() 
	{
        $terms 		= $this->mediasorterTermTreeArray(MEDIASORTER_FOLDER, 0);
		$options 	= $this->mediasorterTermTreeOption($terms);
		$label 		= esc_html__("Select a folder and upload files (Optional)", MEDIASORTER_TEXT_DOMAIN);
		
		echo '<p class="attachments-category">' . $label . '<br/></p>
				<p>
					<select name="themedoWMCFolder" class="themedo-mediasorter-editcategory-filter"><option value="-1">-'.esc_html__('Uncategorized', MEDIASORTER_TEXT_DOMAIN).'</option>' . $options . '</select>
				</p>';
	}
	
	
	private function get_strings(){
		$array = array(
		    'move_1_file' 					=> esc_html__( 'Move 1 file', MEDIASORTER_TEXT_DOMAIN ),
		    'oops' 							=> esc_html__( 'Oops', MEDIASORTER_TEXT_DOMAIN ),
		    'error' 						=> esc_html__( 'Error', MEDIASORTER_TEXT_DOMAIN ),
		    'this_folder_is_already_exists' => esc_html__( 'This folder already exists. Please type another name.', MEDIASORTER_TEXT_DOMAIN ),
		    'error_occurred' 				=> esc_html__( 'Sorry! An error occurred while processing your request.', MEDIASORTER_TEXT_DOMAIN ),
		    'folder_cannot_be_delete' 		=> esc_html__( 'This folder cannot be deleted.', MEDIASORTER_TEXT_DOMAIN ),
		    'add_sub_folder' 				=> esc_html__( 'Add Sub folder', MEDIASORTER_TEXT_DOMAIN ),
		    'new_folder' 					=> esc_html__( 'New folder', MEDIASORTER_TEXT_DOMAIN ),
		    'rename' 						=> esc_html__( 'Rename', MEDIASORTER_TEXT_DOMAIN ),
		    'remove' 						=> esc_html__( 'Remove', MEDIASORTER_TEXT_DOMAIN ),
			'delete' 						=> esc_html__( 'Delete', MEDIASORTER_TEXT_DOMAIN ),
			'refresh' 						=> esc_html__( 'Refresh', MEDIASORTER_TEXT_DOMAIN ),
		    'something_not_correct' 		=> esc_html__( 'Something isn\'t correct here.', MEDIASORTER_TEXT_DOMAIN ),
		    'this_page_will_reload' 		=> esc_html__( 'This page will be reloaded now.', MEDIASORTER_TEXT_DOMAIN ),
		    'folder_are_sub_directories' 	=> esc_html__( 'This folder contains subfolders, you should delete subfolders first!', MEDIASORTER_TEXT_DOMAIN ),
		    'are_you_sure' 					=> esc_html__( 'Are you sure?' , MEDIASORTER_TEXT_DOMAIN ),
		    'not_able_recover_folder' 		=> esc_html__( 'All files inside this folder gets moved to "Uncategorized" folder.', MEDIASORTER_TEXT_DOMAIN ),
		    'yes_delete_it' 				=> esc_html__( 'Delete!', MEDIASORTER_TEXT_DOMAIN ),
		    'deleted' 						=> esc_html__( 'Deleted', MEDIASORTER_TEXT_DOMAIN ),
		    'Move' 							=> esc_html__( 'Move', MEDIASORTER_TEXT_DOMAIN ),
		    'files' 						=> esc_html__( 'files', MEDIASORTER_TEXT_DOMAIN ),
			'limit_folder_title' 			=> esc_html__( 'Folder Limit Reached', MEDIASORTER_TEXT_DOMAIN ),
		    'limit_folder_content' 			=> esc_html__( 'The mediasorter Lite version allows you to manage up to 12 folders.<br>To have unlimited folders, please upgrade to PRO version.</br></br><span class="upgrade_features">Unlimited Folders</br>Get Fast Updates</br>Lifetime Support</br>30-day Refund Guarantee</span></br></br>', MEDIASORTER_TEXT_DOMAIN ),
		    'folder_deleted' 				=> esc_html__( 'Your folder has been deleted.', MEDIASORTER_TEXT_DOMAIN ),
		    'upgrade' 						=> esc_html__( 'Get Pro', MEDIASORTER_TEXT_DOMAIN ),
		    'no_thanks' 					=> esc_html__( 'No, thanks', MEDIASORTER_TEXT_DOMAIN ),
		    'cancel' 						=> esc_html__( 'Cancel', MEDIASORTER_TEXT_DOMAIN ),
		    'reload' 						=> esc_html__( 'Reload', MEDIASORTER_TEXT_DOMAIN ),
		    'folder_name_enter' 			=> esc_html__( 'Please enter your folder name.', MEDIASORTER_TEXT_DOMAIN ),
		);
		return $array;
	}

}

new Mediamatic_Interface();
