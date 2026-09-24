<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Site;

use ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pathway\Pathway;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionDataService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionItemsService;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;
use stdClass;

class PageSeoSettings
{
    /**
     * The Joomla Document object
     *
     * @var Document
     * @since 5.5.0
     */
    protected $document;

    /**
     * The Joomla Application object
     *
     * @var CMSApplication
     * @since 5.5.0
     */
    protected $app;

    /**
     * The active menu item
     *
     * @var Menu
     * @since 5.5.0
     */
    protected $menu;

    /**
     * The component configuration
     *
     * @var Registry
     * @since 5.5.0
     */
    protected $config;

    /**
     * The Joomla Global Configuration
     *
     * @var Registry
     * @since 5.5.0
     */
    protected $globalConfig;

    /**
     * Page Builder Page attributes
     *
     * @var Registry
     * @since 5.5.0
     */
    protected $attributes;

    /**
     * The page data where the seo settings are applied
     *
     * @var object
     * @since 5.5.0
     */
    protected $pageData;

    /**
     * The collection data associated with the detail page
     *
     * @var object
     * @since 5.5.0
     */
    protected $collectionData;

    /**
     * The constructor
     *
     * @param object $pageData The page data where the seo settings are applied
     *
     * @since 5.5.0
     */
    private function __construct($pageData)
    {
        $this->pageData = $pageData;
        $this->app = Factory::getApplication();
        $this->globalConfig = ApplicationHelper::getAppConfig();
        $this->document = $this->app->getDocument();
        $this->getCollectionItemData();
        $this->getActiveMenu();
        $this->getConfig();
        $this->preparePageAttributes();
    }

    /**
     * Make the class instance statically
     *
     * @param object $pageData
     * @return self
     * @since 5.5.0
     */
    public static function make($pageData)
    {
        return new self($pageData);
    }

    /**
     * Run the seo settings
     *
     * @return self
     * @since 5.5.0
     */
    public function run()
    {
        $this->preparePageTitle();
        $this->prepareArticleMetaTags();
        $this->prepareOgMetaTags();
        $this->prepareTwitterMetaTags();
        $this->preparePageMetaTags();
        $this->preparePathway();
    }

