<?php
/*
Plugin Name: BSGallery 
Plugin URI: http://wordpress.org/extend/plugins/bsgallery/
Description: Gallery Plugin
Version: 1.4
Author: Michal Nezerka 
Author URI: http://blue.pavoucek.cz
Text Domain: bsgallery
Domain Path: /lang
*/

// plugin class
class BSGallery
{  
	const DEFAULT_SINGLE_WIDTH = 300;

	// single instance of this class (singleton pattern)
    private static $instance; 

	protected $pluginPath;
	protected $pluginUrl;

	// get instance of this class (singleton pattern)
	public static function getInstance()
	{
		if (!self::$instance)
			self::$instance = new BSGallery();
		return self::$instance;
	}

	// constructor
	public function __construct()  
	{  
		$this->pluginPath = plugin_dir_path(__FILE__);
		$this->pluginUrl = plugin_dir_url(__FILE__);

		// register callbacks to be called by wordpress ----------------
		add_action('init', array($this, 'init'));
		add_filter('attachment_fields_to_edit', array(&$this, 'onAttachmentFieldsEdit'), 10, 2);
		add_filter('attachment_fields_to_save', array(&$this, 'onAttachmentFieldsSave'), 10, 2);
		add_filter('the_content', array(&$this, 'modifyImageLinks'), 65);
	}

	// plugin initialization
	public function init()
	{
		add_shortcode('bsgallery', array(&$this, 'shortcodeGallery'));
		add_shortcode('bsimage', array(&$this, 'shortcodeImage'));
		add_action('admin_menu', array(&$this, 'addToAdminMenu'));
	}

	// create settings page in the administration section
	public function addToAdminMenu()
	{
		if (function_exists('add_theme_page'))
		{
			add_theme_page('BSGallery', 'BSGallery', 'read',  'bsgallery', array(&$this, 'adminMenuPage'));
		}
	}

	// generate and handle settings page in administration section
	public function adminMenuPage()
	{
		if (!current_user_can('manage_options'))
			die('Permission denied');

		$optLinkClass = get_option('bsgallery-link-class', '');
		$optLinkRel = get_option('bsgallery-link-rel', '');

		// process submitted form data
		if (isset($_POST['bsgallery-action']))
		{
			check_admin_referer('save-options', 'bsgallery-action');

			if (isset($_POST['bsgallery-link-class']))
			{
				$optLinkClassNew = $_POST['bsgallery-link-class'];
				if ($optLinkClassNew != $optLinkClass)
				{
					$optLinkClass = $optLinkClassNew;
					update_option('bsgallery-link-class', $optLinkClass);
				}
			}
			if (isset($_POST['bsgallery-link-rel']))
			{
				$optLinkRelNew = $_POST['bsgallery-link-rel'];
				if ($optLinkRelNew != $optLinkRel)
				{
					$optLinkRel = $optLinkRelNew;
					update_option('bsgallery-link-rel', $optLinkRel);
				}
			}

			?><div id="message" class="updated fade"><p>Saved</p></div><?php
		}
        ?>
		<div class="wrap">
		<h2>BSGallery Options</h2>
		<p>Default settings for BSGallery which could be overriden by attributes of bsgallery shorttag</p>
		<form method="post" name="bsgallery-form" id="bsgallery-form" action="#">

			<div><p>
			<label for="bsgallery-link-class">Gallery link CSS class:
			<input type="text" name="bsgallery-link-class" size="50" maxlength="150" value="<?php echo $optLinkClass; ?>" />
			</label>
			</p></div>

			<div><p>
			<label for="bsgallery-link-rel">Gallery link rel attribute:
			<input type="text" name="bsgallery-link-rel" size="50" maxlength="150" value="<?php echo $optLinkRel; ?>" />
			</label>
			</p></div>

			<p class="submit">
			<input id="submit" class="button-primary" type="submit" name="submit" value="Save" />
			</p>
			<?php wp_nonce_field('save-options','bsgallery-action'); ?>

		</form>
		</div>
        <?php
	}

	// Add a custom field to an attachment in WordPress
	public function onAttachmentFieldsEdit($formFields, $post)
	{
		$setValue = get_post_meta($post->ID, '_bsgallery_set', true);

		$html = '<select name="attachments[' . $post->ID . '][bsgallery_set]" id="attachments[' . $post->ID . '][bsgallery_set]">';
		$html.= '<option value="-">-</option>';
		for ($i = 1; $i < 10; $i++)
		{
			$html .= '<option value="' . $i . '" ';
			if ($setValue == $i)
				$html .= 'selected="yes" ';
			$html .= '>' . $i . '</option>';
		}
		$html.= '<option value="skip">skip</option>';
		$html .= '</select>';
		$formFields['bsgallery_set'] = array(
			'label' => 'Skupina',
			'input' => 'html',
			'value' => $setValue,
			'helps' => 'Zařazení do skupiny',
			'html' => $html);

		return $formFields;
	}

	// Save attachement custom field to post_meta
	function onAttachmentFieldsSave($post, $attachment)
	{
		if (isset($attachment['bsgallery_set']))
			update_post_meta($post['ID'], '_bsgallery_set', $attachment['bsgallery_set']);

		return $post;
    }

