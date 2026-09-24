<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/
//no direct accees
defined ('_JEXEC') or die ('Restricted access');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Collection;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php')) {
    require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php';
}

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php')) {
	require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php';
}

class JFormFieldDynamicCollection extends ListField
{
	protected $type = 'DynamicCollection';

    public function getInput()
    {
        $input = parent::getInput();
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $document = $app->getDocument();
        $document->addScriptDeclaration("
            document.addEventListener('DOMContentLoaded', function() {
                const collectionFieldWrapper = document.querySelector('.control-group:has(#jform_request_collection_id)');

                function toggleCollectionField() {
                    const extensionView = document.querySelector('#jform_request_extension_view').value;
                    const collectionField = collectionFieldWrapper.querySelector('#jform_request_collection_id');
                    if (extensionView !== 'dynamic_content:index') {
                        collectionFieldWrapper.style.display = 'none';
                        collectionField.value = '';
                        collectionField.removeAttribute('required');
                        collectionField.classList.remove('required');
                        collectionField.setAttribute('disabled', 'disabled');
                    } else {
                        collectionFieldWrapper.style.display = 'flex';
                        collectionField.removeAttribute('disabled');
                        collectionField.classList.add('required');
                        collectionField.setAttribute('required', true);
                    }
                }

                toggleCollectionField();

                collectionFieldWrapper.addEventListener('change', function() {
                    toggleCollectionField();
                });

            });
        ");

        return $input;
    }

    public function getOptions()
    {
        return $this->getCollections();
    }


    private function getCollections()
    {
        $collections = Collection::where('published', 1)->get(['id', 'title']);
        if (empty($collections)) {
            return [];
        }

        $options = array_map(function ($collection) {
            return [
                'value' => $collection->id,
                'text' => $collection->title
            ];
        }, $collections);

        return array_merge([
            [
                'value' => '',
                'text' => 'Select a collection'
            ]
        ], $options);
    }
}
