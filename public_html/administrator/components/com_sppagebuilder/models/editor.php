<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Dispatcher\Dispatcher as DispatcherDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Collection;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionsService;


if (! \class_exists('EditorUtils')) {
    require_once __DIR__ . './../editor/helpers/EditorUtils.php';
}

/**
 * SPPB Editor Model
 *
 * @since 4.0.0
 */
class SppagebuilderModelEditor extends AdminModel
{

    /**
     * The context for the model.
     *
     * @var string
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->setDispatcher(Factory::getApplication()->getDispatcher());
    }

    /**
     * Method for getting a form.
     *
     * @param array $data Data for the form.
     * @param bool $loadData True if the form is to load its own data (default case), false if not.
     * @return void
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_sppagebuilder.page', 'page', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        $jinput = Factory::getApplication()->input;

        $id = $jinput->get('id', 0);

        // Determine correct permissions to check.
        if ($this->getState('page.id')) {
            $id = $this->getState('page.id');

            // Existing record. Can only edit in selected categories.
            $form->setFieldAttribute('catid', 'action', 'core.edit');

            // Existing record. Can only edit own pages in selected categories.
            $form->setFieldAttribute('catid', 'action', 'core.edit.own');
        } else {
            // New record. Can only create in selected categories.
            $form->setFieldAttribute('catid', 'action', 'core.create');
        }

        $user = Factory::getUser();

        // Modify the form based on Edit State access controls.
        if (
            $id != 0 && (! $user->authorise('core.edit.state', 'com_sppagebuilder.page.' . (int) $id))
            || ($id == 0 && ! $user->authorise('core.edit.state', 'com_sppagebuilder'))
        ) {
            // Disable fields for display.
            $form->setFieldAttribute('published', 'disabled', 'true');

            // Disable fields while saving.
            // The controller has already verified this is an page you can edit.
            $form->setFieldAttribute('published', 'filter', 'unset');
        }

        return $form;
    }

    /**
     * Method to get a single item.
     *
     * @param   mixed  $pk  The primary key value for the item.
     *
     * @return  object|boolean  The item data object on success, false on failure.
     * @since   4.0.0
     */
    public function getItem($pk = null)
    {
        if ($item = parent::getItem($pk)) {
            $item = parent::getItem($pk);

            // Get item language code
            $lang_code = (isset($item->language) && $item->language && explode('-', $item->language)[0]) ? explode('-', $item->language)[0] : '';

            // Preview URL
            $item->link = 'index.php?option=com_sppagebuilder&task=page.edit&id=' . $item->id;

            $item->preview       = SppagebuilderHelperRoute::getPageRoute($item->id, $lang_code);
            $item->frontend_edit = SppagebuilderHelperRoute::getFormRoute($item->id, $lang_code);
        }

        return $item;
    }

    /**
     * Method to get the data that should be injected into the form.
     *
     * @return  mixed  The data for the form.
     * @since   4.0.0
     */
    protected function loadFormData()
    {
        /** @var CMSApplication */
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_sppagebuilder.edit.page.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_sppagebuilder.page', $data);

        return $data;
    }

    /**
     * Check if the user can edit the state of the item.
     *
     * @param object $item The item to check.
     *
     * @return bool True if the user can edit the state, false otherwise.
     * @since 4.0.0
     */
    protected function canEditState($item)
    {
        return Factory::getUser()->authorise('core.edit.state', 'com_sppagebuilder.page.' . $item->id);
    }

    /**
     * Get the table instance.
     *
     * @param string $name The name of the table.
     * @param string $prefix The prefix for the table class.
     * @param array $options Additional options for the table.
     *
     * @return Table The table instance.
     * @since 4.0.0
     */
    public function getTable($name = 'Editor', $prefix = 'SppagebuilderTable', $options = [])
    {
        return Table::getInstance($name, $prefix, $options);
    }

    /**
     * Sort by the given parameter.
     *
     * @param string $param The parameter to sort by.
     *
     * @return object An object containing the field and direction of the sort.
     * @since 4.0.0
     */
    public function sortBy(string $param)
    {
        $firstCharacter = substr($param, 0, 1);
        $orderDirection = "ASC";

        if ($firstCharacter === '-') {
            $param          = substr($param, 1);
            $orderDirection = "DESC";
        }

        $ordering            = new stdClass();
        $ordering->field     = $param;
        $ordering->direction = $orderDirection;

        return $ordering;
    }

