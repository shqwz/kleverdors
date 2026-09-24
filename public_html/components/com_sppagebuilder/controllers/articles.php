<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;

class SppagebuilderControllerArticles extends FormController
{
    private function sendResponse($response, int $statusCode = 200)
	{
		return response()->json($response, $statusCode);
	}

    /**
	 * Load more articles for pagination
	 *
	 * @return void
	 * @since 6.0.0
	 */
	public function loadMoreArticles()
	{
		$app = Factory::getApplication();
		$input = $app->input;

		$rawData = file_get_contents('php://input');
		$data = json_decode($rawData, true);

		if (!$data) {
			$this->sendResponse(['error' => 'Invalid JSON data'], 400);
			return;
		}

		$addonId = $data['addon_id'] ?? '';
		$limit = (int) ($data['limit'] ?? 5);
		$ordering = $data['ordering'] ?? 'latest';
		$catid = $data['catid'] ?? [];
		$includeSubcat = (bool) ($data['include_subcat'] ?? true);
		$postType = $data['post_type'] ?? '';
		$tagids = $data['tagids'] ?? [];
		$page = (int) ($data['page'] ?? 1);
		$addonSettings = $data['addonSettings'] ?? [];

		require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';

		try {
			$items = SppagebuilderHelperArticles::getArticles(
				$limit, 
				$ordering, 
				$catid, 
				$includeSubcat, 
				$postType, 
				$tagids, 
				1, 
				$page
			);

			if (empty($items)) {
				$this->sendResponse(['data' => '', 'message' => 'No more articles']);
				return;
			}

			$html = $this->renderArticlesHTML($items, $addonId, $addonSettings);

			$this->sendResponse(['data' => $html, 'success' => true]);

		} catch (Exception $e) {
			$this->sendResponse(['error' => $e->getMessage()], 500);
		}
	}

