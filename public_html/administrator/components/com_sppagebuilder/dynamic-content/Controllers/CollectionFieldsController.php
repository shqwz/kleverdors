<?php

namespace JoomShaper\SPPageBuilder\DynamicContent\Controllers;

use JoomShaper\SPPageBuilder\DynamicContent\Concerns\Validator;
use JoomShaper\SPPageBuilder\DynamicContent\Controller;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Request;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;

class CollectionFieldsController extends Controller
{
    use Validator;

    public function __construct()
    {
        $model = new CollectionField();
        parent::__construct($model);
    }

    /**
     * Reorder the fields
     * 
     * @param Request $request The request object.
     * 
     * @return JsonResponse
     * @since 5.5.0
     */
    public function reorder(Request $request)
    {
        return parent::reorder($request);
    }
}
