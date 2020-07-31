<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionDetailsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'type'                                  => $this->when((bool) $this->getType(), $this->getType()),
            'payment_methods'                       => $this->when((bool) $this->getPaymentMethods(), $this->getPaymentMethods()),
            'max_transactions'                      => $this->when((bool) $this->getMaxTransactions(), $this->getMaxTransactions()),
            'max_transactions_per_consumer'         => $this->when((bool) $this->getMaxTransactionsPerConsumer(), $this->getMaxTransactionsPerConsumer()),
            'max_transactions_per_consumer_per_day' => $this->when((bool) $this->getMaxTransactionsPerConsumerPerDay(), $this->getMaxTransactionsPerConsumerPerDay()),
            'required_message'                      => $this->when((bool) $this->getRequiredMessage(), $this->getRequiredMessage()),
            'min_transaction_value'                 => $this->when((bool) $this->getMinTransactionValue(), $this->getMinTransactionValue()),
            'first_payment'                         => $this->when((bool) $this->isFirstPayment(), $this->isFirstPayment()),
            'first_payment_to_seller'               => $this->when((bool) $this->isFirstPaymentToSeller(), $this->isFirstPaymentToSeller()),
            'first_payee_received_payment_only'     => $this->when((bool) $this->isFirstPayeeReceivedPaymentOnly(), $this->isFirstPayeeReceivedPaymentOnly()),
            'credit_card_brands'                    => $this->when($this->getRequiredCreditCardBrands() !== null, $this->getRequiredCreditCardBrands()),
            'min_installments'                      => $this->when((bool) $this->getMinInstallments() !== null, $this->getMinInstallments()),
            'conditions'                            => $this->when((bool) $this->getConditions() !== null, $this->getConditions()),
        ];
    }
}