	/**
	 * Render articles HTML for pagination
	 *
	 * @param array $items The articles to render
	 * @param string $addonId The addon ID
	 * @param string $addonSettings The addon settings JSON
	 * @return string The rendered HTML
	 * @since 6.0.0
	 */
	private function renderArticlesHTML($items, $addonId, $addonSettings)
	{
		$html = '';
		$addonId = $addonId ?? null;
		$settings = json_decode($addonSettings, true);
		
		$layout = $settings['layout'] ?? 'default';
		$columns_lg = !empty($settings['columns_original']['xl']) ? $settings['columns_original']['xl'] : 3;
		$columns_md = !empty($settings['columns_original']['lg']) ? $settings['columns_original']['lg'] : $columns_lg;
		$columns_sm = !empty($settings['columns_original']['md']) ? $settings['columns_original']['md'] : $columns_md;
		$columns_xs = !empty($settings['columns_original']['sm']) ? $settings['columns_original']['sm'] : 2;
		$columns = !empty($settings['columns_original']['xs']) ? $settings['columns_original']['xs'] : 1;
		
		$article_heading_selector = $settings['article_heading_selector'] ?? 'h3';
		$show_intro = (int) ($settings['show_intro'] ?? 1);
		$intro_limit = (int) ($settings['intro_limit'] ?? 200);
		$hide_thumbnail = (int) ($settings['hide_thumbnail'] ?? 0);
		$show_author = (int) ($settings['show_author'] ?? 1);
		$show_tags = (int) ($settings['show_tags'] ?? 1);
		$show_category = (int) ($settings['show_category'] ?? 1);
		$show_date = (int) ($settings['show_date'] ?? 1);
		$show_readmore = (int) ($settings['show_readmore'] ?? 1);
		$readmore_text = $settings['readmore_text'] ?? 'Read More';
		$thumb_size = $settings['thumb_size'] ?? 'image_thumbnail';
		$show_date_text = $settings['show_date_text'] ?? '';
		$show_last_modified_date = (int) ($settings['show_last_modified_date'] ?? 0);
		$show_last_modified_date_text = $settings['show_last_modified_date_text'] ?? '';
		$show_custom_field = (int) ($settings['show_custom_field'] ?? 0);
		
		$version = new \Joomla\CMS\Version();
		$JoomlaVersion = $version->getShortVersion();
		
		$article_modified_date = \Joomla\CMS\Component\ComponentHelper::getParams('com_content')->get('show_modify_date');
		$article_created_date = \Joomla\CMS\Component\ComponentHelper::getParams('com_content')->get('show_publish_date');
		
		$layoutWrapperCls = 'sppb-addon-article-layout ';
		$layoutContentCls = 'sppb-addon-article-layout-content ';
		
		if ($layout === 'default' || $layout === '' || $layout === null) {
			$layoutWrapperCls .= 'sppb-col-xs-' . round(12 / $columns_xs) . ' sppb-col-sm-' . round(12 / $columns_sm) . ' sppb-col-md-' . round(12 / $columns_md) . ' sppb-col-lg-' . round(12 / $columns_lg) . ' sppb-col-' . round(12 / $columns);
		}
		
		if ($layout === 'editorial') {
			$layoutWrapperCls .= ' sppb-addon-article-layout-editorial-wrapper';
			$layoutContentCls .= ' sppb-addon-article-layout-editorial-content';
		} elseif ($layout === 'side') {
			$layoutWrapperCls .= ' sppb-col-12 sppb-addon-article-layout-side-wrapper';
			$layoutContentCls .= ' sppb-addon-article-layout-side-content';
		} elseif ($layout === 'magazine') {
			$layoutWrapperCls .= ' sppb-addon-article-layout-magazine-wrapper';
			$layoutContentCls .= ' sppb-addon-article-layout-magazine-content';
		} elseif ($layout === 'masonry') {
			$layoutWrapperCls .= ' sppb-addon-article-layout-masonry-wrapper';
			$layoutContentCls .= ' sppb-addon-article-layout-masonry-content';
		}
		
		foreach ($items as $key => $item) {
			$html .= '<div class="' . $layoutWrapperCls . '">';
			$html .= '<div class="sppb-addon-article ' . $layoutContentCls . '">';

			if (!$hide_thumbnail) {
				$image = $item->{$thumb_size} ?? $item->image_thumbnail;

				if ($item->post_format === 'gallery') {
					if (count((array) $item->imagegallery->images)) {
						$html .= '<div class="sppb-carousel sppb-slide" data-sppb-ride="sppb-carousel">';
						$html .= '<div class="sppb-carousel-inner">';
						foreach ($item->imagegallery->images as $key => $gallery_item) {
							$active_class = '';
							if ($key == 0) {
								$active_class = ' active';
							}
							if (isset($gallery_item['thumbnail']) && $gallery_item['thumbnail']) {
								$html .= '<div class="sppb-item' . $active_class . '">';
								$html .= '<img src="' . $gallery_item['thumbnail'] . '" alt="">';
								$html .= '</div>';
							} elseif (isset($gallery_item['full']) && $gallery_item['full']) {
								$html .= '<div class="sppb-item' . $active_class . '">';
								$html .= '<img src="' . $gallery_item['full'] . '" alt="">';
								$html .= '</div>';
							}
						}
						$html .= '</div>';
						
						if ($layout !== 'magazine') {
							$html .= '<a class="left sppb-carousel-control" role="button" data-slide="prev" aria-label="Previous"><i class="fa fa-angle-left" aria-hidden="true"></i></a>';
							$html .= '<a class="right sppb-carousel-control" role="button" data-slide="next" aria-label="Next"><i class="fa fa-angle-right" aria-hidden="true"></i></a>';
						}
						
						$html .= '</div>';
					} elseif (isset($item->image_thumbnail) && $item->image_thumbnail) {
						$placeholder = $item->image_thumbnail == '' ? false : $this->get_image_placeholder($item->image_thumbnail);

						$img_obj = json_decode($item->images);
						$img_obj_helix = json_decode($item->attribs);

						$img_blog_op_alt_text = (isset($img_obj->image_intro_alt) && $img_obj->image_intro_alt) ? $img_obj->image_intro_alt : "";
						$img_helix_alt_text = (isset($img_obj_helix->helix_ultimate_image_alt_txt) && $img_obj_helix->helix_ultimate_image_alt_txt) ? $img_obj_helix->helix_ultimate_image_alt_txt : "";
						$img_alt_text = "";

						if ($img_helix_alt_text) {
							$img_alt_text = $img_helix_alt_text;
						} else if ($img_blog_op_alt_text) {
							$img_alt_text = $img_blog_op_alt_text;
						} else {
							$img_alt_text = $item->title;
						}

						$html .= '<a href="' . $item->link . '" itemprop="url"><img class="sppb-img-responsive' . ($placeholder ? ' sppb-element-lazy' : '') . '" src="' . ($placeholder ? $placeholder : $item->image_thumbnail) . '" alt="' . $img_alt_text . '" itemprop="thumbnailUrl" ' . ($placeholder ? 'loading="lazy" data-large="' . $image . '"' : '') . '></a>';
					}
				} elseif ($item->post_format == 'video' && isset($item->video_src) && $item->video_src) {
					$html .= '<div class="entry-video embed-responsive embed-responsive-16by9">';
					$html .= '<object class="embed-responsive-item" style="width:100%;height:100%;" data="' . $item->video_src . '">';
					$html .= '<param name="movie" value="' . $item->video_src . '">';
					$html .= '<param name="wmode" value="transparent" />';
					$html .= '<param name="allowFullScreen" value="true">';
					$html .= '<param name="allowScriptAccess" value="always"></param>';
					$html .= '<embed src="' . $item->video_src . '" type="application/x-shockwave-flash" allowscriptaccess="always"></embed>';
					$html .= '</object>';
					$html .= '</div>';
				} elseif ($item->post_format == 'audio' && isset($item->audio_embed) && $item->audio_embed) {
					$html .= '<div class="entry-audio embed-responsive embed-responsive-16by9">';
					$html .= $item->audio_embed;
					$html .= '</div>';
				} elseif ($item->post_format == 'link' && isset($item->link_url) && $item->link_url) {
					$html .= '<div class="entry-link">';
					$html .= '<a target="_blank" rel="noopener noreferrer" href="' . $item->link_url . '"><h4>' . $item->link_title . '</h4></a>';
					$html .= '</div>';
				} else {
					if (isset($image) && $image) {
						$default_placeholder = $image == '' ? false : $this->get_image_placeholder($image);

						$img_obj = json_decode($item->images);
						$img_obj_helix = json_decode($item->attribs);

						$img_blog_op_alt_text = (isset($img_obj->image_intro_alt) && $img_obj->image_intro_alt) ? $img_obj->image_intro_alt : "";
						$img_helix_alt_text = (isset($img_obj_helix->helix_ultimate_image_alt_txt) && $img_obj_helix->helix_ultimate_image_alt_txt) ? $img_obj_helix->helix_ultimate_image_alt_txt : "";
						$img_alt_text = "";

						if ($img_helix_alt_text) {
							$img_alt_text = $img_helix_alt_text;
						} else if ($img_blog_op_alt_text) {
							$img_alt_text = $img_blog_op_alt_text;
						} else {
							$img_alt_text = $item->title;
						}

						$html .= '<a class="sppb-article-img-wrap" href="' . $item->link . '" itemprop="url"><img class="sppb-img-responsive' . ($default_placeholder ? ' sppb-element-lazy' : '') . '" src="' . ($default_placeholder ? $default_placeholder : $image) . '" alt="' . $img_alt_text . '" itemprop="thumbnailUrl" ' . ($default_placeholder ? 'loading="lazy" data-large="' . $image . '"' : '') . '></a>';
					}
				}
			}

			$html .= '<div class="sppb-article-info-wrap" role="article">';
			$html .= '<' . $article_heading_selector . '><a href="' . $item->link . '" itemprop="url">' . $item->title . '</a></' . $article_heading_selector . '>';

			if ($show_author || $show_category || $show_date || $show_tags) {
				$html .= '<div class="sppb-article-meta">';

				if ($show_date) {
					$date = ($article_created_date) ? \Joomla\CMS\HTML\HTMLHelper::_('date', $item->publish_up, 'DATE_FORMAT_LC3') : '<p class="alert alert-warning">Date display is disabled in Joomla settings</p>';
					$date_text = ($show_date_text) ? '<b>' . $show_date_text . ': </b>' : '';
					$date_format = ($article_created_date) ? \Joomla\CMS\HTML\HTMLHelper::_('date', $item->publish_up, 'c') : '';
					$html .= '<time datetime="' . $date_format . '" class="sppb-meta-date sppb-meta-date-unmodified">' . $date_text . $date . '</time>';
				}

				if ($show_last_modified_date) {
					$modify_date = ($article_modified_date) ? \Joomla\CMS\HTML\HTMLHelper::_('date', $item->modified, 'DATE_FORMAT_LC3') : '<p class="alert alert-warning">Modified date display is disabled in Joomla settings</p>';
					$modify_text = ($show_last_modified_date_text) ? '<b>' . $show_last_modified_date_text . ': </b>' : '';
					$modify_date_format = ($article_modified_date) ? \Joomla\CMS\HTML\HTMLHelper::_('date', $item->modified, 'c') : '';
					$html .= '<time datetime="' . $modify_date_format . '" class="sppb-meta-date sppb-meta-date-modified">' . $modify_text . $modify_date . '</time>';
				}

				if ($show_category) {
					$item->catUrl = \Joomla\CMS\Router\Route::_(\Joomla\Component\Content\Site\Helper\RouteHelper::getCategoryRoute($item->catslug));
					$html .= '<span class="sppb-meta-category"><a href="' . $item->catUrl . '" itemprop="genre">' . $item->category . '</a></span>';
				}

				if ($show_author) {
					$author = ($item->created_by_alias ? $item->created_by_alias : $item->username);
					$html .= '<span class="sppb-meta-author" itemprop="name">' . $author . '</span>';
				}

				if ($show_tags) {
					$tagLayout = new \Joomla\CMS\Layout\FileLayout('joomla.content.tags');
					$html .= $tagLayout->render($item->tags->itemTags);
				}

				$html .= '</div>';
			}

			if ($show_custom_field) {
				if ((float) $JoomlaVersion >= 4) {
					\JLoader::registerAlias('FieldsHelper', 'Joomla\Component\Fields\Administrator\Helper\FieldsHelper');
				} else {
					\JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
				}

				$custom_fields = FieldsHelper::getFields('com_content.article', $item);
				$this->renderCustomFields($custom_fields);

				$html .= FieldsHelper::render(
					'com_content.article',
					'fields.render',
					array(
						'context' => 'com_content.article',
						'item'    => $item,
						'fields'  => $custom_fields,
					)
				);
			}

			if ($show_intro) {
				if (!isset($custom_fields)) {
					if ((float) $JoomlaVersion >= 4) {
						\JLoader::registerAlias('FieldsHelper', 'Joomla\Component\Fields\Administrator\Helper\FieldsHelper');
					} else {
						\JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
					}
					$custom_fields = FieldsHelper::getFields('com_content.article', $item);
					$this->renderCustomFields($custom_fields);
				}
				$introtext = $this->replaceFieldShortcodes($item->introtext, $custom_fields);
				$html .= '<div class="sppb-article-introtext">' . $this->truncateHtml($introtext, $intro_limit) . '</div>';
			}

			if ($show_readmore) {
				$max_title_characters = 25;
				$aria_label = strlen($item->title) > $max_title_characters ? mb_substr(strip_tags($item->title), 0, $max_title_characters, 'UTF-8') . '...' : strip_tags($item->title);
				$full_aria_label = 'Read more about ' . $aria_label;

				$html .= '<a class="sppb-readmore" href="' . $item->link . '" aria-label="' . $full_aria_label . '" itemprop="url">' . $readmore_text . '</a>';
			}

			$html .= '</div>'; // .sppb-article-info-wrap
			$html .= '</div>';
			$html .= '</div>';
		}
		
		return $html;
	}
	
