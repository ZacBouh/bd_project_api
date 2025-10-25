<?php

declare(strict_types=1);

namespace App\Tests\DTO\Title;

use App\DTO\Title\TitleDTOFactory;
use App\Enum\Language;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class TitleDTOFactoryTest extends TestCase
{
    private TitleDTOFactory $factory;

    protected function setUp(): void
    {
        $normalizer = $this->createMock(NormalizerInterface::class);
        $this->factory = new TitleDTOFactory($normalizer);
    }

    public function testWriteDtoFromInputBagAllowsOmittingIsbn(): void
    {
        $input = new InputBag([
            'name' => 'My Title',
            'publisher' => '5',
            'language' => Language::EN->value,
            'description' => 'Test description',
            'releaseDate' => '',
            'artistsContributions' => [],
        ]);

        $dto = $this->factory->writeDtoFromInputBag($input, new FileBag());

        self::assertNull($dto->isbn);
    }

    public function testWriteDtoFromInputBagTrimsIsbn(): void
    {
        $input = new InputBag([
            'name' => 'Another Title',
            'publisher' => '5',
            'language' => Language::EN->value,
            'description' => 'Test description',
            'releaseDate' => '',
            'artistsContributions' => [],
            'isbn' => ' 9782070413119  ',
        ]);

        $dto = $this->factory->writeDtoFromInputBag($input, new FileBag());

        self::assertSame('9782070413119', $dto->isbn);
    }

    public function testWriteDtoFromInputBagConvertsWhitespaceIsbnToNull(): void
    {
        $input = new InputBag([
            'name' => 'Whitespace Title',
            'publisher' => '5',
            'language' => Language::EN->value,
            'description' => 'Test description',
            'releaseDate' => '',
            'artistsContributions' => [],
            'isbn' => '   ',
        ]);

        $dto = $this->factory->writeDtoFromInputBag($input, new FileBag());

        self::assertNull($dto->isbn);
    }
}
