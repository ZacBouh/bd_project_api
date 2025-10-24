<?php

namespace App\Exception;

use RuntimeException;

/**
 * @psalm-immutable
 */
class CopiesNotForSaleException extends RuntimeException
{
    /**
     * @param int[] $copyIds
     */
    public function __construct(private array $copyIds)
    {
        $copyIds = array_values(array_map('intval', $copyIds));
        $this->copyIds = $copyIds;

        parent::__construct(sprintf(
            'Some items are no longer available for sale: %s',
            implode(', ', $copyIds)
        ));
    }

    /**
     * @return int[]
     */
    public function getCopyIds(): array
    {
        return $this->copyIds;
    }
}
