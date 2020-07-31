<?php

namespace Promo\Http\Requests;

/**
 *
 * @SWG\Definition(
 *     definition="TagAssociation",
 *     type="object",
 *     @SWG\Property(property="tags", type="array", @SWG\Items(type="string", example="5c1a8fad6f569302a152cf92")),
 * )
 */
class TagAssociationRequest
{
    public static function rules()
    {
        return [
            'tags'    => 'nullable|array',
            'tags.*'  => 'string'
        ];
    }
}