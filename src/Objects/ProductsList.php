<?php

namespace Emojisushi\Api\Objects;

class ProductsList {
    /**
     * @var Product[]
     */
    public array $data;

    /**
     * @var PaginationMeta
     */
    public $meta;
}
