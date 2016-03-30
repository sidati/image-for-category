<?php
/*
Plugin Name: Image For Category
Plugin URI: https://github.com/sidati/image-for-category
Description: Add image for each category
Version: 1.0
Author: Sidati
Author URI: https://sidati.com
Text Domain: ifc
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
	exit;
}

/**
 * return attachement id of the category's image
 *
 * @param      int  $cat_id  (Category ID)
 *
 * @return     int	Category's image ID
 */
function ifc_category_image_id($cat_id = false) {

	if (!$cat_id) {

		if (!is_category()) {
			return;
		}

		$cat_id = get_queried_object_id();
	}


	if (function_exists('get_term_meta')) {
		$image_id = (int) get_term_meta($cat_id, 'ifc', true);
	} elseif (!$image_id = (int) get_option('ifc-'.$cat_id, false)) {
		$image_id = null;
	}

	return $image_id;
}

/**
 * Print HTML markup of the image or nothing if the catgery has no image.
 *
 * @param      boolean  $cat_id  ID of category
 * @param      string   $size    Size of printed image (thumbnail, medium, large, full or any other registred size)
 * @param      array    $attrs   Image attributes
 * 
 * @return     html print image markup if exist
 */
function ifc_category_image($cat_id = false, $size = 'thumbnail', $attrs = array()) {

	if (!$image_id = ifc_category_image_id($cat_id)) {
		return;
	}

	$size_class = $size;
	if ( is_array( $size_class ) ) {
		$size_class = join('x', $size_class);
	}

	$attrs = wp_parse_args($attrs, array(
		'class' => 'ifc_category_image attachment-'.$size_class.' size-'.$size_class
	));

	echo wp_get_attachment_image($image_id, $size, false, $attrs);
}

/**
 * Category Featured Image PHP Class
 */
class ifc_plugin {

	const VER = '1.0';
	public $plugin_url;
	private $suffix;
	
	function __construct() {

		$this->plugins_url = plugins_url('category-featured-image');
		$this->suffix = (defined('WP_DEBUG') && WP_DEBUG) ? null : '.min';

		add_filter('media_view_strings', array($this, 'js_strings'));

		add_action('admin_init', array($this, 'styles'));
		add_action('admin_enqueue_scripts', array($this, 'scripts'));
		add_action('plugins_loaded', array($this, 'loaded'));

		add_action( 'created_term', array($this, 'save_image_id'), 10, 3 );
		add_action( 'edited_term', array($this, 'save_image_id'), 10, 3 );
		add_action( 'deleted_term', array($this, 'delete_image_id'), 10, 3 );

		// register_activation_hook(__FILE__, array($this, 'activate'));
		// register_deactivation_hook(__FILE__, array($this, 'deactivate'));
		// register_uninstall_hook(__FILE__, array($this, 'uninstall'));

		add_action('category_edit_form_fields', array($this, 'form_fields'));
		add_action('category_add_form_fields', array($this, 'form_fields'));
	}

	public function activate() {
		wp_cache_flush();
		// flush_rewrite_rules();
	}

	public function deactivate() {
		wp_cache_flush();
	}

	public function uninstall() {
		wp_cache_flush();
	}

	public function loaded() {
		load_plugin_textdomain('sidati_ifc', false, 'facebook-page-plugin');
	}

	public function save_image_id($cat_id, $tt_id, $taxonomy) {

		if (empty($_POST['ifc_id']) || !is_numeric($_POST['ifc_id'])) {
			delete_term_meta($cat_id, 'ifc');
			$this->delete_image_id($cat_id, $tt_id, $taxonomy);
			return;
		}

		$image_id = intval($_POST['ifc_id']);

		if (function_exists('add_term_meta')) {
			add_term_meta($cat_id, 'ifc', $image_id, true) || update_term_meta($cat_id, 'ifc', $image_id);
		} else {
			update_option('ifc-'.$cat_id, $image_id);
		}
	}

	public function delete_image_id($cat_id, $tt_id, $taxonomy) {
		if (!function_exists('delete_term_meta')) {
			delete_option('ifc-'.$cat_id);
		}
	}

	public function styles() {
		wp_enqueue_style('sidati_ifc', $this->plugins_url.'/style'.$this->suffix.'.css', array(), self::VER, 'all');
	}

	public function scripts($hook) {

		if (!in_array($hook, array('edit-tags.php', 'term.php'))) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script('ifc', $this->plugins_url.'/script'.$this->suffix.'.js', array('jquery'), self::VER, true);
	}

	public function js_strings() {

		$strings['ifcTitle'] = __('Image Category', 'ifc');
		$strings['ifcBtn'] = __('Select', 'ifc');

		return $strings;
	}

	public function form_fields($term) {

		$add_btn_status = $src = null;
		$del_btn_status = ' hidden';

		if (doing_action('category_edit_form_fields')) :

			if (function_exists('get_term_meta')) {
				$image_id = get_term_meta($term->cat_id, 'ifc', true);
			} elseif (!$image_id = get_option('ifc-'.$term->cat_id, false)) {
				$image_id = null;
			}

			if (!empty($image_id)) {
				list($src) = wp_get_attachment_image_src($image_id, 'thumbnail');
				$add_btn_status = ' hidden';
				$del_btn_status = null;
			}

		?>
		<tr id="ifc_plugin" class="form-field">
			<th valign="top" scope="row">
				<label for="sidati_add_cat_img"><?php _e('Image', 'ifc'); ?></label>
			</th>
			<td>
				<span style="background-image: url(<?php echo $src ?>);"></span>
				<input type="hidden" name="ifc_id" value="<?php echo $image_id ?>">
				<button class="button ifc_add<?php echo $add_btn_status ?>"><?php _e('Choose Image', 'ifc') ?></button>
				<button class="button ifc_delete<?php echo $del_btn_status ?>"><?php _e('Delete Image', 'ifc') ?></button>
				<p class="description"><?php _e('Description', 'ifc') ?></p>
			</td>
		</tr>
		<?php else : ?>
		<div id="ifc_plugin" class="form-field term-description-wrap">
			<label for="tag-description"><?php _e('Image', 'ifc') ?></label>
			<span></span>
			<input type="hidden" name="ifc_id" value="">
			<button class="button ifc_add"><?php _e('Choose Image', 'ifc') ?></button>
			<button class="button ifc_delete hidden"><?php _e('Delete Image', 'ifc') ?></button>
			<p class="description"><?php _e('Description', 'ifc') ?></p>
		</div>
		<?php
		endif;
	}

}

new ifc_plugin;