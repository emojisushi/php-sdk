<?php

namespace Emojisushi\Api\Objects;

class PaginationMeta {
    public ?int $limit;
    public ?int $offset;
    public int $total;
}
