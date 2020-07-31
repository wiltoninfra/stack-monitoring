<?php


namespace Promo\Traits;


trait QueryFilters
{
    /**
     * @var \Doctrine\MongoDB\Query\Builder
     *
     */
    protected $builder;

    protected $filter_names = ['çount', 'fields', 'page', 'page_size', 'q', 'sort'];

    protected $criteria = [];

    /**
     * @param array $criterias
     */
    protected function init(array $criterias = [])
    {
        $this->builder = $this->createQueryBuilder()->eagerCursor(true);
        $this->criterias = $criterias;

    }

    /**
     * @param array $criterias
     * @return \Illuminate\Support\Collection
     */
    protected function get(array $criterias = [])
    {

        $this->init($criterias);
        $this->applyFilters();
        $result = $this->builder->getQuery()->execute()->toArray();
        $result = array_values($result);


        return collect($result);

    }

    /**
     * @param array $criterias
     * @return mixed
     */
    private function getCount(array $criterias = [])
    {
        $this->init($criterias);
        $this->applyFilters();
        return $this->builder->count()
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $criterias
     * @return array|\Illuminate\Support\Collection
     */
    protected function getAll(array $criterias = [])
    {

        $total_count = 0;
        $show_total = false;

        if (array_key_exists('count', $criterias)) {
            $show_total = true;
            $total_count = $this->getCount($criterias);
        }

        $this->init($criterias);
        $this->applyFilters();


        $result = $this->builder->getQuery()->execute()->toArray();
        $result = collect(array_values($result));
        $result = ['data' => $result];
        if ($show_total) {
            $result['total_count'] = $total_count;
        }

        return $result;

    }

    /**
     * @param array $criterias
     * @param int $page_size
     * @param int $page
     * @return array
     */
    protected function getPaginated(array $criterias = [], int $page_size = 10, int $page = 0)
    {

        if (array_key_exists('page', $criterias)) {
            $page = (int)$criterias['page'];
        }

        if (array_key_exists('page_size', $criterias)) {
            $page_size = (int)$criterias['page_size'];
        }

        $total_count = $this->getCount($criterias);

        $this->init($criterias);
        $this->applyFilters();
        $this->paginate($page, $page_size);

        $result = $this->builder->getQuery()->execute()->toArray();

        $paginate_status = $this->paginateStatus($total_count, $page_size, $page);
        $paginate_status['count'] = $total_count;

        $result = [
            'data' => collect(array_values($result)),
            'pagination' => $paginate_status,
            'total_count' => $total_count
        ];

        return $result;

    }

    /**
     * @param $total_count
     * @param $page_size
     * @param $page
     * @return array
     */
    private function paginateStatus($total_count, $page_size, $page)
    {
        $total_pages = (int)ceil($total_count / $page_size);

        return [
            'X-Total-Pages' => $total_pages,
            'X-Page-Size' => $page_size,
            'X-Current-Page' => $page,
            'X-Next-Page' => ($page < ($total_pages - 1)) ? ($page + 1) : '',
            'X-Previous-Page' => ($page == 0) ? '' : ($page - 1)
        ];
    }

    /**
     * @param $page
     * @param $page_size
     */
    private function paginate($page, $page_size)
    {
        $skip = ($page <= 0) ? 0 : ($page * $page_size);
        $this->builder->limit($page_size);
        $this->builder->skip($skip);
    }

    /**
     *
     */
    protected function applyFilters()
    {

        $this->_fields();
        $this->_expand();
        $this->_filter();
        $this->_sort();
        $this->_count();


    }

    /**
     * @param array $criterias
     */
    private function _fields($criterias = [])
    {

        if (array_key_exists('fields', $criterias)) {
            $fields = explode(",", $criterias['fields']);
            if (count($fields) > 0) {
                $this->builder->select($fields);
            }
        }

    }

    /**
     *
     */
    private function _expand()
    {

    }

    /**
     * @param array $criteria \
     */
    private function _filter(array $criteria = [])
    {

        //dd($criteria);
        foreach ($criteria as $key => $value) {

            if (!in_array($key, $this->filter_names) && !str_contains($key, ":")) {
                //dd($key);
                $this->builder->field($key)->equals($value);
            }
        }

    }

    /**
     *
     */
    private function _count()
    {

    }

    /**
     * @param array $criterias
     */
    private function _sort(array $criterias = [])
    {
        if (array_key_exists('sort', $criterias)) {
            $fields = explode(",", $criterias['fields']);
            if (count($fields) > 0) {
                foreach ($criterias['sort'] as $field => $order) {
                    $this->builder->sort($field, $order);
                }
            }

        }
    }


}