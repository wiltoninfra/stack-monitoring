<?php

namespace Promo\Http\Requests\BlackListedConsumer;

use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Http\Requests\FormRequest;
use Promo\Documents\Enums\TransactionTypeEnum;

/**
 * BlackListedConsumerUpdateRequest class
 *
 *
 * @SWG\Definition(
 *      definition="BlackListedConsumerUpdateRequest",
 *      type="object",
 *      @SWG\Property(property="transaction_types", type="array", @SWG\Items(type="string"),example="['p2p','pav']"),
 *      @SWG\Property(property="campaign_types", type="array",@SWG\Items(type="string"),example="['cashback','cashfront]"),
 *      @SWG\Property(property="details", ref="#/definitions/BlackListedConsumerDetailUpdateRequest"),
 * )
 *
 * @SWG\Definition(
 *      definition="BlackListedConsumerDetailUpdateRequest",
 *      type="object",
 *      @SWG\Property(property="created_by", type="string", example="user@picpay.com"),
 *      @SWG\Property(property="origin", type="string", example="herodash"),
 *      @SWG\Property(property="description", type="string", example="Cadastro xpto"),
 * )
 */
class BlackListedConsumerUpdateRequest extends FormRequest
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
                'active',
                'details.created_by',
                'details.origin',
                'details.description'
            ];
        }
    

    /**
     * Regras de validação para atualizacao
     *
     * @return array
     */
    public function rules()
    {
        $campaignRule    = 'required_without:transaction_types';
        $transactionRule = 'required_without:campaign_types';
        if ($this->request->has('active')) {
            $campaignRule    = '';
            $transactionRule = '';
        }
        return [
            'active' => 'boolean',
            'transaction_types' => $transactionRule . '|array',
            'transaction_types.*' => 'string|in:' . TransactionTypeEnum::getFieldsListToCsv(),
            'campaign_types' => $campaignRule . '|array',
            'campaign_types.*' => 'string|in:' . CampaignTypeEnum::getFieldsListToCsv(),
            'details.created_by' => 'email',
        ];
    }



}