<?php

namespace Promo\Http\Requests;

/**
 *
 * @SWG\Definition(
 *     definition="PublishBanners",
 *     type="object",
 *     @SWG\Property(property="ids", type="array", @SWG\Items(type="string", example="5c1a8fad6f569302a152cf92")),
 * )
 */
class PublishBannersRequest
{
    public static function rules()
    {
        return [
            'ids'    => 'required|array',
            'ids.*'  => 'string'
        ];
    }
}