    /**
     * Get the collection item data
     *
     * @return self
     * @since 5.5.0
     */
    protected function getCollectionItemData()
    {
        $itemId = CollectionHelper::getCollectionItemIdFromUrl();

        if (empty($itemId)) {
            return null;
        }
        $input = Factory::getApplication()->input;
        $collectionType = $input->get('collection_type');

        if ($collectionType === 'articles') {
            if (!\class_exists('SppagebuilderHelperArticles')) {
                require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
            }

            try {
                $articlesCount = \SppagebuilderHelperArticles::getArticlesCount();
                $articles = \SppagebuilderHelperArticles::getArticles($articlesCount);
                foreach ($articles as $article) {
                    if ($article->id == $itemId) {
                        $data = CollectionHelper::getDetailPageDataFromArticles();
                        $collectionId = $data['collection_id'] ?? null;
                        $fieldKeys = $this->getFieldKeys($collectionId);

                        foreach ($data as $key => $value) {
                            if (array_key_exists($key, $fieldKeys)) {
                                $data[$fieldKeys[$key]] = $value;
                                unset($data[$key]);
                            }
                        }

                        $this->collectionData = $data;
                        return $this;
                    }
                }
            } catch (\Exception $e) {
            }
        } else if ($collectionType === 'tags') {
            $db = \Joomla\CMS\Factory::getDbo();
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from('#__tags')
                ->where('id = ' . (int) $itemId)
                ->where('published = 1');
            $db->setQuery($query);
            $tagCount = $db->loadResult();
            
            if ($tagCount > 0) {
                $data = CollectionHelper::getDetailPageDataFromTags();
                $collectionId = $data['collection_id'] ?? null;
                $fieldKeys = $this->getFieldKeys($collectionId);

                foreach ($data as $key => $value) {
                    if (array_key_exists($key, $fieldKeys)) {
                        $data[$fieldKeys[$key]] = $value;
                        unset($data[$key]);
                    }
                }

                $this->collectionData = $data;
                return $this;
            }
        } else {
            $data = (new CollectionDataService())->fetchCollectionItemById($itemId);
            $collectionId = $data['collection_id'] ?? null;
            $fieldKeys = $this->getFieldKeys($collectionId);
    
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $fieldKeys)) {
                    $data[$fieldKeys[$key]] = $value;
                    unset($data[$key]);
                }
            }
    
            $this->collectionData = $data;
        }
        return $this;
    }

    /**
     * Get the field keys
     *
     * @param int $collectionId
     * @return array
     * @since 5.5.0
     */
    protected function getFieldKeys($collectionId)
    {
        if (empty($collectionId)) {
            return [];
        }

        if ($collectionId === CollectionIds::ARTICLES_COLLECTION_ID) {
            // Articles source - return predefined field keys
            return [
                'title' => '{{title}}',
                'alias' => '{{alias}}',
                'introtext' => '{{intro text}}',
                'fulltext' => '{{full text}}',
                'featured_image' => '{{featured image}}',
                'image_intro' => '{{intro image}}',
                'image_fulltext' => '{{full image}}',
                'image_intro_caption' => '{{intro image caption}}',
                'image_fulltext' => '{{full image caption}}',
                'link' => '{{article link}}',
                'username' => '{{author}}',
                'category' => '{{category}}',
                'created date' => '{{created date}}',
                'modified' => '{{modified}}',
                'hits' => '{{hits}}',
                'published' => '{{published}}'
            ];
        }

        if ($collectionId === CollectionIds::TAGS_COLLECTION_ID) {
            // Tags source - return predefined field keys
            return [
                'title' => '{{title}}',
                'alias' => '{{alias}}',
                'description' => '{{description}}',
                'created' => '{{created}}',
                'modified' => '{{modified}}',
                'published' => '{{published}}'
            ];
        }

        $fields = CollectionField::where('collection_id', $collectionId)
            ->whereIn('type', [
                FieldTypes::TITLE,
                FieldTypes::TEXT,
                FieldTypes::ALIAS,
                FieldTypes::IMAGE,
                FieldTypes::OPTION,
                FieldTypes::RICH_TEXT,
            ])->get();

        if (empty($fields)) {
            return [];
        }

        $fields = Arr::make($fields);
        return $fields->reduce(function ($carry, $field) {
            $name = '{{' . strtolower($field->name) . '}}';
            $key = CollectionItemsService::createFieldKey($field->id);
            $carry[$key] = $name;
            return $carry;
        }, [])->toArray();
    }

    /**
     * Parse the variable
     *
     * @param string $value
     * @return string
     * @since 5.5.0
     */
    protected function parseVariable($value, $isStripTags = false)
    {
        if (empty($value) || empty($this->collectionData)) {
            return $value;
        }

        $value = strtolower($value);
        $pattern = '/{{([^}]+)}}/';
        preg_match_all($pattern, $value, $matches);

        if (empty($matches[0])) {
            return $value;
        }

        foreach ($matches[0] as $match) {
            $replacement = $this->collectionData[$match] ?? null;

            if (is_null($replacement)) {
                continue;
            }

            if ($isStripTags) {
                $replacement = strip_tags($replacement);
            }

            $value = str_replace($match, $replacement, $value);
        }

        return $value;
    }

    /**
     * Get the active menu
     *
     * @return self
     * @since 5.5.0
     */
    protected function getActiveMenu()
    {
        $menu = $this->app->getMenu();
        $this->menu = $menu->getActive();

        return $this;
    }

    /**
     * Get the config
     *
     * @return self
     * @since 5.5.0
     */
    protected function getConfig()
    {
        $this->config = ComponentHelper::getParams('com_sppagebuilder');
        return $this;
    }

    /**
     * Prepare the page attributes
     *
     * @return self
     * @since 5.5.0
     */
    protected function preparePageAttributes()
    {
        $attributes = $this->pageData->attribs ?? new stdClass();
        $attributes = is_string($attributes) ? json_decode($attributes) : $attributes;

        if(is_array($attributes)) {
            $attributes = (object) $attributes;
        }
        

        $ogTitle = $this->pageData->og_title ?? '';
        $ogImage = $this->pageData->og_image ?? '';
        $ogDescription = $this->pageData->og_description ?? '';
        $ogAlt = '';
        $metaDescription = $attributes->meta_description ?? '';

        if (!empty($ogImage) && is_string($ogImage)) {
            if (preg_match("@^{@", $ogImage)) {
                $ogImage = json_decode($ogImage);
            }

            if(!empty($ogImage->alt)){
                $ogAlt = $ogImage->alt;
            }

            if (!empty($ogImage->src)) {
                $ogImage = $ogImage->src;
            }
        }

        if (is_object($ogImage)) {
            if(isset($ogImage->src)) {
                $ogImage = $ogImage->src;
            } else {
                $ogImage = '';
            }
        }

        $attributes->og_title = $this->parseVariable($ogTitle);
        $attributes->og_image = $this->parseVariable($ogImage) ?? '';
        $attributes->og_alt = $this->parseVariable($ogAlt);
        $attributes->meta_description = $this->parseVariable($metaDescription, true) ?? '';


        
        if (!empty($attributes->og_image) && stripos($attributes->og_image, 'http') !== 0) {
            $attributes->og_image = Uri::root() . $attributes->og_image;
        }

        $attributes->og_description = $this->parseVariable($ogDescription, true);
        $this->attributes = new Registry($attributes);
        return $this;
    }

    /**
     * Get the page title
     *
     * @return string
     * @since 5.5.0
     */
    protected function getPageTitle()
    {
        $menuParams = $this->getMenuParams();
        $pageTitle = $menuParams->get('page_title', '');

        if (empty($pageTitle)) {
            $pageTitle = $this->menu->title ?? '';
        }

        if (!empty($this->collectionData)) {
            $titleValue = $this->getTitleFieldValue();
            if (!empty($titleValue)) {
                $pageTitle = $titleValue;
            }
        }

        $globalTitle = (int) $this->globalConfig->get('sitename_pagetitles');

        if ($globalTitle === 2) {
			$pageTitle = Text::sprintf('JPAGETITLE', $pageTitle, $this->app->get('sitename'));
		} elseif ($globalTitle === 1) {
			$pageTitle = Text::sprintf('JPAGETITLE', $this->app->get('sitename'), $pageTitle);
		}

        return $pageTitle;
    }

    /**
     * Get the value of the title field by type
     *
     * @return string|null
     * @since 6.2.0
     */
    protected function getTitleFieldValue()
    {
        if (empty($this->collectionData)) {
            return null;
        }

        $collectionId = $this->collectionData['collection_id'] ?? null;

        if (empty($collectionId)) {
            return null;
        }

        if ($collectionId === CollectionIds::ARTICLES_COLLECTION_ID || $collectionId === CollectionIds::TAGS_COLLECTION_ID) {
            return $this->collectionData['{{title}}'] ?? null;
        }

        $titleField = CollectionField::where('collection_id', $collectionId)
            ->where('type', FieldTypes::TITLE)
            ->first();

        if (empty($titleField)) {
            return null;
        }

        $titleFieldKey = '{{' . strtolower($titleField->name) . '}}';
        
        return $this->collectionData[$titleFieldKey] ?? null;
    }

    /**
     * Prepare the page title
     *
     * @return self
     * @since 5.5.0
     */
    protected function preparePageTitle()
    {
        $this->document->setTitle($this->getPageTitle());
        return $this;
    }

    /**
     * Prepare the article meta tags
     *
     * @return self
     * @since 5.5.0
     */
    protected function prepareArticleMetaTags()
    {
        $params = ComponentHelper::getParams('com_sppagebuilder');
        $isOgDisabled = $params->get('disable_og', false);

        $author = $this->attributes->get('author', '');
        $author = $author ? $this->parseVariable($author) : $this->pageData->author_name ?? '';
        $this->document->addCustomTag('<meta property="article:author" content="' . $author . '"/>');

        $publishedTime = $this->pageData->created_on ?? '';
        $this->document->addCustomTag('<meta property="article:published_time" content="' . $publishedTime . '"/>');

        $modifiedTime = $this->pageData->modified ?? '';
        $this->document->addCustomTag('<meta property="article:modified_time" content="' . $modifiedTime . '"/>');

        $language = $this->pageData->language ?? $this->app->getLanguage()->getTag();
        $language = $language === '*' ? $this->app->getLanguage()->getTag() : $language;
        $language = str_replace('-', '_', $language);

        if(!$isOgDisabled){
            $this->document->addCustomTag('<meta property="og:locale" content="' . $language . '" />');
        }

        return $this;
    }

    /**
     * Prepare the og meta tags
     *
     * @return self
     * @since 5.5.0
     */
    protected function prepareOgMetaTags()
    {
        $params = ComponentHelper::getParams('com_sppagebuilder');
        $isOgDisabled = $params->get('disable_og', false);

        if ($isOgDisabled) {
            return;
        }

        $ogTitle = $this->attributes->get('og_title', '');
        $ogTitle = $ogTitle ?? $this->pageData->og_title ?? '';

        if (!empty($ogTitle)) {
            $this->document->addCustomTag('<meta property="og:title" content="' . $ogTitle . '" />');
        } else {
            $this->document->addCustomTag('<meta property="og:title" content="' . $this->getPageTitle() . '" />');
        }

        $ogType = $this->attributes->get('og_type', 'website');
        $this->document->addCustomTag('<meta property="og:type" content="' . $ogType . '" />');
        $this->document->addCustomTag('<meta property="og:url" content="' . Uri::getInstance()->toString() . '" />');

        $facebookAppId = $this->config->get('fb_app_id', '');

        if (!empty($facebookAppId)) {
            $this->document->addCustomTag('<meta property="fb:app_id" content="' . $facebookAppId . '" />');
        }

        $siteName = $this->globalConfig->get('sitename', '');

        if (!empty($siteName)) {
            $this->document->addCustomTag('<meta property="og:site_name" content="' . $siteName . '" />');
        }

        $ogImage = $this->attributes->get('og_image', null);
        $ogAlt = $this->attributes->get('og_alt', null);

        if (!empty($ogImage)) {
            $this->document->addCustomTag('<meta property="og:image" content="' . $ogImage . '" />');
            $this->document->addCustomTag('<meta property="og:image:width" content="1200" />');
            $this->document->addCustomTag('<meta property="og:image:height" content="630" />');
            $this->document->addCustomTag('<meta property="og:image:alt" content="' . $ogAlt . '" />');
        }

        $ogDescription = $this->attributes->get('og_description', null);

        if (!empty($ogDescription)) {
            $this->document->addCustomTag('<meta property="og:description" content="' . $ogDescription . '" />');
        }

        return $this;
    }

    /**
     * Prepare the twitter meta tags
     *
     * @return self
     * @since 5.5.0
     */
    protected function prepareTwitterMetaTags()
    {
        $params = ComponentHelper::getParams('com_sppagebuilder');
        $isTwitterDisabled = $params->get('disable_tc', false);

        if ($isTwitterDisabled) {
            return;
        }

        $siteName = $this->globalConfig->get('sitename', '');
        $ogTitle = $this->attributes->get('og_title', '');
        $ogDescription = $this->attributes->get('og_description', '');
        $ogImage = $this->attributes->get('og_image', '');
        $title = !empty($ogTitle) ? $ogTitle : $this->getPageTitle();

        $this->document->addCustomTag('<meta name="twitter:card" content="summary" />');

        if (!empty($title)) {
            $this->document->addCustomTag('<meta name="twitter:title" content="' . $title . '" />');
        }

        if (!empty($siteName)) {
            $this->document->addCustomTag('<meta name="twitter:site" content="@' . htmlspecialchars($siteName ?? '') . '" />');
        }

        if (!empty($ogDescription)) {
            $this->document->addCustomTag('<meta name="twitter:description" content="' . strip_tags($ogDescription) . '" />');
        }

        if (!empty($ogImage)) {
            $this->document->addCustomTag('<meta name="twitter:image" content="' . $ogImage . '" />');
        }

        return $this;
    }

    /**
     * Get the active menu params managing Joomla versions.
     *
     * @return Registry
     * @since 5.5.1
     */
    protected function getMenuParams()
    {
        if (empty($this->menu)) {
            return new Registry();
        }

        if (JVERSION < 4) {
            $menuParams = new Registry();

            if (!empty($this->menu)) {
                $menuParams->loadString($this->menu->params);
            }
        } else {
            $menuParams = $this->menu->getParams();
        }

        return $menuParams;
    }

    /**
     * Prepare the page meta tags
     *
     * @return self
     * @since 5.5.0
     */
    protected function preparePageMetaTags()
    {
        $metaDescription = $this->attributes->get('meta_description', '');
        $metaKeywords = $this->attributes->get('meta_keywords', '');
        $globalConfigRobot = Factory::getApplication()->get('robots', 'index, follow');
        $robots = $this->attributes->get('robots', $globalConfigRobot);
        $menuParams = $this->getMenuParams();

        if (!empty($this->menu)) {
            $metaDescription = $menuParams->get('menu-meta_description', $metaDescription);
            $metaKeywords = $menuParams->get('menu-meta_keywords', $metaKeywords);
            $robots = $menuParams->get('robots', $robots);
        }

        if (!empty($metaDescription)) {
            $this->document->setDescription($metaDescription);
        }

        if (!empty($metaKeywords)) {
            $this->document->setMetadata('keywords', $metaKeywords);
        }

        if (!empty($robots)) {
            $this->document->setMetadata('robots', $robots);
        }

        return $this;
    }

    /**
     * Prepare the pathway for dynamic content detail pages
     *
     * @return self
     * @since 6.2.3
     */
    protected function preparePathway()
    {
        if (empty($this->collectionData)) {
            return $this;
        }

        $pathway = $this->app->getPathway();
        
        if (empty($pathway)) {
            return $this;
        }

        $titleValue = $this->getTitleFieldValue();
        
        if (empty($titleValue)) {
            return $this;
        }

        $currentUri = Uri::getInstance();
        $currentPath = $currentUri->getPath();
        
        $query = $currentUri->getQuery();
        if (!empty($query)) {
            $currentPath .= '?' . $query;
        }

        $pathway->addItem($titleValue, $currentPath);

        return $this;
    }
}
