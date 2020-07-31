<?php

namespace Promo\Http\Requests;

/**
 *
 * @SWG\Definition(
 *     definition="ApplyPermissions",
 *     type="object",
 *     @SWG\Property(property="update", type="boolean", @SWG\Items(type="boolean", example="false")),
 *     @SWG\Property(property="delete", type="boolean", @SWG\Items(type="boolean", example="false")),
 * )
 */
class ApplyPermissionsRequest
{
    public static function rules()
    {
        return [
            'update' => 'boolean|required',
            'delete' => 'boolean|required'
        ];
    }
}