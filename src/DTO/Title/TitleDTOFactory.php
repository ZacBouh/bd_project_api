<?php

namespace App\DTO\Title;

use App\DTO\Builder\AbstractDTOFactory;
use App\Entity\Title;
use App\DTO\Title\TitleReadDTO;
use App\DTO\Title\TitleWriteDTO;
use App\Entity\Publisher;
use App\Enum\Language;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * @extends AbstractDTOFactory<Title, TitleReadDTO, TitleWriteDTO>
 */
class TitleDTOFactory extends AbstractDTOFactory
{
    public function __construct(
        private NormalizerInterface $serializer,
    ) {
        parent::__construct();
    }

    /**
     * @param InputBag<scalar> $i
     * @return TitleWriteDTO
     */
    public function writeDtoFromInputBag(InputBag $i, ?FileBag $f = null): object
    {
        $id = $this->getIdInput($i);

        /** @var array{artist: int, skills: string[]} $artistsContributions */
        //@phpstan-ignore-next-line
        $artistsContributions = array_map(fn($contribution) => ['artist' => (int) $contribution['artist'], 'skills' => $contribution['skills']], $i->all('artistsContributions'));
        $dto = new TitleWriteDTO(
            $i->getString('name'),
            $i->getInt('publisher'),
            $i->getEnum('language', Language::class),
            $id,
            trim($i->getString('description')) !== '' ? $i->getString('description') : null,
            $this->getCoverImageFile($f),
            $artistsContributions, //@phpstan-ignore-line
            $i->getString('releaseDate') !== '' ? $i->getString('releaseDate') : null,
            $this->getUploadedImagesFiles($f)
        );
        return $dto;
    }

    /**
     * @param Title $title
     */
    public function readDTOFromEntity(object $title): TitleReadDTO
    {
        $artistsContributions = [];

        foreach ($title->getArtistsContributions() as $contribution) {
            $artistId = $contribution->getArtist()->getId();
            if (!isset($artistsContributions[$artistId])) {
                $artistsContributions[$artistId] = [
                    'artist' => [
                        'id' => $artistId,
                        'fullName' => $contribution->getArtist()->getFullName()
                    ],
                    'skills' => []
                ];
            }
            $artistsContributions[$artistId]['skills'][] = $contribution->getSkill()->getName();
        }

        $publisher = Publisher::normalizeCallback($title->getPublisher());

        $id = $this->validateId($title);

        $coverImage = $this->getCoverImageDTO($title);

        $name = $this->validateName($title);

        $uploadedImages = [];
        foreach ($title->getUploadedImages() as $image) {
            $uploadedImages[] = $this->serializer->normalize($image, null, ['groups' => ['titleReadDTO']]);
        }

        $language = $this->validateLanguage($title);

        return new TitleReadDTO(
            $id,
            $name,
            $publisher,
            $language,
            $title->getDescription(),
            [],
            $coverImage,
            $title->getReleaseDate()?->format('Y-m-d'),
            [],
        );
    }
}