    /**
     * Get pages based on the provided page data.
     *
     * @param object $pageData The data for filtering and sorting pages.
     *
     * @return object The response containing total items, total pages, and results.
     * @since 4.0.0
     */
    public function getPages($pageData)
    {
        $search        = $pageData->search;
        $offset        = (int) $pageData->offset;
        $limit         = (int) $pageData->limit;
        $sortBy        = $pageData->sortBy;
        $category      = (int) $pageData->category;
        $language      = $pageData->language;
        $status        = $pageData->status;
        $extension     = $pageData->extension;
        $extensionView = $pageData->extension_view;
        $pageTypes     = $pageData->page_types;
        $type          = $pageData->type;

        if (empty($pageTypes)) {
            $pageTypes = ['page'];
        }

        if (! is_array($pageTypes)) {
            $pageTypes = [$pageTypes];
        }

        if (! empty($type)) {
            $pageTypes = [$type];
        }

        $response = new stdClass();

        try
        {
            $db    = Factory::getDbo();
            $query = $db->getQuery(true);

            $query->select([
                $db->quoteName('p.id'),
                $db->quoteName('p.asset_id'),
                $db->quoteName('p.title'),
                $db->quoteName('p.text'),
                $db->quoteName('p.content'),
                $db->quoteName('p.extension_view'),
                $db->quoteName('p.view_id'),
                $db->quoteName('p.catid'),
                $db->quoteName('p.published'),
                $db->quoteName('p.created_on'),
                $db->quoteName('p.created_by'),
                $db->quoteName('p.language'),
                $db->quoteName('p.hits'),
                $db->quoteName('p.checked_out'),
                $db->quoteName('p.css'),
                $db->quoteName('p.attribs'),
                $db->quoteName('p.og_title'),
                $db->quoteName('p.og_description'),
                $db->quoteName('p.og_image'),
                $db->quoteName('c.title', 'category'),
                $db->quoteName('l.title', 'language_title'),
                $db->quoteName('ug.title', 'access_title'),
            ]);

            $query->from($db->quoteName('#__sppagebuilder', 'p'))
                ->join('LEFT', $db->quoteName('#__categories', 'c'),
                    $db->quoteName('p.catid') . ' = ' . $db->quoteName('c.id'))
                ->join('LEFT', $db->quoteName('#__languages', 'l'),
                    $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('p.language'))
                ->join('LEFT', $db->quoteName('#__viewlevels', 'ug'),
                    $db->quoteName('ug.id') . ' = ' . $db->quoteName('p.access'));

            $query->where($db->quoteName('p.extension') . ' = :extension')
                ->bind(':extension', $extension);

            $query->whereIn($db->quoteName('p.extension_view'), $pageTypes, ParameterType::STRING);

            if (is_numeric($status)) {
                $query->where($db->quoteName('p.published') . ' = :status')
                    ->bind(':status', $status, ParameterType::INTEGER);
            } else {
                $query->whereIn($db->quoteName('p.published'), [0, 1]);
            }

            if (! empty($category)) {
                $query->where($db->quoteName('p.catid') . ' = :category')
                    ->bind(':category', $category, ParameterType::INTEGER);
            }

            if (! empty($language)) {
                $query->where($db->quoteName('p.language') . ' = :language')
                    ->bind(':language', $language);
            }

            if (! empty($search)) {
                $search      = trim($search);
                $searchWords = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

                foreach ($searchWords as $i => $word) {
                    $search = '%' . $word . '%';
                    $query->where($db->quoteName('p.title') . ' LIKE :search' . $i)
                        ->bind(':search' . $i, $search);
                }
            }

            if (! empty($sortBy)) {
                $ordering  = $this->sortBy($sortBy);
                $direction = $ordering->direction === 'ASC' ? 'ASC' : 'DESC';
                $query->order($db->quoteName('p.' . $ordering->field) . ' ' . $direction);
            }

            $query->setLimit($limit, $offset);

            $db->setQuery($query);

            try {
                $results = $db->loadObjectList();
            } catch (\Exception $error) {
                $results = [];
            }

            if (! empty($results)) {
                if (! class_exists('SppagebuilderHelperRoute')) {
                    require_once JPATH_SITE . '/components/com_sppagebuilder/helpers/route.php';
                }

                foreach ($results as &$result) {
                    if ($result->created_on) {
                        $result->created = (new DateTime($result->created_on))->format('j F, Y');
                        unset($result->created_on);
                    }

                    if (! empty($result->created_by)) {
                        $result->author = Factory::getUser($result->created_by)->name;
                    }

                    if (empty($result->category)) {
                        $result->category = '-';
                    }

                    if (! empty($result->content)) {
                        $result->text = $result->content;
                        unset($result->content);
                    }

                    if (! empty($result->attribs)) {
                        $result->attribs = json_decode($result->attribs);
                    }

                    if (! empty($result->og_image)) {
                        $result->og_image = json_decode($result->og_image);
                    }

                    $result->url       = SppagebuilderHelperRoute::getFormRoute($result->id, $result->language, 0, null, $extensionView === 'popup');
                    $result->preview   = $this->getPreviewUrl($result->id, $result->language)['url'] ?? '';
                    $result->page_type = static::getReadablePageType($result->extension_view, $result->view_id);
                    if ($result->view_id === CollectionIds::ARTICLES_COLLECTION_ID) {
                        if ($result->extension_view === 'dynamic_content:index') {
                            $result->page_type = sprintf(Text::_('COM_SPPAGEBUILDER_EDITOR_PAGE_LIST_PAGE_TYPE_DYNAMIC_CONTENT_INDEX'), 'articles');
                        } else if ($result->extension_view === 'dynamic_content:detail') {
                            $result->page_type = sprintf(Text::_('COM_SPPAGEBUILDER_EDITOR_PAGE_LIST_PAGE_TYPE_DYNAMIC_CONTENT_DETAIL'), 'articles');
                        }
                    } else if ($result->view_id === CollectionIds::TAGS_COLLECTION_ID) {
                        if ($result->extension_view === 'dynamic_content:index') {
                            $result->page_type = sprintf(Text::_('COM_SPPAGEBUILDER_EDITOR_PAGE_LIST_PAGE_TYPE_DYNAMIC_CONTENT_INDEX'), 'tags');
                        } else if ($result->extension_view === 'dynamic_content:detail') {
                            $result->page_type = sprintf(Text::_('COM_SPPAGEBUILDER_EDITOR_PAGE_LIST_PAGE_TYPE_DYNAMIC_CONTENT_DETAIL'), 'tags');
                        }
                    }
                }

                unset($result);
            }

            $totalQuery = clone $query;
            $totalQuery->clear('select')->clear('limit')->clear('offset')
                ->select('COUNT(*)');

            $db->setQuery($totalQuery);
            $allItems = (int) $db->loadResult();

            $response->totalItems = $allItems;
            $response->totalPages = ceil($allItems / $limit);
            $response->results    = EditorUtils::parsePageListData($results);
            $response->code       = 200;

            return $response;
        } catch (Exception $error) {
            $response->totalItems = 0;
            $response->totalPages = 0;
            $response->results    = $error->getMessage();
            $response->code       = 500;

            return $response;
        }
    }

