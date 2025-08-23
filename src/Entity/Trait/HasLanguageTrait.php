<?php

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use App\Enum\Language;

trait HasLanguageTrait
{
    #[Groups(['title:read'])]
    #  ISO 639-1 (2-letter codes like en, fr, es)
    #[ORM\Column(length: 2, nullable: true, enumType: Language::class)]
    private ?Language $language = null;

    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    public function setLanguage(null | string | Language $language): static
    {
        if ($language === '') {
            $language = null;
        }

        if (is_string($language)) {
            $language = Language::from($language);
        }

        $this->language = $language;

        return $this;
    }
}