	/**
	 * Truncate HTML content
	 *
	 * @param string $html The HTML content
	 * @param int $limit The character limit
	 * @return string The truncated HTML
	 * @since 6.0.0
	 */
	private function truncateHtml($html, $limit)
	{
		if (empty($html)) {
			return '';
		}
		
		$dom = new DOMDocument();
		@$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		
		$content = '';
		$charCount = 0;
		$this->traverseNodes($dom->documentElement, $content, $charCount, $limit);
		
		return $content;
	}
	
	/**
	 * Traverse DOM nodes for HTML truncation
	 *
	 * @param DOMNode $node The DOM node
	 * @param string $content The content string
	 * @param int $charCount The character count
	 * @param int $limit The character limit
	 * @return void
	 * @since 6.0.0
	 */
	private function traverseNodes($node, &$content, &$charCount, $limit)
	{
		foreach ($node->childNodes as $child) {
			if ($child->nodeType == XML_TEXT_NODE) {
				$text = $child->textContent;
				$remainingChars = $limit - $charCount;

				if (mb_strlen($text) <= $remainingChars) {
					$content .= htmlspecialchars($text);
					$charCount += mb_strlen($text);
				} else {
					$content .= htmlspecialchars(mb_substr($text, 0, $remainingChars)) . '...';
					$charCount = $limit;
					return;
				}
			} elseif ($child->nodeType == XML_ELEMENT_NODE) {
				$content .= '<' . $child->nodeName;
				if ($child->hasAttributes()) {
					foreach ($child->attributes as $attr) {
						$content .= ' ' . $attr->nodeName . '="' . htmlspecialchars($attr->nodeValue) . '"';
					}
				}
				$content .= '>';
	
				$this->traverseNodes($child, $content, $charCount, $limit);
	
				$content .= '</' . $child->nodeName . '>';
				if ($charCount >= $limit) return;
			}
		}
	}
	
