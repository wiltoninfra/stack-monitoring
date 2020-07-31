<?php

namespace Promo\Repositories;

use Doctrine\ODM\MongoDB\DocumentRepository;

class BannerRepository extends DocumentRepository
{
    public function getOne(string $id, bool $deleted = false)
    {
        $filters['_id'] = $id;

        if (!$deleted) {
            $filters['deleted_at'] = null;
        }
        // return $this->findOneBy(['_id' => $id, 'deleted_at' => null]);
        return $this->findOneBy($filters);
    }
}