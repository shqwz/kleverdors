<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use JoomShaper\SPPageBuilder\DynamicContent\Controllers\CollectionImportExportController;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Layout Import Trait
 */
trait CommonTrait
{

    /**
     * Import dynamic content data.
     *
     * @param string $data
     * @return array
     */
    private function importDynamicContentData($data)
    {
        $dynamicContentExportImportController = new CollectionImportExportController();
        return $dynamicContentExportImportController->importDynamicContent(json_decode($data, true));
    }

    /**
     * Update dynamic ids in the content.
     *
     * @param string $content
     * @param array $updatedFieldIds
     * @param array $updatedCollectionIds
     * @return string
     */
    private function updateDynamicIds($content, $updatedFieldIds = [], $updatedCollectionIds = [])
    {
        if (empty($updatedFieldIds) || empty($updatedCollectionIds)) {
            return $content;
        }

        $data = $content;

        $this->recursivelyUpdateIds($data, $updatedFieldIds, $updatedCollectionIds);

        return $data;
    }

    /**
     * Recursively update ids in the data.
     *
     * @param mixed $data
     * @param array $updatedFieldIds
     * @param array $updatedCollectionIds
     */
    private function recursivelyUpdateIds(&$data, $updatedFieldIds, $updatedCollectionIds)
    {
        if (!is_array($data) && !is_object($data)) {
            return;
        }

        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }

        foreach ($data as $key => &$value) {
            if($key === 'source') {
                if(isset($value) && isset($updatedCollectionIds[$value])) {
                    $value = $updatedCollectionIds[$value];
                }
            } elseif($key === 'field_name') {
                if(isset($value) && isset($updatedFieldIds[$value])) {
                    $value = $updatedFieldIds[$value];
                }
            } elseif (is_array($value)) {
                if ($key === 'attribute') {
                    if(isset($value['id']) && isset($updatedFieldIds[$value['id']])) {
                        $value['id'] = $updatedFieldIds[$value['id']];
                    }

                    if(isset($value['path'])) {
                        $path = $value['path'];
                        $pathExploded = explode('.', $path);
                        $updatedIds = [];

                        foreach ($pathExploded as $attributeId) {
                            if(isset($updatedFieldIds[$attributeId])) {
                                $updatedIds[] = $updatedFieldIds[$attributeId];
                            } else {
                                $updatedIds[] = $attributeId;
                            }
                        }

                        $value['path'] = implode('.', $updatedIds);
                    }
                }

                $this->recursivelyUpdateIds($value, $updatedFieldIds, $updatedCollectionIds);
            } elseif (is_object($value)) {
                $arrayValue = json_decode(json_encode($value), true);

                if ($key === 'attribute') {
                    if(isset($arrayValue['id']) && isset($updatedFieldIds[$arrayValue['id']])) {
                        $arrayValue['id'] = $updatedFieldIds[$arrayValue['id']];
                    }  
                    
                    if(isset($arrayValue['path'])) {
                        $path = $arrayValue['path'];
                        $pathExploded = explode('.', $path);
                        $updatedIds = [];

                        foreach ($pathExploded as $attributeId) {
                            if(isset($updatedFieldIds[$attributeId])) {
                                $updatedIds[] = $updatedFieldIds[$attributeId];
                            } else {
                                $updatedIds[] = $attributeId;
                            }
                        }

                        $value['path'] = implode('.', $updatedIds);
                    }
                } 
                
                $this->recursivelyUpdateIds($arrayValue, $updatedFieldIds, $updatedCollectionIds);
                
                $value = json_decode(json_encode($arrayValue));
            }
        }

        if (is_object($data)) {
            $data = json_decode(json_encode($data));
        }
    }
}
