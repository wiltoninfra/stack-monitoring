<?php

namespace Promo\Http\Requests;

/**
 * TagRequest class
 *
 * @SWG\Definition(
 *      definition="Tag",
 *      type="object",
 *      @SWG\Property(property="name", type="string", example="Comercial"),
 *      @SWG\Property(property="abbreviation", type="string", example="COM"),
 *      @SWG\Property(property="color", type="string", example="#FFF"),
 * )
 *
 */
class TagRequest
{
    /**
     * Regras de validação criação de tags
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'name'           => 'required|string',
            'abbreviation'   => 'required|string|size:3',
            'color'          => 'required|string',
        ];
    }
}
