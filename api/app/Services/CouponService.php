<?php

namespace Promo\Services;

use Carbon\Carbon;
use Promo\Documents\Coupon;
use Promo\Documents\Campaign;
use Promo\Documents\ConsumerCoupon;
use Doctrine\MongoDB\Query\Builder;
use Promo\Documents\ConsumerCampaign;
use Promo\Documents\Enums\CouponRedirectionType;
use Promo\Exceptions\InvalidCouponException;
use Promo\Services\Consumer\ConsumerService;
use Promo\Exceptions\InvalidCampaignException;
use Promo\Documents\Enums\AssociationTypeEnum;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CouponService
{
    /**
     * Repositório de Campaign
     *
     * @var \Promo\Repositories\CampaignRepository
     */
    private $campaign_repository;

    /**
     * Repositório de ConsumerCampaign
     *
     * @var \Promo\Repositories\ConsumerCampaignRepository
     */
    private $consumer_campaign_repository;

    /**
     * Repositório de ConsumerCoupon
     *
     * @var \Promo\Repositories\ConsumerCouponRepository
     */
    private $consumer_coupon_repository;

    /**
     * Repositório de ConsumerCampaign
     *
     * @var \Promo\Repositories\CouponRepository
     */
    private $coupon_repository;

    private $action_url_service;


    /**
     * Serviço de ConsuemrCampaign
     *
     * @var \Promo\Services\ConsumerCampaignService
     */
    private $consumer_campaign_service;

    public function __construct(ConsumerCampaignService $consumer_campaign_service, ActionUrlService $action_url_service)
    {
        $this->campaign_repository = DocumentManager::getRepository(Campaign::class);
        $this->coupon_repository = DocumentManager::getRepository(Coupon::class);
        $this->consumer_campaign_repository = DocumentManager::getRepository(ConsumerCampaign::class);
        $this->consumer_coupon_repository = DocumentManager::getRepository(ConsumerCoupon::class);
        $this->consumer_campaign_service = $consumer_campaign_service;
        $this->action_url_service = $action_url_service;
    }

    /**
     * Cria cupom, verificando duplicidade
     *
     * @param array $data
     * @return null|Coupon
     * @throws InvalidCampaignException
     * @throws InvalidCouponException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Promo\Exceptions\ValidationException
     */
    public function create(array $data): ?Coupon
    {
        $this->lookForDuplicated($data['code']);

        if (array_key_exists('campaign_id', $data)
            && $data['redirection_type'] === CouponRedirectionType::WEBVIEW)
        {
            $campaign = $this->campaign_repository->getOne($data['campaign_id']);

            if ($campaign === null || $campaign->isActive() === false)
            {
                throw new InvalidCampaignException('Campanha inválida.');
            }

            if ($campaign->isGlobal())
            {
                throw new InvalidCampaignException('Cupons não podem ser associados a campanhas globais.');
            }
        } else
        {
            $campaign = null;
        }

        $coupon = new Coupon($campaign, $data['code']);
        $coupon = $this->fill($coupon, $data);

        DocumentManager::persist($coupon);
        DocumentManager::flush();

        return $coupon;
    }

    /**
     * Substitui (atualiza) conteúdo de um cupom
     *
     * @param array $data
     * @param string $coupon_id
     * @return null|Coupon
     * @throws \Promo\Exceptions\ValidationException
     */
    public function update(array $data, string $coupon_id): ?Coupon
    {
        $coupon = $this->getOne($coupon_id);

        // Atualiza os dados do objeto
        $this->fill($coupon, $data);

        DocumentManager::flush();

        return $coupon;
    }

    /**
     * Obtém todas as campanhas e aplica as condições
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
     * @param bool $status
     * @param string $coupon_id
     * @return Coupon
     * @throws InvalidCouponException
     */
    public function updateStatus(bool $status, string $coupon_id): Coupon
    {
        $coupon = $this->getOne($coupon_id);

        if ($coupon->isValid() === false)
        {
            throw new InvalidCouponException('Cupom não pode ser reativado por ter atingido suas condições de uso.');
        }

        $coupon->setActive($status);
        DocumentManager::flush();

        return $coupon;
    }

    /**
     * @param string $banner_id
     * @return Coupon
     */
    public function getOne(string $banner_id): Coupon
    {
        $coupon = $this->coupon_repository->findOneBy(['id' => $banner_id]);

        if ($coupon === null)
        {
            throw new NotFoundHttpException('Banner não encontrado.');
        }

        return $coupon;
    }

    /**
     * Associa um usuário a uma campanha através de um cupom
     *
     * @param array $data
     * @param integer $consumer_id
     * @return \Promo\Documents\Coupon
     *
     * @throws InvalidCampaignException
     * @throws InvalidCouponException
     * @throws \MongoException
     * @throws \Promo\Exceptions\GlobalCampaignException
     */
    public function apply(array $data, int $consumer_id): Coupon
    {
        \Log::info('Requisição de aplicação de cupom recebida', array_merge(
            ['consumer_id' => $consumer_id], $data)
        );

        // Trata do cupom
        $coupon = $this->getCoupon($consumer_id, $data['conditions'], $data['code']);

        if ($coupon->getRedirectionType() == CouponRedirectionType::WEBVIEW)
        {
            $campaign = $coupon->getCampaign();

            // Trata da campanha
            $this->makeCampaignAssociation($consumer_id, $campaign);
        }

        if ($coupon->getRedirectionType() == CouponRedirectionType::ACTION_URL) {
            //codigo nojento, precisamos tirar cupom do promo e deixar flexivel.
            //Recomendo um servico chamado workflow, flow ou algo do tipo de tenha uma sequencia de passos a serem
            //e um cupom pode disporar um flow especifico.
            if (trim($coupon->getCode()) == 'VIRADA2020') {
                $this->action_url_service->reveillonSalvador2020($consumer_id);
            } else {
                $this->action_url_service->summerCampaign2019($consumer_id);
            }

        }

        // Incrementa estatísticas
        $coupon->getStats()->incrementCurrentUses();

        \Log::info('Cupom aplicado a usuário', array_merge(
            ['consumer_id' => $consumer_id, 'coupon-applied' => true], $data)
        );

        DocumentManager::flush();

        return $coupon;
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
     * Conta somente os ativos
     *
     * @param array $criteria
     * @return int
     */
    public function countActive(array $criteria = []): int
    {
        if (array_key_exists('active', $criteria)
            && filter_var($criteria['active'], FILTER_VALIDATE_BOOLEAN) == false)
        {
            return 0;
        }

        $criteria['active'] = true;

        $active = $this->getAllQuery($criteria)
            ->count()
            ->getQuery()
            ->execute();

        return $active;
    }

    /**
     * Retorna Query Builder de acordo com critérios
     *
     * @param array $criteria
     * @return Builder
     */
    private function getAllQuery(array $criteria): Builder
    {
        $qb = $this->coupon_repository->createQueryBuilder()
            ->field('deleted_at')->exists(false);

        // Filtro por campanha específica
        if (array_key_exists('code', $criteria) === true)
        {
            $qb->field('code')->equals(strtoupper($criteria['code']));
        }

        // Filtro para `active` usando booleano em string
        if (array_key_exists('active', $criteria) === true)
        {
            $qb->field('active')->equals(
                filter_var($criteria['active'], FILTER_VALIDATE_BOOLEAN)
            );
        }

        // Filtro para tipo de cupom
        if (array_key_exists('redirection_type', $criteria) === true)
        {
            $qb->field('redirection_type')->equals($criteria['redirection_type']);
        }

        // Filtro para `global` usando booleano em string
        if (array_key_exists('global', $criteria) === true)
        {
            $qb->field('global')->equals(
                filter_var($criteria['global'], FILTER_VALIDATE_BOOLEAN)
            );
        }

        // Filtro por campanha específica
        if (array_key_exists('campaign_id', $criteria) === true)
        {
            $qb->field('campaign')->equals($criteria['campaign_id']);
        }

        // Filtro por lista de ids separados por vírgula
        if (array_key_exists('ids', $criteria) === true)
        {
            $ids = explode(',', $criteria['ids']);

            $qb->field('_id')->in($ids);
        }

        return $qb;
    }

    /**
     * Retorna (buscando ou criando) uma relação ConsumerCampaign
     *
     * @param integer $consumer_id
     * @param Campaign $campaign
     * @return \Promo\Documents\ConsumerCampaign
     *
     * @throws InvalidCampaignException
     * @throws \MongoException
     * @throws \Promo\Exceptions\GlobalCampaignException
     */
    private function getConsumerCampaign(int $consumer_id, Campaign $campaign)
    {
        $consumer_campaign = $this->consumer_campaign_repository->getOne($consumer_id, $campaign);

        // Se a relação não existir, cria e persiste
        if ($consumer_campaign === null)
        {
            $this->consumer_campaign_service->associateConsumers([$consumer_id], $campaign->getId(), AssociationTypeEnum::COUPON);
        }
        else
        {
            throw new InvalidCampaignException('Você já participa desta campanha.');
        }

        return $consumer_campaign;
    }

    /**
     * Obtém cupom e checa se usuário tem direito
     *
     * @param integer $consumer_id
     * @param array $consumer_conditions
     * @param string $code
     * @return Coupon|null
     *
     * @throws InvalidCouponException
     */
    private function getCoupon(int $consumer_id, array $consumer_conditions, string $code): ?Coupon
    {
        $coupon = $this->coupon_repository->getOne($code);

        // Se cupom já atingiu limite de usos ou foi desativado
        if ($coupon->isActive() === false)
        {
            throw new InvalidCouponException('O cupom não é mais válido ou atingiu limite de usos.');
        }

        // Quando o cupom requer associação prévia a usuário
        if ($coupon->isGlobal() === false)
        {
            $consumer_coupon = $this->consumer_coupon_repository->getOne($consumer_id, $coupon);

            if ($consumer_coupon === null || $consumer_coupon->isActive() === false)
            {
                throw new InvalidCouponException('Cupom inválido para usuário.');
            }
        }
        else
        {
            // Quando global, verifica se o usuário atende às condições
            $this->matchConditions($consumer_conditions, $coupon);
        }

        return $coupon;
    }

    /**
     * Em cupons globais, checam-se as condições de acordo com o que
     * o LegacyApi entregou sobre o usuário que tenta aplicar o cupom
     *
     * @param array $consumer_conditions
     * @param Coupon $coupon
     * @throws InvalidCouponException
     * @return void
     */
    private function matchConditions(array $consumer_conditions, Coupon $coupon): void
    {
        $conditions = $coupon->getConditions();

        if ($conditions === null)
        {
            return;
        }

        // Verifica se é primeira transacao do usuário
        if (($conditions->getFirstTransactionOnly() !== null
            && $conditions->getFirstTransactionOnly() === true
            && $consumer_conditions['first_transaction'] === false)

        // Verifica se DDD está dentro da lista de DDDs requeridos do cupom
        || ($conditions->getAreaCodes() !== null
            && in_array($consumer_conditions['area_code'], $conditions->getAreaCodes()) === false))
        {
            throw new InvalidCouponException('Usuário não é elegível para este cupom.');
        }
    }

    /**
     * Verifica campanha e gera associação, se tudo deu certo
     *
     * @param int $consumer_id
     * @param Campaign $campaign
     * @throws InvalidCampaignException
     * @throws \MongoException
     * @throws \Promo\Exceptions\GlobalCampaignException
     */
    private function makeCampaignAssociation(int $consumer_id, Campaign $campaign)
    {
        // TODO deixar a verificação mais escalável (sem muitos ifs)
        if ($campaign === null || $campaign->isActive() === false)
        {
            throw new InvalidCampaignException('Campanha inválida.');
        }

        // Atrela o usuário à campanha
        $this->getConsumerCampaign($consumer_id, $campaign);
    }

    /**
     * Obtém cupom por ID
     *
     * @param string $coupon_id
     * @return Coupon
     * @throws InvalidCouponException
     */
    private function getCouponById(string $coupon_id): Coupon
    {
        $coupon = $this->coupon_repository->findOneBy(['_id' => $coupon_id]);

        if ($coupon === null)
        {
            throw new InvalidCouponException('Cupom inexistente.', 404);
        }
        else if ($coupon->isActive() === false)
        {
            throw new InvalidCouponException('Cupom inválido.');
        }
        else if ($coupon->isGlobal() === true)
        {
            throw new InvalidCouponException('Cupom não pode ser associado.');
        }

        return $coupon;
    }

    /**
     * Preenche dados de cupom
     *
     * @param Coupon $coupon
     * @param array $data
     * @return Coupon
     * @throws \Promo\Exceptions\ValidationException
     */
    private function fill(Coupon $coupon, array $data): Coupon
    {
        $coupon->setGlobal($data['global'])
            ->setMaxAssociations($data['max_associations'] ?? null)
            ->setWebviewUrl($data['webview_url'] ?? null)
            ->setRedirectionType($data['redirection_type'])
            ->setAppScreenPath($data['app_screen_path'] ?? null);

        $coupon->getConditions()
            ->setAreaCodes($data['conditions']['area_codes'] ?? null)
            ->setFirstTransactionOnly($data['conditions']['first_transaction_only'] ?? null);

        return $coupon;
    }

    /**
     * Procura por cupons com o mesmo código e ativos
     *
     * @param string $code
     * @return void
     * @throws InvalidCouponException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function lookForDuplicated(string $code): void
    {
        $coupon = $this->coupon_repository->findOneBy(['code' => strtoupper($code)]);

        if ($coupon !== null && $coupon->isActive() === true)
        {
            throw new InvalidCouponException('Já existe um cupom ativo com o mesmo código.');
        }
    }

    /**
     * Associa consumers a um cupom, por id de cupom
     *
     * @param array $consumers
     * @param string $coupon_id
     * @throws InvalidCouponException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function associateConsumers(array $consumers, string $coupon_id): void
    {
        $coupon = $this->getCouponById($coupon_id);

        foreach ($consumers as $consumer_id)
        {
            try
            {
                // Tenta criar o documento com a relação da associação
                DocumentManager::createQueryBuilder(ConsumerCoupon::class)
                    ->insert()
                    ->setNewObj([
                        'consumer_id' => (int) $consumer_id,
                        'coupon' => $coupon,
                        'active' => true,
                        'created_at' => Carbon::now()
                    ])
                    ->getQuery()
                    ->execute();

                // Incrementa o contador de associações da campanha
                $coupon->getStats()->incrementCurrentAssociations();
            }
            catch (\MongoDuplicateKeyException $e)
            {
                continue;
            }
        }

        DocumentManager::flush();

        \Log::info('Associando usuários a cupom', [
            'total' => count($consumers),
            'consumers' => $consumers,
            'coupon_id' => $coupon_id
        ]);
    }

    /**
     * Remove associação de consumers ao cupom, por id do cupom
     * @param string $coupon_id
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function removeAssociatedConsumers(string $coupon_id): void
    {
        DocumentManager::createQueryBuilder(ConsumerCoupon::class)
            ->remove()
            ->field('coupon')->equals($coupon_id)
            ->getQuery()
            ->execute();

        DocumentManager::flush();
    }

    /**
     * Remove uma cupom com base no id
     * @param string $coupon_id
     * @param bool $soft
     * @return Coupon
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
   public function delete(string $coupon_id, bool $soft = true): Coupon
   {
        $coupon = $this->getOne($coupon_id);

        if ($soft)
        {
            $coupon->delete();
        }
        else
        {
            DocumentManager::remove($coupon);
            $this->removeAssociatedConsumers($coupon_id);
        }

        DocumentManager::flush();

        return $coupon;
   }
}