	// new shortcode for image gallery from attachments [bsgallery]
	function shortcodeGallery($atts)
	{
		global $post;

		$linkClass = get_option('bsgallery-link-class', NULL);
		$linkRel = get_option('bsgallery-link-rel', NULL);
		$atts = shortcode_atts(array(
			'set' => NULL,
			'template' => 'default'), $atts);

		$args = array('post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $post->ID, 'orderby' => 'menu_order', 'order' => 'ASC'); 
		if (is_numeric($atts['set']))
		{
			$args['meta_query'] = array(array('key' => '_bsgallery_set', 'value' => $atts['set'])); 
		}
		$attachments = get_posts($args);
		if (!$attachments)
			return '';

		// remove not relevant attachments
		foreach($attachments as $key => $attachment)
		{
			if (strpos($attachment->post_mime_type, 'image') === FALSE)
				unset($attachments[$key]);
		}

		if ($atts['template'] == 'list')
		{
			$html = '<div class="bsgallery list">' . "\n";

			foreach($attachments as $attachment)
			{
				$setValue = get_post_meta($attachment->ID, '_bsgallery_set', true);
				// skip images marged as to be skipped
				if ($setValue == 'skip')
					continue;

				$html .= '<div class="bsgallery-item">' . "\n";
				$html .= '<div class="img"><img src="' . wp_get_attachment_thumb_url($attachment->ID) . '" /></div>' . "\n";
				$html .= '<div class="title">' . apply_filters('the_title', $attachment->post_title) . "</div>\n";
				$html .= '<div class="description">' . apply_filters('the_content', $attachment->post_content) . "</div>\n";
				$html .= '</div>' . "\n";
			}
			$html .= '</div>' . "\n";

		}
		else
		{
			$html = '<div class="bsgallery">' . "\n";

			foreach($attachments as $attachment)
			{
				$setValue = get_post_meta($attachment->ID, '_bsgallery_set', true);
				// skip images marged as to be skipped
				if ($setValue == 'skip')
					continue;

				$html .= '<div class="bsgallery-item">' . "\n";
				$html .= '<a ';
				if (!is_null($linkClass))
					$html .= 'class="' . $linkClass . '" ';
				if (!is_null($linkRel))
					//$html .= 'rel="' . $linkRel . '" ';
					$html .= 'data-fancybox-group="' . $linkRel . '" ';
				$html .= ' href="' . wp_get_attachment_url($attachment->ID) . '" ';
				$html .= 'title="' . apply_filters('the_title', $attachment->post_title) . '" ';
				$html .= '><img src="' . wp_get_attachment_thumb_url($attachment->ID) . '" /></a>' . "\n";
				$html .= '</div>' . "\n";
			}
			$html .= '</div>' . "\n";
		}
		$html .= '<div style="clear: both;"></div>' . "\n";

		return $html;
	}

	// new shortcode for single image [bsimage]
	function shortcodeImage($atts)
	{
		global $post;

		$gWidth = get_option('bsgallery-single-width', BSGallery::DEFAULT_SINGLE_WIDTH);

		$linkClass = get_option('bsgallery-link-class', NULL);

		$html = '';
		$atts = shortcode_atts(array(
			'file' => NULL,
			'width' => NULL,), $atts);

		if (is_null($atts['file']))
			return $html . 'Missing image file name (use parameter file="someimage.jpg")';

		$args = array(
			'post_type' => 'attachment',
			'numberposts' => -1,
			'post_mime_type' => 'image',
			'post_status' => null,
			'post_parent' => $post->ID ); 

		$attachments = get_posts($args);
		if ($attachments)
		{
			foreach ($attachments as $attachment)
			{
				$meta = wp_get_attachment_metadata($attachment->ID);
				$imgAtts = wp_get_attachment_image_src($attachment->ID, 'full');
				if ($atts['file'] == basename($meta['file']))
				{
					$imgWidth = is_null($atts['width']) ? $imgAtts[1] : $atts['width'];
					if ($imgWidth > $gWidth) $imgWidth = $gWidth;
					$html .= sprintf('<div id="attachment_%d" class="wp-caption aligncenter" style="width: %dpx">', $attachment->ID, $imgWidth + 10);
					$html .= '<a ';
					if (!is_null($linkClass))
						$html .= 'class="' . $linkClass . '" ';	   
					$html .= 'href="' . wp_get_attachment_url($attachment->ID) . '" ';
					$html .= 'title="' . apply_filters('the_title', $attachment->post_title) . '" ';
					$html .= '><img style="width: ' . $imgWidth . 'px;" src="' . wp_get_attachment_url($attachment->ID) . '" /></a>' . "\n";
					$html .= '<p class="wp-caption-text">' . $attachment->post_excerpt . '</p>';
					$html .= '</div>';
				}
			}
		}
		return $html; 
	}

	// Custom handling of content body - replace all image link titles by image captions (alt attributes)
	function modifyImageLinks($content)
	{
		// get alt from image and use this value to replace link title (for use in lightbox, shutter double etc.)
		return preg_replace_callback('/(<a [^>]+>)[^<]*<img[^>]*title="([^>"]*)"[^>]*>/i', array(&$this, 'modifyImageLinksCallback'), $content);
	}

	// callback function called from regexp in modifyImageLinks
	function modifyImageLinksCallback($a)
	{
		$linkClass = get_option('bsgallery-link-class', NULL);
		if (!is_null($linkClass))
			$linkClass = ' class="' . $linkClass . '"';  

		// check if a has title tag
		if (stripos($a[1], 'title')  === FALSE)
		{
			$result = preg_replace('/<a/', '<a title="' . $a[2] . '"' . $linkClass, $a[0]);

		} else {
			$result = preg_replace("/title='[^']*'/i", "title=\"$a[2]\"" . $linkClass, $a[0]);
		}
		return $result;
	}
}

// create plugin instance at right time
add_action('plugins_loaded', 'BSGallery::getInstance');
?>
