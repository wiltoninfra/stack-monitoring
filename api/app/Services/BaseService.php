<?php

namespace Promo\Services;

use Promo\Documents\BaseDocument;
use Promo\Documents\Coupon;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Promo\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class BaseService
{

    protected $document;
    /**
     *
     * @var BaseRepository
     */
    protected $repository;


    /**
     * @param array $data
     * @return BaseDocument|null
     */
    public function create(array $data): ?BaseDocument
    {

        $this->document->fill($data);
        $document = $this->repository->create($this->document);

        return $document;
    }


    /**
     *
     */
    public function commit(): void
    {
        $this->repository->commit();
    }


    /**
     * Substitui (atualiza) conteúdo de um cupom
     *
     * @param array $data
     * @param string $coupon_id
     * @return null|Coupon
     */
    public function update(array $data, int $id): ?BaseDocument
    {
        $document = $this->getOne($id);
        $document->fill($data);

        DocumentManager::flush();

        return $document;
    }

    /**
     * @param array $data
     * @return array|Collection
     */
    public function index(array $data = [])
    {

        return $this->repository->index($data);
    }


    /**
     * @param array $data
     * @return BaseDocument|null
     */
    public function getOneBy(array $data): ?BaseDocument
    {
        $document = $this->repository->findOneBy($data);

        return $document;
    }


    /**
     * @param string $id
     * @return BaseDocument
     */
    public function getOne(string $id): BaseDocument
    {
        $document = $this->repository->findOneBy(['id' => $id]);

        if ($document === null) {
            throw new NotFoundHttpException('ID não encontrado.');
        }

        return $document;
    }


    /**
     * @param string $id
     * @param bool $soft
     * @return BaseDocument|null
     */
    public function delete(string $id, bool $soft = true): ?BaseDocument
    {
        $this->repository->delete($id, $soft);

    }

    /**
     * @param array $data
     */
    protected function fill(array $data)
    {
        $this->document->fill($data);
    }
}
