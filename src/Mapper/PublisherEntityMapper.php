<?php

namespace App\Mapper;

use App\Entity\Publisher;
use App\DTO\Publisher\PublisherWriteDTO;
use App\Entity\Title;

/**
 * @extends AbstractEntityMapper<Publisher, PublisherWriteDTO>
 */
class PublisherEntityMapper extends AbstractEntityMapper
{

    protected function getNormalizerCallbacks(): array
    {
        return [
            'titles' => function (array $titles) {
                $titlesRef = [];
                foreach ($titles as $title) {
                    $titlesRef[] = Title::normalizeCallback($title);
                }
                return $titlesRef;
            }
        ];
    }

    protected function getEntityClass(): string
    {
        return Publisher::class;
    }

    protected function instantiateEntity(): object
    {
        return new Publisher();
    }
}
