<?php

namespace Promo\Http\Requests\BlackListedConsumer;

use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Http\Requests\FormRequest;
use Promo\Documents\Enums\TransactionTypeEnum;

/**
 * CashbackRequest class
 *
 *
 *
 * @SWG\Definition(
 *      definition="BlackListedConsumerCreateRequest",
 *      type="object",
 *      @SWG\Property(property="transaction_types", type="array", @SWG\Items(type="string", example="p2p")),
 *      @SWG\Property(property="campaign_types", type="array", @SWG\Items(type="string",example="cashback")),
 *      @SWG\Property(property="details", ref="#/definitions/BlackListedConsumerCreateDetailRequest"),
 * )
 *
 * @SWG\Definition(
 *      definition="BlackListedConsumerCreateDetailRequest",
 *      type="object",
 *      @SWG\Property(property="created_by", type="string", example="user@picpay.com"),
 *      @SWG\Property(property="origin", type="string", example="herodash"),
 *      @SWG\Property(property="description", type="string", example="Cadastro xpto"),
 * )
 */
class BlackListedConsumerCreateRequest extends FormRequest
{

    /**
     *     preparacao para validacao
     */
    protected function prepareForValidation() {

        // get the input
        $input =$this->all();

        $input['consumer_id'] = $this->route()[2]['consumer_id'];

        // replace old input with new input
        $this->replace($input);
    }


    /**
     * atributos permitidos no service
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
            'transaction_types'   => 'required_without:campaign_types|array',
            'transaction_types.*' => 'string|in:' . TransactionTypeEnum::getFieldsListToCsv(),
            'campaign_types'      => 'required_without:transaction_types|array',
            'campaign_types.*'    => 'string|in:' . CampaignTypeEnum::getFieldsListToCsv(),
            'details.origin'      => 'required',
            'details.created_by'  => 'email',
            'consumer_id'         => 'required|integer|unique_field:Promo\Documents\BlackListedConsumer,consumer_id'
        ];
    }


}