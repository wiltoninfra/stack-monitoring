<?php

namespace Promo\Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Illuminate\Validation\ValidationException;
use Promo\Documents\Embeded\BlackListedConsumerDetail;
use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Documents\Enums\TransactionTypeEnum;

/**
 * @ODM\Document(collection="blacklisted_consumers", repositoryClass="Promo\Repositories\BlackListedConsumerRepository")
 * @ODM\UniqueIndex(keys={"consumer_id"="asc"})
 * @ODM\Index(keys={"active"="asc"})
 * @ODM\Index(keys={"campaign_types"="asc"})
 * @ODM\Index(keys={"transaction_types"="asc"})
 */
class BlackListedConsumer extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="int") */
    protected $consumer_id;

    /** @ODM\Field(type="boolean") */
    protected $active;

    /** @ODM\Field(type="collection") */
    protected $campaign_types;

    /** @ODM\Field(type="collection") */
    protected $transaction_types;

    /** @ODM\EmbedMany(targetDocument="Promo\Documents\Embeded\BlackListedConsumerDetail")
     *
     */
    protected $details = array();

    public function __construct()
    {
        $this->active = true;
        $this->details = new ArrayCollection();
    }

    /**
     * Se bloqueio está ativa ou não
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Ativa bloqueio
     *
     * @return void
     */
    public function enable()
    {
        $this->active = true;
    }

    /**
     * Desativa bloqueio
     *
     * @return void
     */
    public function disable()
    {
        $this->active = false;
    }

    /**
     * @param $status
     */
    public function setActive(bool $status){

            $this->active = $status;
    }

    /**
     * @param int $id
     */
    public function setConsumerId(int $id)
    {
        $this->consumer_id =  $id;
    }

    /**
     * Obtém o consumer_id
     * @return integer
     */
    public function getConsumerId()
    {
        return (int) $this->consumer_id;
    }

    /**
     * Obtém o tipos de transacao
     *
     * @return array
     */
    public function getTransactionTypes()
    {
        return $this->transaction_types;
    }

    /**
     * Altera tipos de transacao
     *
     * @param array $types
     * @return self
     *
     */
    public function setTransactionTypes(array $types)
    {
        $newTypes = [];
        foreach ($types as $type) {
            if (!in_array($type, TransactionTypeEnum::getFields())) {
                throw new ValidationException('Tipo inesperado de campanha: ' . $type);
            }
            $newTypes[] = $type;
        }
        $this->transaction_types = $newTypes;
        return $this;
    }

    /**
     * Obtém tipos de campanha
     *
     * @return array
     */
    public function getCampaignTypes()
    {
        return $this->campaign_types;
    }

    /**
     * Altera tipos de campanha
     *
     * @param array $types
     * @return self
     *
     */
    public function setCampaignTypes(array $types)
    {
        $newTypes = [];
        foreach ($types as $type) {
            if (!in_array($type, CampaignTypeEnum::getFields())) {
                throw new ValidationException('Tipo inesperado de campanha: ' . $type);
            }
            $newTypes[] = $type;
        }
        $this->campaign_types = $newTypes;
        return $this;

    }


    /**
     * Altera detalhes do bloqueio
     *
     * @param array $details
     * @return self
     *
     */
    public function setDetails(array $details)
    {
        $this->details = $details;
    }


    /**
     * Obtem detalhes do bloqueio
     *
     * @param array $details
     * @return self
     *
     */
    public function getDetails()
    {
        return $this->details ?? [];
    }


    /**
     * adiciona um novo detalhe
     *
     * @param BlackListedConsumerDetail $detail
     *
     */
    public function addDetail(BlackListedConsumerDetail $detail)

    {
        $details_list = $this->details->toArray();
        array_unshift($details_list, $detail);
        $this->details = $details_list;
    }


    /**
     * adiciona um novo detalhe
     *
     * @param array $data
     * @return self
     *
     */
    public function fill(array $data)
    {
        $this->setConsumerId($data['consumer_id'] ?? null);
        $this->setCampaignTypes($data['campaign_types'] ?? []);
        $this->setActive($data['active'] ?? true);
        $this->setTransactionTypes($data['transaction_types'] ?? []);
        $detail = new BlackListedConsumerDetail();
        $detail->setCreatedBy($data['details']['created_by'] ?? '');
        $detail->setDescription($data['details']['description'] ?? '');
        $detail->setOrigin($data['details']['origin'] ?? 'herodash');
        $this->addDetail($detail);
        return $this;

    }

}