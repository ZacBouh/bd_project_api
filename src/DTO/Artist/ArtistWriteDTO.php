<?php

namespace App\DTO\Artist;

use App\Enum\Skill;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ArtistWriteDTO
{
    /**
     * @param array<string> $skills
     */
    public function __construct(
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $id,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $pseudo,
        #[Assert\All(constraints: [
            new Assert\Type('string'),
            new Assert\Choice(callback: 'skillChoices')
        ])]
        #[Assert\Count(
            min: 1,
            minMessage: 'You have to select at least one skill'
        )]
        public ?array $skills,

        #[Ignore]
        public ?UploadedFile $coverImageFile,

        #[Assert\AtLeastOneOf(constraints: [
            new Assert\Date(),
            new Assert\DateTime(),
        ])]
        public ?string $birthDate,
        #[Assert\AtLeastOneOf(constraints: [
            new Assert\Date(),
            new Assert\DateTime(),
        ])]
        public ?string $deathDate,
    ) {}

    #[Assert\Callback]
    public function validateName(ExecutionContextInterface $context): void
    {
        $stringLength = static fn(?string $s): int  => mb_strlen($s ?? '');
        if (
            $stringLength($this->pseudo) < 3 &&
            $stringLength($this->firstName) < 2 &&
            $stringLength($this->lastName) < 2
        ) {
            $context->buildViolation('Artist creation requires that at least pseudo, firstName or lastName is provided.')
                ->addViolation();
        }
    }

    /**
     * @return array<non-empty-string>
     */
    public function skillChoices(): array
    {
        return Skill::values();
    }
}
