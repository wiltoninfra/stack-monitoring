<?php

namespace Promo\Http\Requests;

use Promo\Documents\Enums\CampaignTypeEnum;

/**
* class CampaignRequest
*
* @SWG\Definition(
*     definition="Campaign",
*     type="object",
*     @SWG\Property(property="name", type="string", example="Campanha"),
*     @SWG\Property(property="description", type="string", example="Uma campanha muito legal."),
*     @SWG\Property(property="type", type="string", enum={"cashback", "instantcash", "cashfront"}),
*     @SWG\Property(property="global", type="boolean", example=true),
 *    @SWG\Property(property="consumers", type="array", @SWG\Items(type="integer", example=4)),
*     @SWG\Property(property="sellers", type="array", @SWG\Items(type="integer", example=3)),
*     @SWG\Property(property="except_sellers", type="array", @SWG\Items(type="integer", example=8)),
*     @SWG\Property(property="sellers_types", type="array", @SWG\Items(type="string", example="membership")),
*     @SWG\Property(property="webhook_url", type="string", example="http://webhookpromo.aws.picpay.endereco-grande.com"),
*     @SWG\Property(property="webview_url", type="string", example="http://cdn.aws.picpay.endereco-grande.com/termos.html"),
*     @SWG\Property(property="duration", ref="#/definitions/DurationDetails"),
*     @SWG\Property(property="cashback", ref="#/definitions/CashbackDetails"),
*     @SWG\Property(property="cashfront", ref="#/definitions/CashfrontDetails"),
*     @SWG\Property(property="instantcash", ref="#/definitions/InstantcashDetails"),
*     @SWG\Property(property="transaction", ref="#/definitions/TransactionDetails"),
*     @SWG\Property(property="deposit", ref="#/definitions/DepositDetails"),
*     @SWG\Property(property="tags", type="array", @SWG\Items(type="string", example="5cb09ff75d8a99961e1a36c4")),
*     @SWG\Property(property="limits", ref="#/definitions/CampaignLimits"),
*     @SWG\Property(property="external_merchant", ref="#/definitions/ExternalMerchant"),
* ),
*
* @SWG\Definition(
*     definition="DurationDetails",
*     type="object",
*     @SWG\Property(property="fixed", type="boolean", example=true, description="Se a campanha tem datas fixas ou prazo dinâmico"),
*     @SWG\Property(property="start_date", type="string", example="2018-09-28T19:05:47.904Z"),
*     @SWG\Property(property="end_date", type="string", example="2027-09-30T19:05:47.904Z"),

*     @SWG\Property(property="hours", type="integer", example=1, description="Duração da campanha em horas, após o recebimento da notificação (campanha não fixa)"),
 *    @SWG\Property(property="days", type="integer", example=3, description="Duração da campanha em dias, após o recebimento da notificação (campanha não fixa)"),
 *    @SWG\Property(property="weeks", type="integer", example=2, description="Duração da campanha em semanas , após o recebimento da notificação (campanha não fixa)"),
 *    @SWG\Property(property="months", type="integer", example=6, description="Duração da campanha em months , após o recebimento da notificação (campanha não fixa)"),
* )
*
* @SWG\Definition(
*     definition="CashbackDetails",
*     type="object",
*     @SWG\Property(property="percentage", type="float", example=2.2),
*     @SWG\Property(property="max_value", type="float", example=5.5),
*     @SWG\Property(property="paid_by", type="string", enum={"picpay", "seller"}),
* )
*
*
* @SWG\Definition(
*     definition="CashfrontDetails",
*     type="object",
*     @SWG\Property(property="percentage", type="float", example=2.2),
*     @SWG\Property(property="max_value", type="float", example=5.5),
*     @SWG\Property(property="recharge_method", type="string", example="conta-corrente"),
* )
*
* @SWG\Definition(
*     definition="InstantcashDetails",
*     type="object",
*     @SWG\Property(property="value", type="float", example=10.0),
* )
*
* @SWG\Definition(
*     definition="TransactionDetails",
*     type="object",
*     @SWG\Property(property="type", type="string", enum={"p2p", "pav", "mixed"}),
*     @SWG\Property(property="min_transaction_value", type="float", example=10),
*     @SWG\Property(property="max_transactions", type="integer", example=40000),
*     @SWG\Property(property="max_transactions_per_consumer", type="integer", example=3),
*     @SWG\Property(property="max_transactions_per_consumer_per_day", type="integer", example=1),
*     @SWG\Property(property="required_message", type="string", example="#VaiBrasil"),
*    @SWG\Property(property="first_payment", type="boolean", example=false),
*     @SWG\Property(property="first_payment_to_seller", type="boolean", example=false),
*     @SWG\Property(property="first_payee_received_payment_only", type="boolean", example=false),
*     @SWG\Property(property="credit_card_brands", type="array", @SWG\Items(type="string", example="picpay")),
*     @SWG\Property(property="payment_methods", type="array", @SWG\Items(type="string", example="credit-card")),
*     @SWG\Property(property="min_installments", type="integer", example=2),
*     @SWG\Property(property="conditions", type="array", @SWG\Items(type="string", enum={"in_cash", "installments"})),
* )
*
* @SWG\Definition(
*     definition="DepositDetails",
*     type="object",
*     @SWG\Property(property="min_deposit_value", type="float", example=10),
*     @SWG\Property(property="max_deposits", type="integer", example=40000),
*     @SWG\Property(property="max_deposits_per_consumer", type="integer", example=3),
*     @SWG\Property(property="max_deposits_per_consumer_per_day", type="integer", example=1),
*     @SWG\Property(property="first_deposit_only", type="boolean", example=true),
* )
*
* @SWG\Definition(
*     definition="CampaignLimits",
*     type="object",
*     @SWG\Property(property="uses_per_consumer", ref="#/definitions/UsesPerConsumer"),
*     @SWG\Property(property="uses_per_consumer_per_period", ref="#/definitions/UsesPerConsumerPerPeriod"),
* )
*
* @SWG\Definition(
*    definition="UsesPerConsumer",
*     type="object",
*     @SWG\Property(property="type", type="string", enum={"count", "sum"}),
*     @SWG\Property(property="uses", type="float", example=1),
* )
*
* @SWG\Definition(
*    definition="UsesPerConsumerPerPeriod",
*     type="object",
*     @SWG\Property(property="type", type="string", enum={"count", "sum"}),
*     @SWG\Property(property="period", type="string", enum={"day", "week", "month"}),
*     @SWG\Property(property="uses", type="float", example=1),
* )
*
*
* @SWG\Definition(
*     definition="ExternalMerchant",
*     type="object",
*     @SWG\Property(property="type", type="string", example="cielo"),
*     @SWG\Property(property="ids", type="array", @SWG\Items(type="string", example="0020045759939200"))
* )
*
*/
class CampaignRequest
{
    /**
     * Regras de validação para criação e atualização de campanha
     *
     * @param string $type
     * @return array
     */
    public static function rules(?string $type)
    {
        // Monta o objeto de acordo com o tipo da campanha
        switch ($type)
        {
            case CampaignTypeEnum::CASHBACK:
                return CashbackCampaignRequest::rules();
                break;

            case CampaignTypeEnum::CASHFRONT:
                return CashfrontCampaignRequest::rules();
                break;
            default:
                return ['type' => 'required|in:' . CampaignTypeEnum::getFieldsListToCsv()];
        }
    }
}
