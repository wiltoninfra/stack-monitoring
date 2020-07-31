<?php

namespace Promo\Repositories;

use Carbon\Carbon;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Promo\Documents\BaseDocument;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Promo\Traits\QueryFilters;

class BaseRepository extends DocumentRepository
{

    use QueryFilters;

    protected $document;


    /**
     * @param array $data
     * @return array|\Illuminate\Support\Collection
     */
    public function index(array $data = [])
    {
        //dd($data);
        return $this->getAll($data);

    }

    /**
     * @param BaseDocument $document
     * @return BaseDocument
     */
    public function create(BaseDocument $document)
    {
        $this->dm->persist($document);
        return $document;
    }

    /**
     * @param $oid
     * @return mixed
     */
    public function show($oid)
    {
        return $this->document::find($oid);
    }

    /**
     * @param $data
     * @param $id
     * @return object
     */
    public function update($data, $id)
    {
        $document = $this->findOneBy(['_id' => $id]);
        $document->fill($data);
        $this->commit();

        return $document;
    }

    /**
     * @param $id
     * @param bool $soft
     * @return object
     */
    public function delete($id, $soft = true)
    {
        $document = $this->findOneBy(['_id' => $id]);

        if ($soft) {
            $document->delete();
        } else {
            $this->dm->remove($document);

        }

        $this->commit();

        return $document;
    }

    /**
     *
     */
    public function commit()
    {
        $this->dm->flush();
    }


}