<?php

namespace Promo\Documents\Embeded;

use Promo\Documents\Enums\PaymentMethodsEnum;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Promo\Documents\Enums\TransactionConditionEnum;

/**
 * @ODM\EmbeddedDocument
 */
class TransactionDetails
{
    /**
     * @ODM\Field(type="string")
     * @ODM\Index
     */
    protected $type;

    /** @ODM\Field(type="integer") */
    protected $max_transactions;

    /**
     * @ODM\Field(type="integer")
     * @deprecated Atributo desativado em prol de CampaignLimits
     */
    protected $max_transactions_per_consumer;

    /**
     * @ODM\Field(type="integer")
     * @deprecated Atributo desativado em prol de CampaignLimits
     */
    protected $max_transactions_per_consumer_per_day;

    /** @ODM\Field(type="string") */
    protected $required_message;

    /** @ODM\Field(type="float") */
    protected $min_transaction_value;

    /** @ODM\Field(type="boolean") */
    protected $first_payment;

    /** @ODM\Field(type="boolean") */
    protected $first_payment_to_seller;

    /** @ODM\Field(type="boolean") */
    protected $first_payee_received_payment_only;

    /** @ODM\Field(type="collection") */
    protected $required_credit_card_brands;

    /**
     * @ODM\Field(type="boolean")
     * @deprecated Atributo desativado em prol de payment_methods
     */
    protected $cc_only;

    /** @ODM\Field(type="collection") */
    protected $payment_methods;

    /** @ODM\Field(type="integer") */
    protected $min_installments;

    /** @ODM\Field(type="collection") */
    protected $conditions;

    public function __construct()
    {
        $this->min_transaction_value = 0;
    }

