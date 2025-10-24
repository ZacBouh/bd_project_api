<?php

namespace App\Tests\Service;

use App\Entity\Artist;
use App\Entity\Copy;
use App\Entity\Publisher;
use App\Entity\PublisherCollection;
use App\Entity\Series;
use App\Entity\Title;
use App\Entity\UploadedImage;
use App\Enum\CopyCondition;
use App\Repository\UploadedImageRepository;
use App\Service\UploadedImageService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UploadedImageServiceTest extends TestCase
{
    public function testGetAllImagesReturnsRepositoryResults(): void
    {
        $image = new UploadedImage();

        $repository = $this->createMock(UploadedImageRepository::class);
        $repository
            ->expects($this->once())
            ->method('findBy')
            ->with([], ['createdAt' => 'DESC'])
            ->willReturn([$image]);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $service = new UploadedImageService($entityManager, $repository);

        self::assertSame([$image], $service->getAllImages());
    }

    public function testDeleteUploadedImageThrowsWhenNotFound(): void
    {
        $repository = $this->createMock(UploadedImageRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(1337)
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $service = new UploadedImageService($entityManager, $repository);

        $this->expectException(EntityNotFoundException::class);

        $service->deleteUploadedImage(1337);
    }

    public function testDeleteUploadedImageDetachesCoverReferencesAndRemovesImage(): void
    {
        $image = new UploadedImage();

        $repository = $this->createMock(UploadedImageRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($image);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $coverEntitiesMap = $this->createCoverEntities($image);
        $repositories = [];

        foreach ($coverEntitiesMap as $className => $entities) {
            /** @var MockObject&ObjectRepository $objectRepository */
            $objectRepository = $this->createMock(ObjectRepository::class);
            $objectRepository
                ->expects($this->once())
                ->method('findBy')
                ->with(['coverImage' => $image])
                ->willReturn($entities);

            $repositories[$className] = $objectRepository;
        }

        $entityManager
            ->expects($this->exactly(count($repositories)))
            ->method('getRepository')
            ->willReturnCallback(function (string $className) use ($repositories) {
                $this->assertArrayHasKey($className, $repositories);

                return $repositories[$className];
            });

        $entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($image);

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $service = new UploadedImageService($entityManager, $repository);

        $service->deleteUploadedImage(1);

        foreach ($coverEntitiesMap as $entities) {
            foreach ($entities as $entity) {
                self::assertNull($entity->getCoverImage());
                self::assertFalse($entity->getUploadedImages()->contains($image));
            }
        }
    }

    /**
     * @return array<class-string, list<Artist|Copy|Publisher|PublisherCollection|Series|Title>>
     */
    private function createCoverEntities(UploadedImage $image): array
    {
        $artist = new Artist();
        $artist->setCoverImage($image);

        $copy = new Copy();
        $copy->setCopyCondition(CopyCondition::GOOD);
        $copy->setCoverImage($image);

        $publisher = new Publisher();
        $publisher->setCoverImage($image);

        $publisherCollection = new PublisherCollection();
        $publisherCollection->setCoverImage($image);

        $series = new Series();
        $series->setCoverImage($image);

        $title = new Title();
        $title->setCoverImage($image);

        return [
            Artist::class => [$artist],
            Copy::class => [$copy],
            Publisher::class => [$publisher],
            PublisherCollection::class => [$publisherCollection],
            Series::class => [$series],
            Title::class => [$title],
        ];
    }
}
