<?php

namespace App\ArgumentResolver;

use App\Entity\Artist;
use App\Repository\SkillRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;

class ArtistPayloadResolver implements ValueResolverInterface
{
    public function __construct(
        private SkillRepository $skillRepository,
        private SerializerInterface $serializer,
        private LoggerInterface $logger
    ) {}

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();
        if (!($argumentType === Artist::class)) {
            return [];
        }

        $data = json_decode($request->getContent(), true);
        $skillNames = $data['skills'] ?? [];
        unset($data['skills']);

        $cleanedJson = json_encode($data);

        /** @var Artist $artist */
        $artist = $this->serializer->deserialize($cleanedJson, Artist::class, 'json');

        $skills = $this->skillRepository->findBy(['name' => $skillNames]);
        foreach ($skills as $skill) {
            $artist->addSkill($skill);
        }

        yield $artist;
        return;
    }
}
