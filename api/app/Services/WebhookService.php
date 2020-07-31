<?php

namespace Promo\Services;

use Promo\Documents\Coupon;
use Promo\Documents\Embeded\NotificationVariantPayload;
use Promo\Documents\Webhook;
use Promo\Documents\Campaign;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Promo\Exceptions\InvalidCampaignException;
use Promo\Exceptions\InvalidCouponException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WebhookService
{
    /**
     * Repositório de Webhook
     *
     * @var \Doctrine\ODM\MongoDB\DocumentRepository
     */
    private $webhook_repository;

    /**
     * Repositório de Campanhas
     *
     * @var \Doctrine\ODM\MongoDB\DocumentRepository
     */
    private $campaign_repository;

    /**
     * Repositório de Campanhas
     *
     * @var \Doctrine\ODM\MongoDB\DocumentRepository
     */
    private $coupon_repository;

    public function __construct()
    {
        $this->webhook_repository = DocumentManager::getRepository(Webhook::class);
        $this->campaign_repository = DocumentManager::getRepository(Campaign::class);
        $this->coupon_repository = DocumentManager::getRepository(Coupon::class);
    }

    /**
     * Obtém um webhook, por id
     *
     * @param string $webhook_id
     * @return Webhook
     */
    public function get(string $webhook_id): Webhook
    {
        $webhook = $this->getOne($webhook_id);

        return $webhook;
    }

    /**
     * Método de obtenção de todas os webhooks
     *
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    public function getAll(array $data)
    {
        $criteria['deleted_at'] = null;

        if (array_key_exists('campaign_id', $data) === true)
        {
            $criteria['campaign'] = $data['campaign_id'];
        }
        elseif (array_key_exists('coupon_id', $data) === true)
        {
            $criteria['coupon'] = $data['coupon_id'];
        }

        $webhooks = $this->webhook_repository->findBy($criteria);

        return collect($webhooks);
    }

    /**
     * Cria um Webhook
     *
     * @param array $data
     * @return Webhook
     * @throws InvalidCampaignException
     * @throws InvalidCouponException
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     */
    public function create(array $data)
    {
        $webhook = $this->fill(new Webhook(), $data);

        DocumentManager::persist($webhook);
        DocumentManager::flush();

        return $webhook;
    }

    /**
     * Atualiza Webhook de acordo com parâmetros
     *
     * @param string $webhook_id
     * @param array $data
     * @return Webhook
     * @throws InvalidCampaignException
     * @throws InvalidCouponException
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     */
    public function update(string $webhook_id, array $data)
    {
        $webhook = $this->getOne($webhook_id);

        $this->fill($webhook, $data, true);

        DocumentManager::flush();

        return $webhook;
    }

    /**
     * Preenche os dados do Webhook
     *
     * @param Webhook $webhook
     * @param array $data
     * @param bool $update
     * @return Webhook
     * @throws InvalidCampaignException
     * @throws InvalidCouponException
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     */
    public function fill(Webhook $webhook, array $data, $update = false): Webhook
    {
        $webhook->removeAllVariants();

        foreach ($data['variants'] as $variant)
        {
            $notificationVariantPayload = new NotificationVariantPayload();
            $notificationVariantPayload->setVariants($variant);
            $webhook->addVariant($notificationVariantPayload);
        }

        if (array_key_exists('campaign_id', $data) === true)
        {
            $campaign = $this->campaign_repository->getOne($data['campaign_id']);

            if ($campaign === null)
            {
                throw new InvalidCampaignException('Campanha não encontrada');
            }

            $webhook->setCampaign($campaign);
        }

        if (array_key_exists('coupon_id', $data) === true)
        {
            $coupon = $this->coupon_repository->find($data['coupon_id']);

            if ($coupon === null)
            {
                throw new InvalidCouponException('Coupon não encontrado');
            }

            $webhook->setCoupon($coupon);
        }

        return $webhook;
    }

    /**
     * Obtém um Webhook por id
     *
     * @param string $webhook_id
     * @return Webhook
     */
    private function getOne(string $webhook_id): Webhook
    {
        $webhook = $this->webhook_repository
            ->findOneBy(['_id' => $webhook_id]);

        if ($webhook === null)
        {
            throw new NotFoundHttpException('Webhook não encontrado.');
        }

        return $webhook;
    }

    /**
     * Remove um webhook com base no id
     * @param string $webhook_id
     * @param bool $soft
     */
    public function delete(string $webhook_id, bool $soft = true): void
    {
        $webhook = $this->getOne($webhook_id);

        if ($soft)
        {
            $webhook->delete();
        }
        else
        {
            DocumentManager::remove($webhook);
        }

        DocumentManager::flush();
    }
}