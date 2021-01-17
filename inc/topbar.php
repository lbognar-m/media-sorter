<?php

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * class Mediamatic_Topbar
 * the main class
 */
class Mediamatic_Topbar {

	
	public $plugin_version = MEDIASORTER_VERSION;


    public function __construct() {
        // load code that is only needed in the admin section
        if ( is_admin() ) 
		{
            add_action( 'add_attachment', array( $this, 'mediasorterAddAttachmentCategory' ) );
            add_action( 'edit_attachment', array( $this, 'mediasorterSetAttachmentCategory' ) );
            add_filter( 'ajax_query_attachments_args', array( $this, 'mediasorterAjaxQueryAttachmentsArgs' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'mediasorterEnqueueMediaAction' ) );
            add_action( 'wp_ajax_save-attachment-compat', array( $this, 'mediasorterSaveAttachmentCompat' ), 0 );
            add_action( 'wp_ajax_mediasorterSaveAttachment', array( $this, 'mediasorterSaveAttachment' ), 0 );
            add_action( 'wp_ajax_mediasorterGetTermsByAttachment', array( $this, 'mediasorterGetTermsByAttachment' ), 0 );
            add_action( 'wp_ajax_mediasorterSaveMultiAttachments', array( $this, 'mediasorterSaveMultiAttachments' ), 0 );
        }
    }

	
    public function mediasorterAddAttachmentCategory( $post_ID ) 
	{
        $mediasorter_Folder = isset($_REQUEST["themedoWMCFolder"]) ? sanitize_text_field($_REQUEST["themedoWMCFolder"]) : null;
        if (is_null($mediasorter_Folder)) 
		{
            $mediasorter_Folder = isset($_REQUEST["themedo_mediasorter_folder"]) ? sanitize_text_field($_REQUEST["themedo_mediasorter_folder"]) : null;
        }
        if ($mediasorter_Folder !== null) 
		{
            $mediasorter_Folder = (int)$mediasorter_Folder;
            if ($mediasorter_Folder > 0) {
                wp_set_object_terms($post_ID, $mediasorter_Folder, MEDIASORTER_FOLDER, false);
            }
        }
    }


    public function mediasorterSetAttachmentCategory( $post_ID ) 
	{
        $taxonomy = MEDIASORTER_FOLDER;
        $taxonomy = apply_filters( 'mediasorter_taxonomy', $taxonomy );

        // if attachment already have categories, stop here
        if ( wp_get_object_terms( $post_ID, $taxonomy ) ) 
		{
            return;
        }

        // no, then get the default one
        $post_category = array( get_option( 'default_category' ) );

        // then set category if default category is set on writting page
        if ( $post_category ) 
		{
            wp_set_post_categories( $post_ID, $post_category );
        }
    }


    public static function mediasorterGetTermsValues( $keys = 'ids' ) 
	{

        // Get media taxonomy
        $media_terms = get_terms( MEDIASORTER_FOLDER, array(
            'hide_empty' => 0,
            'fields'     => 'id=>slug',
        ) );
        $media_values = array();
		
        foreach ( $media_terms as $key => $value ) 
		{
            $media_values[] = ( $keys === 'ids' )
                ? $key
                : $value;
        }

        return $media_values;
    }


    public function mediasorterAjaxQueryAttachmentsArgs( $query = array() ) 
	{

        $taxquery 			= isset( $_REQUEST['query'] ) ? (array) $_REQUEST['query'] : array();
        $taxonomies 		= get_object_taxonomies( 'attachment', 'names' );
        $taxquery 			= array_intersect_key( $taxquery, array_flip( $taxonomies ) );
        $query 				= array_merge( $query, $taxquery );// merge our query into the WordPress query
        $query['tax_query'] = array( 'relation' => 'AND' );

        foreach ( $taxonomies as $taxonomy ) 
		{
            if ( isset( $query[$taxonomy] ) && is_numeric( $query[$taxonomy] ) ) 
			{
                if ( $query[ $taxonomy ] > 0 ) 
				{
                    array_push( $query['tax_query'], array(
                        'taxonomy' => $taxonomy,
                        'field'    => 'id',
                        'terms'    => $query[$taxonomy],
                        'include_children'  => false
                    ));
                }
				else
				{
                    $all_terms_ids = self::mediasorterGetTermsValues( 'ids' );
					array_push( $query[ 'tax_query' ], array(
						'taxonomy' => $taxonomy,
						'field'    => 'id',
						'terms'    => $all_terms_ids,
						'operator' => 'NOT IN',
					) );
					
					
                    
                }
                
            }
            unset( $query[$taxonomy] );
        }

        return $query;
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
    }


