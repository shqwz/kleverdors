<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */


namespace JoomShaper\SPPageBuilder\DynamicContent;

use Exception;
use JoomShaper\SPPageBuilder\DynamicContent\Concerns\Validator;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Request;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Response;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Str;

defined('_JEXEC') or die;

class Controller
{
    use Validator;

    /**
     * The model instance.
     * 
     * @var Model|null
     * @since 5.5.0
     */
    protected $model = null;

    /**
     * The constructor.
     * 
     * @param Model|null $model
     * @since 5.5.0
     */
    public function __construct(?Model $model = null)
    {
        $this->model = $model;
    }

    /**
     * Get the model instance.
     * 
     * @return Model|null
     * @since 5.5.0
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Reorder collections.
     *
     * @param Input $input
     * @return void
     * @since 5.5.0
     */
    public function reorder(Request $request)
    {
        $model = $this->getModel();

        if (!$model) {
            return response()->json(['message' => 'Model not found for reordering.'], Response::HTTP_BAD_REQUEST);
        }

        $pks = $request->getRaw('pks');

        $data = [
            'pks' => $pks,
        ];

        $this->validate($data, [
            'pks' => 'required|string',
        ]);

        if ($this->hasErrors()) {
            return response()->json($this->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $pks = Str::toArray($pks);

        try
        {
            $orderingKey = $model->getOrderingKey();
            $primaryKey = $model->getPrimaryKey();

            $model->whereIn($primaryKey, $pks)
                ->update(function ($queryBuilder) use ($pks, $orderingKey, $primaryKey) {
                    $db = $queryBuilder->getDatabase();
                    $orderingQueryString = $queryBuilder->quoteNameWithPrefix($orderingKey) . ' = CASE ' . $queryBuilder->quoteNameWithPrefix($primaryKey);

                    foreach ($pks as $index => $pk) {
                        $orderingValue = $index + 1;
                        $orderingQueryString .= ' WHEN ' . $db->quote($pk) . ' THEN ' . $db->quote($orderingValue) . ' ';
                    }

                    $orderingQueryString .= ' END';
                    return $orderingQueryString;
                });

            return response()->json(true);
        }
        catch (Exception $error)
        {
            return response()->json(['message' => $error->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
