<?php

namespace Promo\Http\Requests\BlackListedConsumer;

use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Http\Requests\FormRequest;
use Promo\Documents\Enums\TransactionTypeEnum;

/**
 * BlackListedConsumerRequest class
 *

 *
 * @SWG\Definition(
 *      definition="BlackListedConsumerRequest",
 *      type="object",
 *      @SWG\Property(property="transaction_types", type="array", @SWG\Items(type="string"),example="['p2p','pav']"),
 *      @SWG\Property(property="campaign_types", type="array", @SWG\Items(type="string"),example="['cashback','cashfront]"),
 *      @SWG\Property(property="details", ref="#/definitions/BlackListedConsumerDetailRequest"),
 * )
 *
 * @SWG\Definition(
 *      definition="BlackListedConsumerDetailRequest",
 *      type="object",
 *      @SWG\Property(property="created_by", type="string", example="user@picpay.com"),
 *      @SWG\Property(property="origin", type="string", example="herodash"),
 *      @SWG\Property(property="description", type="string", example="Cadastro xpto"),
 * )
 */
class BlackListedConsumerRequest extends FormRequest
{

    /**
     * atributos permitidos no
     *
     * @return array
     */
   public function attributes()
        {
            return [
                'transaction_types',
                'campaign_types',
                'consumer_id',
                'details.created_by',
                'details.origin',
                'details.description'
            ];
        }
    

    /**
     * Regras de validação para cadastro
     *
     * @return array
     */
    public function rules()
    {

        return [
            'transaction_types'   => 'array',
            'transaction_types.*' => 'string|in:' . TransactionTypeEnum::getFieldsListToCsv(),
            'campaign_types'      => 'array',
            'campaign_types.*'    => 'string|in:' . CampaignTypeEnum::getFieldsListToCsv(),
            'consumer_id'         => 'integer|has_unique_field:Promo\Documents\BlackListedConsumer,consumer_id'
        ];
    }


}