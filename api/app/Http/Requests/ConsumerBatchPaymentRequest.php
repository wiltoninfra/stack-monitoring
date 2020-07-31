<?php


namespace Promo\Http\Requests;

/**
 * ConsumerBatchPayment class
 *
 * @SWG\Definition(
 *      definition="ConsumerBatchPaymentCampaign",
 *      type="string",
 *      @SWG\Property(property="campaign_id"),
 * )
 * @SWG\Definition(
 *      definition="ConsumerBatchPaymentJustification",
 *      type="string",
 *      @SWG\Property(property="justification"),
 * )
 * @SWG\Definition(
 *      definition="ConsumerBatchPaymentFile",
 *      type="file",
 *
 *      @SWG\Property(property="file")
 * )
 */
class ConsumerBatchPaymentRequest
{
    /**
     * Regras de validação para envio de pagamentos em lote
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'justification' => 'required|string',
            'file'          => 'required|file',
            'extension'     => 'required|in:csv',
        ];
    }
}