    /**
     * Get the preview URL for a page.
     *
     * @param int $pageId The ID of the page.
     * @param string|null $language The language code (optional).
     *
     * @return array An array containing the preview URL.
     * @since 4.0.0
     */
    public function getPreviewUrl($pageId, $language = null)
    {
        if (empty($pageId)) {
            return ['url' => ''];
        }

        $page = Page::where('id', $pageId)->first();

        if ($page->isEmpty()) {
            return ['url' => ''];
        }

        $isAdmin = Factory::getApplication()->isClient('administrator');
        $itemId = SppagebuilderHelperRoute::getMenuItemId($pageId);
        $menuItemId = $itemId ? '&Itemid=' . $itemId : '';
        $pageType = $page->extension_view;

        switch ($pageType) {
            case Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL:
                $firstItemId = CollectionHelper::getFirstCollectionItemId($page->view_id);
                $url         = Route::_(Uri::root() . 'index.php?option=com_sppagebuilder&view=dynamic&collection_item_id[0]=' . $firstItemId . '&collection_type=' . ($page->view_id === CollectionIds::ARTICLES_COLLECTION_ID ? 'articles' : ($page->view_id === CollectionIds::TAGS_COLLECTION_ID ? 'tags' : 'normal-source')), false);
                break;
            case Page::PAGE_TYPE_EASYSTORE_STOREFRONT:
                $url = Route::_(Uri::root() . 'index.php?option=com_easystore&view=products', false);
                break;
            case Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX:
            case Page::PAGE_TYPE_REGULAR:
            default:
                $url = 'index.php?option=com_sppagebuilder&view=page&id=' . $pageId . $menuItemId;

                if (Multilanguage::isEnabled() && ! empty($language) && $language !== '*') {
                    $url .= '&lang=' . $language;
                }

            	$url = $isAdmin ? Route::link('site', $url, false) : Route::_($url, false);

                if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
                    $uri = Uri::getInstance();
                    $url = $uri->toString(['scheme', 'host']) . '/' . ltrim($url, '/');
                }

                break;
        }

        if($pageType === Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL || $pageType === Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX || $pageType === Page::PAGE_TYPE_EASYSTORE_STOREFRONT) {
             if (Multilanguage::isEnabled() && ! empty($language) && $language !== '*') {
                $url .= '&lang=' . $language;
            }
        }