    public function mediasorterSaveAttachmentCompat() 
	{
        if ( ! isset( $_REQUEST['id'] ) ) 
		{
            wp_send_json_error();
        }

        if ( ! $id = absint( $_REQUEST['id'] ) ) 
		{
            wp_send_json_error();
        }

        if ( empty( $_REQUEST['attachments'] ) || empty( $_REQUEST['attachments'][ $id ] ) ) 
		{
            wp_send_json_error();
        }
        $attachment_data = sanitize_text_field($_REQUEST['attachments'][ $id ]);
       
        if ( current_user_can( 'edit_post', $id ) ) 
		{
			check_ajax_referer( 'update-post_' . $id, 'nonce' );
		}
        
		if ( ! current_user_can( 'edit_post', $id ) ) 
		{
            wp_send_json_error();
        }

        $post = get_post( $id, ARRAY_A );

        if ( 'attachment' != $post['post_type'] ) 
		{
            wp_send_json_error();
        }

        $post = apply_filters( 'attachment_fields_to_save', $post, $attachment_data );

        if ( isset( $post['errors'] ) ) 
		{
            $errors = $post['errors']; 
            unset( $post['errors'] );
        }

        wp_update_post( $post );

        foreach ( get_attachment_taxonomies( $post ) as $taxonomy ) 
		{

            if ( isset( $attachment_data[ $taxonomy ] ) ) 
			{
                wp_set_object_terms( $id, array_map( 'trim', preg_split( '/,+/', $attachment_data[ $taxonomy ] ) ), $taxonomy, false );
            } 
			else if ( isset($_REQUEST['tax_input']) && isset( $_REQUEST['tax_input'][ $taxonomy ] ) ) 
			{
                wp_set_object_terms( $id, sanitize_text_field($_REQUEST['tax_input'][ $taxonomy ]), $taxonomy, false );
            } 
			else 
			{
                wp_set_object_terms( $id, '', $taxonomy, false );
            }
            
        }

        if ( ! $attachment = wp_prepare_attachment_for_js( $id ) ) 
		{
            wp_send_json_error();
        }

        wp_send_json_success( $attachment );
    }

    public function mediasorterSaveMultiAttachments()
	{

        $ids 		= $_REQUEST['ids'];
        $result 	= array();

        foreach ($ids as $key => $id) 
		{
            $term_list 	= wp_get_post_terms( sanitize_text_field($id), MEDIASORTER_FOLDER, array( 'fields' => 'ids' ) );
            $from 		= -1;

            if(count($term_list))
			{
                $from = $term_list[0];
            }

            $obj 		= (object) array('id' => $id, 'from' => $from, 'to' => sanitize_text_field($_REQUEST['folder_id']));
            $result[] 	= $obj;

            wp_set_object_terms( $id, intval(sanitize_text_field($_REQUEST['folder_id'])), MEDIASORTER_FOLDER, false );

        }

        wp_send_json_success( $result );

    }

	
    public function mediasorterSaveAttachment() 
	{
        if ( ! isset( $_REQUEST['id'] ) ) 
		{
            wp_send_json_error();
        }

        if ( ! $id = absint( sanitize_text_field($_REQUEST['id']) ) ) 
		{
            wp_send_json_error();
        }

        if ( empty( $_REQUEST['attachments'] ) || empty( $_REQUEST['attachments'][ $id ] ) ) 
		{
            wp_send_json_error();
        }
        $attachment_data = $_REQUEST['attachments'][ $id ];

        $post = get_post( $id, ARRAY_A );

        if ( 'attachment' != $post['post_type'] ) {
            wp_send_json_error();
        }

        $post = apply_filters( 'attachment_fields_to_save', $post, $attachment_data );

        if ( isset( $post['errors'] ) ) 
		{
            $errors = $post['errors']; 
            unset( $post['errors'] );
        }

        wp_update_post( $post );


        wp_set_object_terms( $id, intval(sanitize_text_field($_REQUEST['folder_id'])), MEDIASORTER_FOLDER, false );
        if ( ! $attachment = wp_prepare_attachment_for_js( $id ) ) 
		{
            wp_send_json_error();
        }

        wp_send_json_success( $attachment );
    }

	
    public function mediasorterGetTermsByAttachment()
	{
		
		$nonce = sanitize_text_field($_POST['nonce']);

		if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ){
			wp_send_json_error();
		}
		
        if ( ! isset( $_REQUEST['id'] ) ) 
		{
            wp_send_json_error();
        }
        if ( ! $id = absint( sanitize_text_field($_REQUEST['id'] ) )) 
		{
            wp_send_json_error();
        }
        $terms  = get_the_terms($id, MEDIASORTER_FOLDER);
        wp_send_json_success( $terms );
    }


    

	
    public static function get_uncategories_attachment()
	{
        $args = array(
            'post_type' 		=> 'attachment',
            'post_status' 		=> 'inherit,private',
            'posts_per_page' 	=> -1,
            'tax_query' 		=> Array
                (
                    'relation' 	=> 'AND',
                    0 => Array
                        (
                            'taxonomy' 	=> MEDIASORTER_FOLDER,
                            'field' 	=> 'id',
                            'terms' 	=>  self::mediasorterGetTermsValues('ids'),
                            'operator' 	=> 'NOT IN'
                        )
                )
        );
        $result = get_posts($args);
        return count($result);
    }

}

$mediasorter_topbar = new Mediamatic_Topbar();

