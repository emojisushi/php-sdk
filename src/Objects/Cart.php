<?php

namespace Emojisushi\Api\Objects;

class Cart {
    /**
     * @var CartProduct[]
     */
    public array $data;
    public string $total;
    public string $totalQuantity;
}
