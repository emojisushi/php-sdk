<?php

namespace Emojisushi\Api\Objects;

class PaymentMethodsList {
    /**
     * @var PaymentMethod[]
     */
    public array $data;

    /**
     * @var PaginationMeta
     */
    public $meta;
}