	/**
	 * Replace field shortcodes in text
	 *
	 * @param string $text The text content
	 * @param array $custom_fields The custom fields
	 * @return string The processed text
	 * @since 6.0.0
	 */
	private function replaceFieldShortcodes($text, $custom_fields)
	{
		$fieldMap = [];
		foreach ($custom_fields as $field) {
			if (isset($field->id)) {
				$fieldMap[$field->id] = $field->value;
			}
		}

		return preg_replace_callback('/\{field\s+(\d+)\}/', function($matches) use ($fieldMap) {
			$fieldId = $matches[1];
			return isset($fieldMap[$fieldId]) ? $fieldMap[$fieldId] : '';
		}, $text);
	}
	
	/**
	 * Render custom fields
	 *
	 * @param array $custom_fields The custom fields
	 * @since 6.0.0
	 */
	private function renderCustomFields(&$custom_fields)
	{
		foreach ($custom_fields as $custom_field) {
			if (!empty($custom_field->type) && $custom_field->type === 'url' && !empty($custom_field->value)) {
				$custom_field->value = '<a href="' . $custom_field->value . '">' . $custom_field->value . '</a>';
			}

			if (!empty($custom_field->type) && $custom_field->type === 'media' && !empty($custom_field->value)) {
				$media = json_decode($custom_field->value);		
				if (isset($media->imagefile) && !empty($media->imagefile)) {
					$custom_field->value = '<img src="' . $media->imagefile . '" alt="' . htmlspecialchars($media->alt_text, ENT_QUOTES, 'UTF-8') . '" />';
				} else {
					$custom_field->value = '';
				}
			}

			if(!empty($custom_field->type) && $custom_field->type === 'calendar' && !empty($custom_field->value)) {
				$date = explode(' ', $custom_field->value)[0];
				$custom_field->value = '<span>' . $date . '</span>';
			}

			if(!empty($custom_field->type) && $custom_field->type === 'checkboxes' && !empty($custom_field->value)){
				$values = $custom_field->value;
				$updated_values = [];
				$checkbox_values = $custom_field->fieldparams->get('options', []);
				if(is_array($values)) {
					foreach($values as $value){
						foreach($checkbox_values as $option){
							if($option->value === $value){
								$updated_values[] = $option->name;
							}
						}
					}
				} else {
					foreach($checkbox_values as $option){
						if($option->value === $values){
							$updated_values = $option->name;
							break;
						}
					}
				}
				if(is_array($updated_values)){
					$custom_field->value = implode(', ', $updated_values);
				} else {
					$custom_field->value = $updated_values;
				}
			}

			if(!empty($custom_field->type) && $custom_field->type === 'radio' && !empty($custom_field->value)){
				$radio_values = $custom_field->fieldparams->get('options', []);
				foreach($radio_values as $option){
					if($option->value === $custom_field->value){
						$custom_field->value = $option->name;
						break;
					}
				}
			}

			if(!empty($custom_field->type) && $custom_field->type === 'list' && !empty($custom_field->value)){
				$list_values = $custom_field->fieldparams->get('options', []);
				foreach($list_values as $option){
					if($option->value === $custom_field->value){
						$custom_field->value = $option->name;
						break;
					}
				}
			}

			if(!empty($custom_field->type) && $custom_field->type === 'user' && !empty($custom_field->value)){
				$user = \Joomla\CMS\Factory::getUser((int)$custom_field->value);
				$custom_field->value = $user->name ?? '';
			}

			if(!empty($custom_field->type) && $custom_field->type === 'usergrouplist' && !empty($custom_field->value)){
				$db = \Joomla\CMS\Factory::getDbo();
				$query = $db->getQuery(true)
					->select($db->quoteName('title'))
					->from($db->quoteName('#__usergroups'))
					->where($db->quoteName('id') . ' = ' . (int) $custom_field->value);
				$db->setQuery($query);

				$groupName = $db->loadResult();
				$custom_field->value = $groupName ? $groupName : '';
			}
		}
	}
	
	/**
	 * Get image placeholder for lazy loading
	 *
	 * @param string $src The image source
	 * @return mixed The placeholder path or false
	 * @since 6.0.0
	 */
	protected function get_image_placeholder($src)
	{
		$config = \Joomla\CMS\Component\ComponentHelper::getParams('com_sppagebuilder');
		$lazyload = $config->get('lazyloadimg', '0');

		if ($lazyload)
		{
			$filename   = basename($src);
			$mediaPath  = 'media/com_sppagebuilder/placeholder';
			$basePath   = JPATH_ROOT . '/' . $mediaPath . '/' . $filename;
			$defaultImg = 'https://sppagebuilder.com/addons/image/image1.jpg';

			if (\Joomla\CMS\Filesystem\File::exists($basePath))
			{
				return $mediaPath . '/' . $filename;
			}
			else
			{
				return $defaultImg;
			}
		}

		return false;
	}
}