    /**
     * Altera o tipo da transação
     *
     * @param string $type
     * @return self
     */
    public function setType(?string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Obtém o tipo
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Obtém o máximo de transações de uma campanha
     *
     * @return null|int
     */
    public function getMaxTransactions()
    {
        return $this->max_transactions;
    }

    /**
     * Altera o máximo de transações de uma campanha
     *
     * @param $value
     * @return self
     */
    public function setMaxTransactions(?int $value): self
    {
        $this->max_transactions = $value;

        return $this;
    }

    /**
     * Obtém o máximo de transações por consumer
     * para esta campanha
     *
     * @return mixed
     */
    public function getMaxTransactionsPerConsumer()
    {
        return $this->max_transactions_per_consumer;
    }

    /**
     * Altera o máximo de transações de um consumer, para esta campanha
     * antes de desativá-la para o consumer
     *
     * @param $value
     * @return self
     */
    public function setMaxTransactionsPerConsumer(?int $value)
    {
        $this->max_transactions_per_consumer = $value;

        return $this;
    }

    /**
     * O valor mínimo que a transação precisa ter
     * para obter o cashback
     */
    public function getMinTransactionValue()
    {
        if ($this->min_transaction_value === null)
        {
            return 0;
        }

        return $this->min_transaction_value;
    }

    /**
     * Altera o valor mínimo que a transação precisa ter
     * para obter o cashback
     *
     * @param $value
     * @return self
     */
    public function setMinTransactionValue(?int $value)
    {
        $this->min_transaction_value = $value;

        return $this;
    }

    /**
     * Obtém o valor de required_message
     */
    public function getRequiredMessage()
    {
        return $this->required_message;
    }

    /**
     * Altera o valor de required_message
     *
     * @param $required_message
     * @return self
     */
    public function setRequiredMessage(?string $required_message)
    {
        $this->required_message = $required_message;

        return $this;
    }

    /**
     * Obtém o valor de credit_card
     *
     * @return boolean|null
     */
    public function isCreditCardOnly()
    {
        return $this->cc_only;
    }

    /**
     * Altera o valor de credit_card
     *
     * @param $cc_only
     * @return self
     */
    public function setCreditCardOnly(?bool $cc_only)
    {
        $this->cc_only = $cc_only;

        return $this;
    }

    /**
     * Obtém o máximo de transações de um usuário por dia
     *
     * @return mixed
     */
    public function getMaxTransactionsPerConsumerPerDay()
    {
        return $this->max_transactions_per_consumer_per_day;
    }

    /**
     * Altera o máximo de transações por dia
     *
     * @param mixed $max_transactions_per_consumer_per_day
     * @return self
     */
    public function setMaxTransactionsPerConsumerPerDay(?int $max_transactions_per_consumer_per_day)
    {
        $this->max_transactions_per_consumer_per_day = $max_transactions_per_consumer_per_day;

        return $this;
    }

    /**
     * Se a campanha é apenas para primeiro pagamento
     *
     * @return mixed
     */
    public function isFirstPayment()
    {
        return $this->first_payment;
    }

    /**
     * Altera o comportamento de primeiro pagamento
     *
     * @param mixed $first_payment
     * @return TransactionDetails
     */
    public function setFirstPayment(?bool $first_payment)
    {
        $this->first_payment = $first_payment;

        return $this;
    }

    /**
     * Se a campanha é apenas para primeiro pagamento ao seller
     *
     * @return mixed
     */
    public function isFirstPaymentToSeller()
    {
        return $this->first_payment_to_seller;
    }

    /**
     * Altera o comportamento de primeiro pagamento ao seller
     *
     * @param mixed $first_payment_to_seller
     * @return TransactionDetails
     */
    public function setFirstPaymentToSeller(?bool $first_payment_to_seller)
    {
        $this->first_payment_to_seller = $first_payment_to_seller;

        return $this;
    }

    /**
     * Se a campanha tem a restrição de apenas ser válida
     * quando o transação está sendo feita para alguém que nunca
     * recebeu um pagamento P2P
     *
     * @return boolean|null
     */
    public function isFirstPayeeReceivedPaymentOnly()
    {
        return $this->first_payee_received_payment_only;
    }

    /**
     * Altera valor de propriedade sobre ser o primeiro recebimento
     * do usuário recebedor
     *
     * @param mixed $first_payee_received_payment_only
     * @return TransactionDetails
     */
    public function setFirstPayeeReceivedPaymentOnly(?bool $first_payee_received_payment_only)
    {
        $this->first_payee_received_payment_only = $first_payee_received_payment_only;

        return $this;
    }

    /**
     * Configura as bandeiras de cartão obrigatórias a campanha
     *
     * @param array|null $required_credit_cards
     * @return $this
     */
    public function setRequiredCreditCardBrands(?array $required_credit_cards)
    {
        $this->required_credit_card_brands = $required_credit_cards;

        return $this;
    }

    /**
     * Obtém os cartões obrigatórios de uma campanha
     *
     * @return mixed
     */
    public function getRequiredCreditCardBrands()
    {
        return $this->required_credit_card_brands;
    }

    /**
     * Obtém os métodos de pagamento de uma campanha
     *
     * @return array|null
     */
    public function getPaymentMethods(): ?array
    {
        return $this->payment_methods;
    }

    /**
     * Substitui os métodos de pagamento
     *
     * @param mixed $payment_methods
     * @return TransactionDetails
     */
    public function setPaymentMethods(array $payment_methods)
    {
        $this->payment_methods = $payment_methods;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getMinInstallments(): ?int
    {
        if($this->min_installments == null) {
            $this->setMinInstallments(1);
        }

        return $this->min_installments;
    }

    /**
     * @param int $minInstallments
     * @return $this
     */
    public function setMinInstallments(int $minInstallments)
    {
        $this->min_installments = $minInstallments;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getConditions(): ?array
    {
        if ($this->conditions === null) {
            $this->setConditions([TransactionConditionEnum::IN_CASH, TransactionConditionEnum::INSTALLMENTS]);
        }

        return $this->conditions;
    }

    /**
     * @param array $conditions
     * @return $this
     */
    public function setConditions(array $conditions)
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * Método para migrar payment_methods
     */
    public function postLoad()
    {
        // Faz a migração para o novo formato de métodos de pagamentos
        // uma vez que cc_only foi substituído por payment_methods
        if ($this->cc_only === true)
        {
            // Aceita somente cartão
            $this->setPaymentMethods(PaymentMethodsEnum::CREDIT_CARD_ONLY);
        }
        else if ($this->cc_only === false)
        {
            // Aceita todos os métodos
            $this->setPaymentMethods(PaymentMethodsEnum::ALL);
        }

        $this->cc_only = null;
    }
}
