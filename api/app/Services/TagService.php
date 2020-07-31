<?php

namespace Promo\Services;

use Promo\Documents\Tag;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TagService
{
    /**
     * Repositório de Tag
     *
     * @var \Doctrine\ODM\MongoDB\DocumentRepository
     */
    private $tag_repository;

    public function __construct()
    {
        $this->tag_repository = DocumentManager::getRepository(Tag::class);
    }

    /**
     * Método de obtenção de todas as tags
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAll()
    {
        $tags = $this->tag_repository->findBy(['deleted_at' => null]);

        return collect($tags);
    }

    /**
     * Cria uma Tag
     *
     * @param array $data
     * @return Tag
     * @throws \Promo\Exceptions\ValidationException
     */
    public function create(array $data)
    {
        $tag = $this->fill(new Tag(), $data);

        DocumentManager::persist($tag);
        DocumentManager::flush();

        return $tag;
    }

    /**
     * Atualiza Tag de acordo com parâmetros
     *
     * @param string $tag_id
     * @param array $data
     * @return Tag
     * @throws \Promo\Exceptions\ValidationException
     */
    public function update(string $tag_id, array $data)
    {
        $tag = $this->getOne($tag_id);

        $this->fill($tag, $data);

        DocumentManager::flush();

        return $tag;
    }

    /**
     * Preenche os dados de Tag
     *
     * @param Tag $tag
     * @param array $data
     * @return Tag
     * @throws \Promo\Exceptions\ValidationException
     */
    public function fill(Tag $tag, array $data): Tag
    {
        $tag->setName($data['name'])
            ->setAbbreviation($data['abbreviation'])
            ->setColor($data['color']);

        return $tag;
    }

    /**
     * Obtém uma Tag por id
     *
     * @param string $tag_id
     * @return Tag
     */
    private function getOne(string $tag_id): Tag
    {
        $tag = $this->tag_repository
            ->findOneBy(['_id' => $tag_id]);

        if ($tag === null)
        {
            throw new NotFoundHttpException('Tag não encontrada.');
        }

        return $tag;
    }

    /**
     * Obtém várias tags a partir de ids em string
     *
     * @param null|array $ids
     * @return array
     */
    public function getMany(?array $ids)
    {
        if ($ids === null || empty($ids))
        {
            return [];
        }

        $tags = $this->tag_repository->findBy(['_id' => ['$in' => $ids]]);

        return $tags;
    }

    /**
     * Remove uma tag com base no id
     * @param string $tag_id
     * @param bool $soft
     */
   public function delete(string $tag_id, bool $soft = true): void
   {
        $tag = $this->getOne($tag_id);

        if ($soft)
        {
            $tag->delete();
        }
        else
        {
            DocumentManager::remove($tag);
        }
        
        DocumentManager::flush();
   }
}