        return ['url' => $url];
    }

    /**
     * Get readable page type.
     *
     * @param string $pageType
     * @param int|null $collectionId
     *
     * @return string
     * @since 5.5.0
     */
    private static function getReadablePageType($pageType, $collectionId = null)
    {
        switch ($pageType) {
            case Page::PAGE_TYPE_REGULAR:
                return Text::_('COM_SPPAGEBUILDER_EDITOR_PAGE_LIST_PAGE_TYPE_PAGE');
            case Page::PAGE_TYPE_POPUP:
                return Text::_('COM_SPPAGEBUILDER_EDITOR_PAGE_LIST_PAGE_TYPE_POPUP');
            case Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX:
                $collection = Collection::find($collectionId);
                if ($collection->isEmpty()) {
                    // @TODO: check this
                    return Text::_('COM_SPPAGEBUILDER_EDITOR_PAGE_LIST_PAGE_TYPE_PAGE');
                }
                return Text::sprintf('COM_SPPAGEBUILDER_EDITOR_PAGE_LIST_PAGE_TYPE_DYNAMIC_CONTENT_INDEX', $collection->alias);
            case Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL:
                $collection = Collection::find($collectionId);
                if ($collection->isEmpty()) {
                    // @TODO: check this
                    return Text::_('COM_SPPAGEBUILDER_EDITOR_PAGE_LIST_PAGE_TYPE_PAGE');
                }
                return Text::sprintf('COM_SPPAGEBUILDER_EDITOR_PAGE_LIST_PAGE_TYPE_DYNAMIC_CONTENT_DETAIL', $collection->alias);
            default:
                return $pageType;
        }
    }

    /**
     * Count total number of pages.
     *
     * @param     string     $keyword    The search keyword.
     *
     * @return     int
     * @since     4.0.0
     */
    public function countTotalPages($keyword = ''): int
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select('COUNT(*)')
            ->from($db->quoteName('#__sppagebuilder'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_sppagebuilder'));

        if (! empty($keyword)) {
            $keyword = preg_quote($keyword, '/');
            $query->where($db->quoteName('title') . ' REGEXP :keyword')
                ->bind(':keyword', $keyword);
        }

        $db->setQuery($query);

        try
        {
            return (int) $db->loadResult();
        } catch (\Exception $error) {
            Factory::getApplication()->enqueueMessage($error->getMessage(), 'error');
            return 0;
        }
    }

    /**
     * Get page content by ID.
     *
     * @param int $id The ID of the page.
     *
     * @return object|null The page content or null if not found.
     * @since 4.0.0
     */
    public function getPageContent(int $id)
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select([
            'id',
            'asset_id',
            'title',
            'text',
            'content',
            'extension',
            'extension_view',
            'view_id',
            'created_by',
            'published',
            'catid',
            'access',
            'created_on',
            'attribs',
            'og_title',
            'og_description',
            'og_image',
            'language',
            'css',
            'version',
        ])->from($db->quoteName('#__sppagebuilder'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $db->setQuery($query);
        $result = null;

        try
        {
            $result = $db->loadObject();
        } catch (\Exception $e) {
            return $result;
        }

        $defaultAttributes = (object) [
            'meta_description' => '',
            'meta_keywords'    => '',
            'robots'           => 'global',
            'author'           => '',
        ];

        $defaultImage = (object) [
            'src' => '',
        ];

        if (! empty($result)) {
            $result->text     = ! empty($result) ? \json_decode($result->text) : null;
            $result->attribs  = \json_decode($result->attribs);
            $result->og_image = \json_decode($result->og_image);

            if (empty($result->attribs)) {
                $result->attribs = $defaultAttributes;
            }

            if (empty($result->og_image)) {
                $result->og_image = $defaultImage;
            }

            $result->url            = SppagebuilderHelperRoute::getFormRoute($result->id, $result->language);
            $result->collection     = null;
            $result->dynamic_fields = [];

            if ($result->extension_view === 'dynamic_content:detail' && !empty($result->view_id && $result->view_id !== -2)) {
                $result->collection     = Collection::where('id', $result->view_id)->first(['id', 'title', 'alias'])->toArray();
                $result->dynamic_fields = $this->getCollectionFields($result->view_id);
            } else if ($result->extension_view === 'dynamic_content:detail' && !empty($result->view_id && $result->view_id == -2)) {
                $result->collection     = [
                    'id' => $result->view_id,
                    'title' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ARTICLES'),
                    'alias' => 'articles',
                ];
                $result->dynamic_fields = $this->getArticleFields();;
            }
        }

        return $result;
    }

    /**
     * Get collection fields for a given collection ID.
     *
     * @param int $collectionId The ID of the collection.
     *
     * @return array An array of fields with their values and labels.
     * @since 4.0.0
     */
    private function getCollectionFields($collectionId)
    {
        if (empty($collectionId)) {
            return [];
        }

        $fields = CollectionField::where('collection_id', $collectionId)
            ->whereIn('type', [
                FieldTypes::TITLE,
                FieldTypes::ALIAS,
                FieldTypes::TEXT,
                FieldTypes::IMAGE,
                FieldTypes::RICH_TEXT,
            ])
            ->get();

        $fields = Arr::make($fields)->reduce(function ($carry, $field) {
            if ($field->type === FieldTypes::IMAGE) {
                $carry['image'] ??= [];
                $carry['image'][] = ['value' => $field->id, 'label' => '{{' . $field->name . '}}', 'type' => $field->type];
            } else {
                $carry['text'] ??= [];
                $carry['text'][] = ['value' => $field->id, 'label' => '{{' . $field->name . '}}', 'type' => $field->type];
            }

            return $carry;
        }, []);

        return $fields->toArray();
    }

    private function getArticleFields()
    {
        $fields = (new CollectionsService)->fetchArticleFieldsForSeo();
        $fields = array_filter($fields, function ($field) {
            return $field['id'] !== 0;
        });

        $fields = Arr::make($fields)->reduce(function ($carry, $field) {
            if ($field['type'] === FieldTypes::IMAGE) {
                $carry['image'] ??= [];
                $carry['image'][] = ['value' => $field['id'], 'label' => '{{' . $field['name'] . '}}', 'type' => $field['type']];
            } else {
                $carry['text'] ??= [];
                $carry['text'][] = ['value' => $field['id'], 'label' => '{{' . $field['name'] . '}}', 'type' => $field['type']];
            }

            return $carry;
        }, []);

        return $fields->toArray();
    }

    /**
     * Get product page content by extension and view.
     *
     * @param string $extension The extension name.
     * @param string $extensionView The view name.
     *
     * @return object|null The product page content or null if not found.
     * @since 4.0.0
     */
    public function getProductPageContent(string $extension, string $extensionView)
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select([
            'id',
            'title',
            'text',
            'content',
            'extension',
            'extension_view',
            'view_id',
            'created_by',
            'catid',
            'access',
            'created_on',
            'attribs',
            'og_title',
            'og_description',
            'og_image',
            'language',
            'published',
            'css',
            'version',
        ])->from($db->quoteName('#__sppagebuilder'))
            ->where($db->quoteName('extension') . ' = :extension')
            ->where($db->quoteName('extension_view') . ' = :extension_view')
            ->bind(':extension', $extension, ParameterType::STRING)
            ->bind(':extension_view', $extensionView, ParameterType::STRING);

        $db->setQuery($query);
        $result = null;

        try
        {
            $result = $db->loadObject();
        } catch (\Exception $error) {
            return $result;
        }

        $defaultAttributes = (object) [
            'meta_description' => '',
            'meta_keywords'    => '',
            'robots'           => 'global',
            'author'           => '',
        ];

        $defaultImage = (object) [
            'src' => '',
        ];

        if (! empty($result)) {
            $result->text     = ! empty($result) ? \json_decode($result->text) : null;
            $result->attribs  = \json_decode($result->attribs);
            $result->og_image = \json_decode($result->og_image);

            if (empty($result->attribs)) {
                $result->attribs = $defaultAttributes;
            }

            if (empty($result->og_image)) {
                $result->og_image = $defaultImage;
            }

            $result->url = SppagebuilderHelperRoute::getFormRoute($result->id, $result->language);
        }

        return $result;
    }

    /**
     * Save page data.
     *
     * @param array $data The page data to save.
     *
     * @throws Exception
     * @since 4.0.0
     */
    public function savePage(array $data)
    {
        $data['css'] = $data['css'] ?? '';

        $db = Factory::getDbo();

        $query = $db->getQuery(true);

        $query->select($db->quoteName(['extension_view', 'extension']))
            ->from($db->quoteName('#__sppagebuilder'))
            ->where($db->quoteName('id') . ' = ' . $db->quote($data['id']));

        $db->setQuery($query);
        $result = $db->loadObject();

        if ($result->extension_view === 'popup') {
            $popupSettingKeys = [
                'popup_type',
                'is_excluded_pages',
                'is_excluded_menus',
                'excluded_pages',
                'excluded_menus',
                'selected_pages',
                'selected_menus',
            ];

            $popupSettings = [
                'id' => $data['id'],
            ];

            foreach ($popupSettingKeys as $popupKey) {
                if (! empty($data[$popupKey])) {
                    $popupSettings[$popupKey] = $data[$popupKey];
                }
            }

            $params = ComponentHelper::getParams('com_sppagebuilder');

            $componentId = ComponentHelper::getComponent('com_sppagebuilder')->id;

            $visibility = $params->get('popup_visibility', null);
            $visibility = ! empty($visibility) ? $visibility : null;
            $popupId    = $data['id'];

            if (is_null($visibility)) {
                $visibility = [$popupId => $popupSettings];
            } else {
                $visibility->$popupId = $popupSettings;
            }

            $params->set('popup_visibility', $visibility);

            $table = Table::getInstance('extension');

            $table->load($componentId);
            $table->params = json_encode($params);

            if (! $table->store()) {
                echo "Something went wrong";
                die;
            }
        }

        try
        {
            if (! parent::save($data)) {
                throw new Exception('Error while saving the page');
            }

            if ($result->extension == 'com_content' && $result->extension_view == 'article') {
                $page = $this->getPageContent($data['id']);
                $this->addArticleFullText($page->view_id, $page->content);
            }
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Duplicate a page.
     *
     * @param int $id The ID of the page to duplicate.
     *
     * @return object
     * @since 4.0.0
     */
    public function duplicatePage(int $id)
    {
        try
        {
            $db    = Factory::getDbo();
            $query = $db->getQuery(true);
            $query->select("*");
            $query->from($db->quoteName('#__sppagebuilder'));
            $query->where($db->quoteName('id') . '=' . $db->quote($id));
            $db->setQuery($query);

            $page = $db->loadObject();

            if (! empty($page)) {
                $page->title = $this->generatePageNewTitle($page->title);
                $page->hits  = 0;
                $page->id    = '';
                $db->insertObject('#__sppagebuilder', $page, 'id');
                $this->checkin($id);

                return (object) [
                    'response' => [
                        'status'  => true,
                        'id'      => $page->id,
                        'message' => Text::_("COM_SPPAGEBUILDER_SUCCESS_MSG_FOR_PAGE_DUPLICATED"),
                    ],
                    'code'     => 201,
                ];
            }
        } catch (Exception $e) {
            return (object) ['status' => false, 'message' => $e->getMessage(), 'code' => 500];
        }
    }

    /**
     * Generate page title
     *
     * @param string $title current page title.
     *
     * @return string
     */
    public function generatePageNewTitle($title)
    {
        $table = $this->getTable();

        while ($table->load(['title' => $title])) {
            $title = StringHelper::increment($title);
        }

        return $title;
    }

    /**
     * Get Menu List
     *
     * @return    object    The response
     * @since    4.0.0
     */
    public function getMenus(): object
    {
        $response = new stdClass();

        try
        {
            $db    = Factory::getDbo();
            $query = $db->getQuery(true);

            $query->select('id, menutype, title')
                ->from($db->quoteName('#__menu_types'))
                ->where($db->quoteName('client_id') . ' = 0');

            $db->setQuery($query);

            $result = $db->loadObjectList();
            $data   = [];

            if (! empty($result)) {
                foreach ($result as $value) {
                    $data[] = (object) [
                        'value' => $value->menutype,
                        'label' => $value->title,
                    ];
                }
            }

            $response->data = $data;
            $response->code = 200;

            return $response;
        } catch (Exception $e) {
            $response->message = $e->getMessage();
            $response->code    = 500;

            return $response;
        }
    }

    /**
     * Get Menu List
     *
     * @param    string    $menuType     The menu type
     * @return    object    The response
     * @since    4.0.0
     */
    public function getParentItems(string $menuType, int $id = 0): object
    {
        $response = new stdClass();

        try
        {
            $db    = Factory::getDbo();
            $query = $db->getQuery(true);
            $query->select('DISTINCT(a.id) AS value, a.title AS text, a.level, a.lft')
                ->from($db->quoteName('#__menu', 'a'))
                ->where($db->quoteName('a.menutype') . ' = ' . $db->quote($menuType))
                ->where($db->quoteName('a.client_id') . ' = 0');

            if ($id > 0) {
                $query->join('LEFT', $db->quoteName('#__menu') . ' AS p ON p.id = ' . (int) $id)
                    ->where('NOT(a.lft >= p.lft AND a.rgt <= p.rgt)');
            }

            $query->where('a.published != -2')
                ->order('a.lft ASC');

            $db->setQuery($query);

            $result = $db->loadObjectList();
            $data   = [];

            if (! empty($result)) {
                foreach ($result as $value) {
                    $data[] = (object) [
                        'value' => $value->value,
                        'label' => $value->text,
                    ];
                }
            }

            $rootItem = (object) ['value' => 1, 'label' => Text::_('COM_SPPAGEBUILDER_MENU_ITEM_ROOT')];
            array_unshift($data, $rootItem);

            $response->data = $data;
            $response->code = 200;

            return $response;
        } catch (Exception $e) {
            $response->data = $e->getMessage();
            $response->code = 500;

            return $response;
        }
    }

    /**
     * Get menu item by page ID.
     *
     * @param int $pageId The page ID to search for.
     *
     * @return object|null The menu item object if found, null otherwise.
     * @since 4.0.0
     */
    public function getMenuByPageId($pageId = 0)
    {
        $db    = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__menu');
        $query->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_sppagebuilder&view=page&id=' . $pageId));
        $query->where($db->quoteName('client_id') . '= 0');
        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Apply bulk actions on pages.
     *
     * @param object $params The parameters containing the action type and IDs.
     *
     * @return bool True on success, false on failure.
     * @since 4.0.0
     */
    public function applyBulkActions(object $params)
    {
        switch ($params->type) {
            case 'published':
                return $this->changeStatus($params->ids, 1);
            case 'unpublished':
                return $this->changeStatus($params->ids, 0);
            case 'trash':
                return $this->changeStatus($params->ids, -2);
            case 'delete':
                return $this->deletePages($params->ids);
            case 'check-out':
                return $this->checkOutItems($params->ids);
            case 'rename':
                return $this->renamePage($params->ids, $params->value);
            case 'language':
                return $this->performBulkActions($params->ids, 'language', $params->value);
            case 'access':
                return $this->performBulkActions($params->ids, 'access', $params->value);
            case 'category':
                return $this->performBulkActions($params->ids, 'catid', $params->value);
        }
    }

    /**
     * Perform bulk actions on pages.
     *
     * @param string $ids Comma-separated list of page IDs.
     * @param string $type The type of action to perform (e.g., 'language', 'access', 'catid').
     * @param string $value The value to set for the specified type.
     *
     * @return bool True on success, false on failure.
     * @since 4.0.0
     */
    private function performBulkActions(string $ids, string $type, string $value)
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true);

        $ids = array_map('intval', explode(',', $ids));
        $query->update($db->quoteName('#__sppagebuilder'))
            ->set($db->quoteName($type) . ' = ' . $db->quote($value))
            ->whereIn($db->quoteName('id'), $ids);

        $db->setQuery($query);

        try
        {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Rename a page by ID.
     *
     * @param string $id The page ID to rename.
     * @param string $value The new title for the page.
     *
     * @return bool True on success, false on failure.
     * @since 4.0.0
     */
    private function renamePage(string $id, string $value)
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->update($db->quoteName('#__sppagebuilder'))
            ->set($db->quoteName('title') . ' = ' . $db->quote($value))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $db->setQuery($query);

        try
        {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Change the status of pages by IDs.
     *
     * @param string $ids Comma-separated list of page IDs to change status.
     * @param int $status The new status to set (1 for published, 0 for unpublished).
     *
     * @return bool True on success, false on failure.
     * @since 4.0.0
     */
    private function changeStatus(string $ids, int $status)
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true);

        $idArray = array_map('intval', explode(',', $ids));
        $query->update($db->quoteName('#__sppagebuilder'))
            ->set($db->quoteName('published') . ' = :status')
            ->whereIn($db->quoteName('id'), $idArray)
            ->bind(':status', $status, ParameterType::INTEGER);

        $db->setQuery($query);

        try
        {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete pages by IDs.
     *
     * @param string $ids Comma-separated list of page IDs to delete.
     *
     * @return bool True on success, false on failure.
     * @since 4.0.0
     */
    private function deletePages(string $ids)
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->delete($db->quoteName('#__sppagebuilder'))
            ->where($db->quoteName('id') . ' IN (' . $ids . ')');

        $db->setQuery($query);

        try
        {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check in multiple items by IDs.
     *
     * @param string $ids Comma-separated list of item IDs to check in.
     *
     * @return bool True on success, false on failure.
     * @since 4.0.0
     */
    private function checkOutItems(string $ids)
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__sppagebuilder'))
            ->set($db->quoteName('checked_out') . ' = 0')
            ->where($db->quoteName('id') . ' IN (' . $ids . ')');
        $db->setQuery($query);

        try
        {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check in a page by ID.
     *
     * @param int $id The page ID to check in.
     *
     * @return bool True on success, false on failure.
     * @since 4.0.0
     */
    public function checkInPage($id)
    {
        if (empty($id)) {
            return;
        }

        $user  = Factory::getUser();
        $db    = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__sppagebuilder'))
            ->set($db->quoteName('checked_out') . ' = ' . (int) $user->id)
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);
        try
        {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check out a page by ID.
     *
     * @param int $id The page ID to check out.
     *
     * @return bool True on success, false on failure.
     * @since 4.0.0
     */
    public function checkOutPage($id)
    {
        if (empty($id)) {
            return;
        }

        $db    = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__sppagebuilder'))
            ->set($db->quoteName('checked_out') . ' = 0')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);

        try
        {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a language is installed.
     *
     * @param string $language The language tag to check.
     *
     * @return object|false The language object if installed, false otherwise.
     * @since 4.0.0
     */
    public function checkLanguageIsInstalled($language = 'en-GB')
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(['id', 'state', 'version']));
        $query->from($db->quoteName('#__sppagebuilder_languages'));
        $query->where($db->quoteName('lang_tag') . ' = :language');
        $query->bind(':language', $language);
        $db->setQuery($query);

        $result = $db->loadObject();

        return ! empty($result) ? $result : false;
    }

    /**
     * Store language details.
     *
     * @param object $language The language object containing details.
     *
     * @return string The version of the stored or updated language.
     * @since 4.0.0
     */
    public function storeLanguage($language)
    {
        $db      = Factory::getDbo();
        $result  = $this->checkLanguageIsInstalled($language->lang_tag);
        $version = $language->version;

        if ($result) {
            $values = [
                'title'       => $language->title,
                'description' => $language->description,
                'lang_key'    => $language->lang_key,
                'version'     => $language->version,
            ];
            $version = $this->updateLanguage($values, $language->lang_key);
        } else {
            $values = [
                $language->title,
                $language->description,
                $language->lang_tag,
                $language->lang_key,
                $language->version,
                1,
            ];
            $this->insertLanguage($values);
        }

        return $version;
    }

    /**
     * Insert a new language.
     *
     * @param array $values The values to insert.
     *
     * @return int The inserted language ID.
     * @since 4.0.0
     */
    private function insertLanguage($values = [])
    {
        if (empty($values)) {
            return 0;
        }

        $db      = Factory::getDbo();
        $columns = ['title', 'description', 'lang_tag', 'lang_key', 'version', 'state'];

        $object = new \stdClass();
        foreach ($columns as $i => $column) {
            $object->$column = $values[$i] ?? '';
        }

        $db->insertObject('#__sppagebuilder_languages', $object);

        return $db->insertid();
    }

    /**
     * Update language details.
     *
     * @param array $values The values to update.
     * @param string $lang_tag The language tag.
     *
     * @return string The updated version.
     * @since 4.0.0
     */
    private function updateLanguage($values = [], $lang_tag = 'en-GB')
    {
        $db    = $this->getDbo();
        $query = $db->getQuery(true);

        $query->update($db->quoteName('#__sppagebuilder_languages'))
            ->set([
                $db->quoteName('title') . ' = :title',
                $db->quoteName('description') . ' = :description',
                $db->quoteName('lang_key') . ' = :langKey',
                $db->quoteName('version') . ' = :version',
            ])
            ->where($db->quoteName('lang_key') . ' = :langTag')
            ->bind(':title', $values['title'])
            ->bind(':description', $values['description'])
            ->bind(':langKey', $values['lang_key'])
            ->bind(':version', $values['version'])
            ->bind(':langTag', $lang_tag);

        $db->setQuery($query);
        $db->execute();

        return $values['version'];
    }

    /**
     * Get menu by ID.
     *
     * @param int $menuId The menu ID.
     *
     * @return object|null The menu object or null if not found.
     * @since 4.0.0
     */
    public function getMenuById($menuId = 0)
    {
        $db    = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select(['a.*']);
        $query->from('#__menu as a')
            ->where('a.id = :id')
            ->where('a.client_id = 0')
            ->where($db->quoteName('published') . ' != -2')
            ->bind(':id', $menuId, ParameterType::INTEGER);
        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Create a new page.
     *
     * @param array $data The data for the new page.
     *
     * @return int|array The ID of the created page or an error message.
     * @since 4.0.0
     */
    public function createPage(array $data)
    {
        try
        {
            if (! parent::save($data)) {
                throw new Exception('Failed creating the page.');
            }

            $id = $this->getState($this->getName() . '.id') ?? 0;

            return $id;
        } catch (Exception $error) {
            return ['message' => $error->getMessage()];
        }
    }

    /**
     * Toggle Integration
     *
     * @return     boolean
     * @since     4.0.0
     */
    public function toggleIntegration($group = '', $name = '')
    {
        $enabled = PluginHelper::isEnabled($group, $name);
        $status  = $enabled ? 0 : 1;

        $db     = Factory::getDbo();
        $query  = $db->getQuery(true);
        $fields = [$db->quoteName('enabled') . ' = ' . $status];

        $conditions = [
            $db->quoteName('type') . ' = ' . $db->quote('plugin'),
            $db->quoteName('element') . ' = ' . $db->quote($name),
            $db->quoteName('folder') . ' = ' . $db->quote($group),
        ];

        $query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
        $db->setQuery($query);
        $db->execute();

        return $status;
    }

    /**
     * Get the creator of a page.
     *
     * @param int $id The page ID.
     *
     * @return int The user ID of the page creator.
     * @since 5.5.0
     */
    public function getPageCreator($id)
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select('created_by')
            ->from($db->quoteName('#__sppagebuilder'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $db->setQuery($query);

        try
        {
            return $db->loadResult();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Add full text to an article.
     *
     * @param int $id The article ID.
     * @param string $data The full text data.
     *
     * @return void
     * @since 5.5.0
     */
    public function addArticleFullText($id, $data)
    {
        $article           = new stdClass();
        $article->id       = $id;
        $article->fulltext = SppagebuilderHelperSite::getPrettyText($data);

        Factory::getDbo()->updateObject('#__content', $article, 'id');
    }
}
