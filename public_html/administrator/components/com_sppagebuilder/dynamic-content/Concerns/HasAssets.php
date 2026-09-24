<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/

namespace JoomShaper\SPPageBuilder\DynamicContent\Concerns;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Asset;
use Joomla\Database\DatabaseInterface;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Asset as AssetModel;
use JoomShaper\SPPageBuilder\DynamicContent\QueryBuilder;
use Throwable;

trait HasAssets
{
    /**
     * Get the context.
     *
     * @return string
     * @since 5.5.0
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set the context.
     * The context is required to creating the asset_id into the table.
     * The system will check if the context is set or not. If the context is not set, the system will not create the asset_id.
     * Otherwise, it skips the asset_id creation.
     *
     * @param string $context
     * @since 5.5.0
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * Check if the model has asset.
     *
     * @return bool
     * @since 5.5.0
     */
    public function hasAsset()
    {
        return !is_null($this->context);
    }

    /**
     * Manage the asset.
     *
     * @return boolean
     * @since 5.5.0
     */
    public function manageAsset()
    {
        $parent = $this->getAssetParent();
        $parentId = is_null($parent) ? 0 : $parent->id;
        $primaryKey = $this->getPrimaryKey();
        $name = $this->getAssetName();
        $title = $this->getAssetTitle();

        $record = [
            'parent_id' => null,
            'name' => $name,
            'title' => $title,
            'rules' => '{}',
            'lft' => null,
            'rgt' => null,
            'level' => null,
        ];

        try {
            if ($parentId >= 0) {
                if ($parentId === 0) {
                    $node = AssetModel::where('parent_id', 0)
                        ->orderBy('lft', 'DESC')
                        ->first([$primaryKey, 'parent_id', 'lft', 'rgt', 'level']);
                } else {
                    $node = $this->getNode($parentId);
                }

                if (is_null($node)) {
                    return false;
                }

                $treeData = $this->getTreeRepositionData($node, 2, 'last-child');

                if (empty($treeData)) {
                    return false;
                }

                [$column, $operator, $value] = $treeData->left_where;
                AssetModel::where($column, $operator, $value)->update(['lft' => QueryBuilder::raw('lft + 2')]);

                [$column, $operator, $value] = $treeData->right_where;
                AssetModel::where($column, $operator, $value)->update(['rgt' => QueryBuilder::raw('rgt + 2')]);

                $record['parent_id'] = $treeData->new_parent_id;
                $record['lft'] = $treeData->new_lft;
                $record['rgt'] = $treeData->new_rgt;
                $record['level'] = $treeData->new_level;
            }

            $assetId = AssetModel::create($record);
            $this->newQuery()->where($primaryKey, $this->$primaryKey)->update(['asset_id' => $assetId]);
        } catch (Throwable $error) {
            return false;
        }

        return true;
    }

    /**
     * Delete the asset.
     *
     * @return void
     * @since 5.5.0
     */
    public function deleteAsset()
    {
        $asset = $this->getAssetInstance();
        $assetName = $this->getAssetName();
        $asset->loadByName($assetName);
        $asset->delete();
    }

    /**
     * Get the Joomla Asset Table instance.
     *
     * @return Asset
     * @since 5.5.0
     */
    protected function getAssetInstance()
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $dispatcher = $app->getDispatcher();

        return new Asset($db, $dispatcher);
    }

    /**
     * Get the asset name.
     *
     * @return string
     * @since 5.5.0
     */
    protected function getAssetName()
    {
        $key = $this->getPrimaryKey();

        return $this->context . '.' . $this->$key;
    }

    /**
     * Get the asset title.
     *
     * @return string
     * @since 5.5.0
     */
    protected function getAssetTitle()
    {
        return $this->getAssetName();
    }

    /**
     * Get the asset parent.
     *
     * @return Asset|null
     * @since 5.5.0
     */
    protected function getAssetParent()
    {
        $parts = explode('.', $this->getAssetName());
        $component = $parts[0];
        $parent = AssetModel::where('name', $component)->first();

        return $parent->isEmpty() ? null : $parent;
    }

    /**
     * Get the node.
     *
     * @param int $id
     * @param string|null $key
     * @return object
     * @since 5.5.0
     */
    protected function getNode($id, $key = null)
    {
        $whereKey = null;
        $primaryKey = $this->getPrimaryKey();

        switch ($key) {
            case 'parent':
                $whereKey = 'parent_id';
                break;
            case 'left':
                $whereKey = 'lft';
                break;
            case 'right':
                $whereKey = 'rgt';
                break;
            default:
                $whereKey = $primaryKey;
                break;
        }

        $node = AssetModel::where($whereKey, $id)->first([$primaryKey, 'parent_id', 'lft', 'rgt', 'level']);
        $node->total_children = ($node->rgt - $node->lft - 1) / 2;
        $node->width = $node->rgt - $node->lft + 1;

        return $node;
    }

    /**
     * Get the tree reposition data.
     *
     * @param object $node
     * @param int $width
     * @param string $position
     * @return object
     * @since 5.5.0
     */
    protected function getTreeRepositionData($node, $width, $position = 'before')
    {
        $data = [];
        $key = $this->getPrimaryKey();

        switch ($position) {
            case 'first-child':
                $data['left_where'] = ['lft', '>', $node->lft];
                $data['right_where'] = ['rgt', '>=', $node->lft];
                $data['new_lft'] = $node->lft + 1;
                $data['new_rgt'] = $node->lft + $width;
                $data['new_parent_id'] = $node->$key;
                $data['new_level'] = $node->level + 1;
                break;
            case 'last-child':
                $data['left_where']  = ['lft', '>', $node->rgt];
                $data['right_where'] = ['rgt', '>=', $node->rgt];
                $data['new_lft'] = $node->rgt;
                $data['new_rgt'] = $node->rgt + $width - 1;
                $data['new_parent_id'] = $node->$key;
                $data['new_level'] = $node->level + 1;
                break;

            case 'before':
                $data['left_where']  = ['lft', '>=', $node->lft];
                $data['right_where'] = ['rgt', '>=', $node->lft];
                $data['new_lft'] = $node->lft;
                $data['new_rgt'] = $node->lft + $width - 1;
                $data['new_parent_id'] = $node->$key;
                $data['new_level'] = $node->level;
                break;

            default:
            case 'after':
                $data['left_where']  = ['lft', '>', $node->rgt];
                $data['right_where'] = ['rgt', '>', $node->rgt];
                $data['new_lft'] = $node->rgt + 1;
                $data['new_rgt'] = $node->rgt + $width;
                $data['new_parent_id'] = $node->$key;
                $data['new_level'] = $node->level;
                break;
        }

        return (object) $data;
    }
}
