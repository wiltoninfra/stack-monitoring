<?php

namespace Promo\Services;

use Promo\Documents\Banner;
use Promo\Documents\Campaign;
use Illuminate\Support\Collection;
use Doctrine\MongoDB\Query\Builder;
use Promo\Exceptions\ExpiredBannerException;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BannerService
{
    /**
     * Repositório de Banner
     *
     * @var \Promo\Repositories\BannerRepository
     */
    private $repository;

    /**
     * Repositório de Campaign
     *
     * @var \Promo\Repositories\CampaignRepository
     */
    private $campaign_repository;

    /**
     * Serviço de Recompensas
     *
     * @var \Promo\Services\RewardService
     */
    private $reward_service;

    public function __construct(RewardService $reward_service)
    {
        $this->repository = DocumentManager::getRepository(Banner::class);
        $this->campaign_repository = DocumentManager::getRepository(Campaign::class);
        $this->reward_service = $reward_service;
    }

    /**
     * Obtém um banner, por id
     *
     * @param string $banner_id
     * @return Banner
     */
    public function get(string $banner_id): Banner
    {
        $banner = $this->getOne($banner_id);

        // Desativa campanha expirada
        if ($banner->isExpired())
        {
            $banner->disable();
        }

        DocumentManager::flush();

        return $banner;
    }

    /**
     * Obtém todas os cupons e aplica as condições
     *
     * @param array $criteria
     * @param array $sort
     * @param int $limit
     * @param int $skip
     * @return mixed
     */
    public function getAll(array $criteria = [], array $sort = [], int $limit = 10, int $skip = 0)
    {
        $qb = $this->getAllQuery($criteria);

        // Aplica as ordenações
        foreach ($sort as $field => $order)
        {
            $qb->sort($field, $order);
        }

        // Paginação
        $qb->limit($limit);
        $qb->skip($skip);

        $result = $qb->getQuery()
            ->execute()
            ->toArray();

        $result = array_values($result);

        return collect($result);
    }

    /**
     * Conta todos os resultados de cupons
     *
     * @param array $criteria
     * @return int
     */
    public function countAll(array $criteria = []): int
    {
        $total = $this->getAllQuery($criteria)
            ->count()
            ->getQuery()
            ->execute();

        return $total;
    }

    /**
     * Retorna Query Builder de acordo com critérios
     *
     * @param array $criteria
     * @return Builder
     */
    private function getAllQuery(array $criteria): Builder
    {
        $qb = $this->repository->createQueryBuilder()
            ->field('deleted_at')->exists(false);

        // Filtro para `active` usando booleano em string
        if (array_key_exists('active', $criteria) === true)
        {
            $qb->field('active')->equals(
                filter_var($criteria['active'], FILTER_VALIDATE_BOOLEAN)
            );
        }

        return $qb;
    }

    /**
     * @param array $data
     * @return Banner
     */
    public function create(array $data)
    {
        $banner = new Banner($data['image'], $data['global']);

        $this->fill($banner, $data);

        DocumentManager::persist($banner);
        DocumentManager::flush();

        return $banner;
    }

    /**
     * @param array $data
     * @param string $banner_id
     * @return null|Banner
     */
    public function update(array $data, string $banner_id): ?Banner
    {
        $banner = $this->getOne($banner_id);

        // Atualiza os dados do objeto
        $this->fill($banner, $data);

        DocumentManager::flush();

        return $banner;
    }

    /**
     * @param bool $status
     * @param string $banner_id
     * @return Banner
     * @throws ExpiredBannerException
     */
    public function updateStatus(bool $status, string $banner_id): Banner
    {
        $banner = $this->getOne($banner_id);

        if ($banner->isExpired())
        {
            throw new ExpiredBannerException();
        }

        $banner->setActive($status);
        DocumentManager::flush();

        return $banner;
    }

    /**
     * @param string $banner_id
     * @return Banner
     */
    public function getOne(string $banner_id): Banner
    {
        $campaign = $this->repository->getOne($banner_id);

        if ($campaign === null)
        {
            throw new NotFoundHttpException('Banner não encontrado.');
        }

        return $campaign;
    }

    /**
     * @param string $banner_id
     * @return Banner
     */
    public function delete(string $banner_id, bool $soft = true): Banner
    {
        
        if ($soft)
        {
            $banner = $this->getOne($banner_id);
            $banner->delete();
        }
        else
        {
            $banner = $this->repository->getOne($banner_id, true);
            DocumentManager::remove($banner);
        }

        DocumentManager::flush();

        return $banner;
    }

    /**
     * @param Banner $banner
     * @param array $data
     * @return Banner
     */
    private function fill(Banner $banner, array $data): Banner
    {
        $banner->setName($data['name'])

            ->setInfoTitle(data_get($data,'info.title'))
            ->setInfoDescription(data_get($data,'info.description'))

            ->setStartDate($data['start_date'] ?? null)
            ->setEndDate($data['end_date'] ?? null)

            ->setIosMinVersion($data['ios_min_version'] ?? null)
            ->setAndroidMinVersion($data['android_min_version'] ?? null)

            ->setTarget($data['target']['name'])
            ->setTargetParam($data['target']['param']);

        // Verifica se existe imagem no payload e seta imageUrl
        if ($data['image'] !== null) {
            $banner->setImageUrl($data['image']);
        }

        // Banners globais podem ter condições
        if ($banner->isGlobal() === true) {
            $banner->getConditions()
                ->setAreaCodes($data['conditions']['area_codes'] ?? null)
                ->setExcludedCampaigns($data['conditions']['excluded_campaigns'] ?? null);
        }
        // Já os não globais podem estar associados a campanhas
        else {
            $campaignId = data_get($data, 'campaign_id');
            $banner->setCampaign($this->campaign_repository->getOne($campaignId) ?? null);
        }


        return $banner;
    }

    /**
     * A partir de uma lista de ids, ordena os banners de interesse
     * e desativa os fora da lista
     *
     * @param array $ids
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function publish(array $ids)
    {
        // Ativa os banners selecionados e ordena
        $priority = count($ids);
        foreach ($ids as $id)
        {
            $this->repository->createQueryBuilder()
                ->updateOne()
                ->field('active')->set(true)
                ->field('priority')->set($priority--)
                ->field('_id')->equals($id)
                ->getQuery()
                ->execute();
        }

        // Desativa todos os banners que estão fora da seleção
        $this->repository->createQueryBuilder()
            ->updateMany()
            ->field('active')->set(false)
            ->field('priority')->set(0)
            ->field('_id')->notIn($ids)
            ->getQuery()
            ->execute();

        DocumentManager::flush();
    }

    /**
     * Obtém banners de um usuário
     *
     * @param int $consumer_id
     * @param array $data
     * @return Collection
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getConsumerBanners(int $consumer_id, array $data)
    {
        // Obtém campanhas ativas do consumer
        $associated = $this->reward_service->getConsumerActiveAssociatedCampaigns($consumer_id);

        // Obtém campanhas globais desativadas do consumer
        $disabled = $this->reward_service->getConsumerDisabledCampaigns($consumer_id);

        // Campanhas globais ativas (gerais)
        $global_active = $this->reward_service->getGlobalActiveCampaignsOnly();

        $qb = $this->repository->createQueryBuilder();

        $qb->field('campaign')->prime(true)
            ->field('active')->equals(true)
            // Já remove todas as campanhas globais já usadas pelo usuário
            ->field('campaign')->notIn($disabled)
            ->field('conditions.excluded_campaigns')->notIn(array_merge($associated, $global_active))
            // Filtra os globais ou direcionados
            ->addOr(
                $qb->expr()->addAnd(
                    $qb->expr()->field('global')->equals(true),
                    $qb->expr()->addOr(
                        $qb->expr()->field('conditions.area_codes')->exists(false),
                        $qb->expr()->field('conditions.area_codes')->equals(intval($data['area_code'] ?? 0))
                    )
                ),
                $qb->expr()->addAnd(
                    // Retorna banners direcionados a campanhas globais ativas ou exclusivas que o consumer ainda não usou
                    $qb->expr()->field('global')->equals(false),
                    $qb->expr()->field('campaign')->in(array_merge($associated, $global_active))
                )
            )
            // Garante que os banners são retornados como ordenados pelo painel
            ->sort('priority', 'desc');

        $result = array_values($qb->getQuery()
            ->execute()
            ->toArray()
        );

        return collect($result);
    }
}
