<?php
/*
Plugin Name: MoodArea
Plugin URI: http://www.alexl.de/moodarea
Description: Interactive simple media area for mood expressing on a site. 
Version: 0.1
Author: Alexander Lübeck
Author URI: http://www.alexl.de
License: GPL2

	Copyright 2013  Alexander Lübeck  (email : alex@alexl.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/


require_once( ABSPATH.PLUGINDIR."/mypluginbase/mypluginbase.php" );


//plugin hooks during install/uninstall plugin
register_activation_hook( __FILE__, array('MoodArea','activate') );
register_deactivation_hook( __FILE__, array('MoodArea','deactivate') );






class MoodArea extends MyPlugin {
	
	private $adminpage_url;
	
	
	public function __construct() {
		
		parent::__construct( array(
			'longname'		=> 'MoodArea',
			'shortname'		=> 'moodarea',
			'optionname'	=> 'moodarea_option',
			'pluginfile'	=> __FILE__
		));	
		
		
		$this->adminpage_url = "admin.php?page=".$this->slug;
		
	
		add_action( 'wp_ajax_nopriv_moodarea-updateimage', array(&$this,'ajax_updateImage') );
		add_action( 'wp_ajax_moodarea-updateimage', array(&$this,'ajax_updateImage') );
		
		add_action('wp_enqueue_scripts', array($this, 'bindFrontendScripts') );
		
	}
	
	
	public function bindFrontendScripts() {
		
		wp_enqueue_script( 'moodarea_slideshow', plugin_dir_url( __FILE__ ) . '/js/moodarea_slideshow.js', array('jquery'));	
	}
	
	
	/**
	 * @desc update image information for moodarea (add & delete)
	 */
	public function ajax_updateImage() {
	
	    $nonce = $_POST['nonce'];
	    if ( ! wp_verify_nonce( $nonce, 'nonce' ) )
	        die ( 'Busted!');    		
		
		if(isset($_POST['imgdata'])) {
		//save imagedata for current moodimage
			
			$imgdata = $_POST['imgdata'];

			$moodarea_pictures = get_post_meta($_POST['post_id'], 'moodarea_pictures', true);
			if(!is_array($moodarea_pictures)) $moodarea_pictures = array();
			
			$moodarea_pictures[ $_POST['moodimg_id'] ] = $imgdata;
			
			
			//special case: create second empty mood area (you have to save two empty items inside db)
			if( $_POST['modus'] == 'add-image-area' && !isset($moodarea_pictures[1]['sizes']['medium']['url']) ) {
			
				$moodarea_pictures = array( "1" => $imgdata, "2" => $imgdata);	
			}
				
				 
			
			update_post_meta($_POST['post_id'], 'moodarea_pictures', $moodarea_pictures);
			
				
		} else {
		//no image data: delete of current moodimage

			$moodarea_pictures = get_post_meta($_POST['post_id'], 'moodarea_pictures', true);
			unset( $moodarea_pictures[$_POST['moodimg_id']] );
			
			update_post_meta($_POST['post_id'], 'moodarea_pictures', $moodarea_pictures);
		}
		
		
	    
		if( isset($_POST['modus'])) 

			//modus: add image area - get smaller image url for first image
			if( $_POST['modus'] == 'add-image-area' ) {
				
				if( isset($moodarea_pictures[1]['sizes']['medium']) )
					echo $moodarea_pictures[1]['sizes']['medium']['url'];
				else 
					echo $moodarea_pictures[1]['sizes']['full']['url'];

			} elseif( $_POST['modus'] == 'del-image-area' ) {
			//opposite: get bigger image version
				
				$pic = array();
				if( isset($moodarea_pictures[1]['sizes']['large']) )
				 	$pic = $moodarea_pictures[1]['sizes']['large'];
				else
					$pic = $moodarea_pictures[1]['sizes']['full'];
					
				
				if( isset($pic['url']) )
					echo $pic['url'];
				else {
					//1 of 2 image areas deleted, no image for area 1 available == no data, 
					//=> delete post meta for this post 
					delete_post_meta($_POST['post_id'], 'moodarea_pictures');
					
				}
			}

			
	    exit();
	        
	        
	}
	
	

	
	
	/**
	 * @desc plugin hook for admin_init
	 */
	public function initiate_plugin() {
		
		
	}

	/**
	 * desc plugin hook for admin_menu
	 */
	public function create_menupage() {
		
		
	}
	

	/**
	 * desc plugin hook for "add_meta_boxes"
	 */
	public function create_post_metaboxes($postType) {
		
		load_plugin_textdomain( 'moodarea', false,  'moodarea/i18n' );
		
		
		
		
		wp_enqueue_style(  'moodarea', plugin_dir_url( __FILE__ ) . '/css/metabox.css');
		
		wp_enqueue_script( 'moodarea', plugin_dir_url( __FILE__ ) . '/js/moodarea_metabox.js', array('jquery'));
		wp_enqueue_script( 'moodarea_slideshow', plugin_dir_url( __FILE__ ) . '/js/moodarea_slideshow.js', array('jquery'));
				
		wp_localize_script('moodarea', 'Moodarea', array( 
			'ajaxurl' 			=> admin_url( 'admin-ajax.php' ),
			'nonce' 			=> wp_create_nonce( 'nonce' ),
		));
		
		
		$types = apply_filters('moodarea_metabox_post-types', array('page'));
		
		if( in_array($postType, $types)) {

			add_meta_box(
				'metabox-moodarea',								// Unique ID
				__( 'site image', 'moodarea' ),					// Title
				array( &$this, 'createMoodboxContent' ),		// Callback function
				$postType,										// Admin page (or post type)
				'normal',										// Context
				'high'										// Priority
			);		
		}
				
		//add_meta_box('custom_editor', 'Text Content', array( &$this, 'emptyMetabox' ), 'page', 'normal', 'core');
		
	}

	
	
	public function createMoodboxContent() {

		$post = get_post($_POST['id']);
		
		$moodarea_pictures = get_post_meta($post->ID, 'moodarea_pictures', true);
		
		$css = array('',' hidden');		//general css for a moodpicture (std. 2nd image dont show)
		$style = array('','');			//css for background-image
		$addimage_css = array('','');
		$delimage_css = array(' hidden', ' hidden');
		$addimagearea_css = array('','');
		$delimagearea_css = array('','');
		

				
		
				
		if( !is_array($moodarea_pictures) ) {
			
			
			
		} else {
		
			if(isset($moodarea_pictures[1])) $moodarea_pictures[1] = json_decode( $moodarea_pictures[1], true);
			if(isset($moodarea_pictures[2])) $moodarea_pictures[2] = json_decode( $moodarea_pictures[2], true);
			
			if( count($moodarea_pictures) == 1 && isset($moodarea_pictures[1][0]['sizes']['large']['url']) ) {
		
				/*
				//special case: image for first area is exact 850 x 300, $moodarea_pictures[1]['sizes']['large'] is not setted
				$pic = array();
				if( !isset($moodarea_pictures[1]['sizes']['large']) )
					$pic = $moodarea_pictures[1]['sizes']['full'];
				else
					$pic = $moodarea_pictures[1]['sizes']['large'];
				*/
				
				
				
				$style[0] = 'background-image:url(' . $moodarea_pictures[1][0]['sizes']['large']['url'] . ');';
				$addimage_css[0] = ' hidden';
				$delimage_css[0] = '';
				$addimagearea_css[0] = '';
						
			} elseif( count($moodarea_pictures) == 2 ) {
			
			
				$css = array(' two-pic', ' two-pic');
				$addimagearea_css = array(' hidden', ' hidden');
				
				$delimage_css = array(' hidden', ' hidden');
				$addimage_css = array('', '');
				
				/*
				//special case: image for first and/or second area is exact 423 x 300, 
				//than $moodarea_pictures[1]['sizes']['medium'] is not setted, use the full (original) version instead
				$pic = array();
				$pic[1] = array();
				$pic[2] = array();
				
				if( !isset($moodarea_pictures[1]['sizes']['medium']) )
					$pic[1] = $moodarea_pictures[1]['sizes']['full'];
				else
					$pic[1] = $moodarea_pictures[1]['sizes']['medium'];
	
				if( !isset($moodarea_pictures[2]['sizes']['medium']) )
					$pic[2] = $moodarea_pictures[2]['sizes']['full'];
				else
					$pic[2] = $moodarea_pictures[2]['sizes']['medium'];
				*/
				
				if( isset($moodarea_pictures[1][0]['sizes']['medium']['url']) && isset($moodarea_pictures[2][0]['sizes']['medium']['url']) ) {
					
					//2 moodpictures setted	
					$style[0] = 'background-image:url(' . $moodarea_pictures[1][0]['sizes']['medium']['url'] . ');';
					$style[1] = 'background-image:url(' . $moodarea_pictures[2][0]['sizes']['medium']['url'] . ');';
					
					$delimage_css = array('', '');
					$addimage_css = array(' hidden', ' hidden');
										
				} else if( isset($moodarea_pictures[2][0]['sizes']['medium']['url']) ) {
				//second moodpicture setted
					
					$style[0] = 'background-image:url(' . $moodarea_pictures[1][0]['sizes']['medium']['url'] . ');';
					$style[1] = 'background-image:url(' . $moodarea_pictures[2][0]['sizes']['medium']['url'] . ');';
					$addimage_css[1] = ' hidden';
					$delimage_css[1] = '';
					
				} else if( isset($moodarea_pictures[1][0]['sizes']['medium']['url']) ) {
				//first moodpicture setted
					
					$style[0] = 'background-image:url(' . $moodarea_pictures[1][0]['sizes']['medium']['url'] . ');';
									
					$addimage_css[0] = ' hidden';
					$delimage_css[0] = '';
					
					
				} 
			
			}
		
		}
		
		?> 
		
		<div class="moodpicture">  
		
			<div id="pic_1" class="picture<?php echo $css[0]; ?>" style="<?php echo $style[0]; ?>">
				<div class="commandline">
					<a class="add-image<?php echo $addimage_css[0]; ?>" href="">Bildinhalt hinzufügen</a>
					<a class="del-image<?php echo $delimage_css[0]; ?>" href="">Bildinhalt löschen</a>
					<a class="add-image-area<?php echo $addimagearea_css[0]; ?>" href="">Bildfläche hinzufügen</a>
				</div>
			</div>
			<div id="pic_2" class="picture<?php echo $css[1]; ?>" style="<?php echo $style[1]; ?>">
				<div class="commandline">
					<a class="add-image<?php echo $addimage_css[1]; ?>" href="">Bildinhalt hinzufügen</a>
					<a class="del-image<?php echo $delimage_css[1]; ?>" href="">Bildinhalt löschen</a>
					<a class="del-image-area<?php echo $delimagearea_css[1]; ?>" href="">Bildfläche löschen</a>
				</div>
			</div>
		
		</div>
		
	<?php		
		

		
	}
	
	
	public static function activate() {

	}
	
	
	
	public static function deactivate() {
		
	}
	

	
}


//instantiate the class
$moodarea_var = new MoodArea();


?>