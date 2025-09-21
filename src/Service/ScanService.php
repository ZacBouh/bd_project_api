<?php

namespace App\Service;

use App\DTO\Scan\ScanDTOFactory;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\File;

class ScanService
{

    public function __construct(
        private LoggerInterface $logger,
        private HttpClientInterface $http,
        private ScanDTOFactory $dtofactory,
        private ValidatorInterface $validator,
    ) {}

    /**
     * @param InputBag<scalar> $inputBag
     * @return string JSON encoded string
     */
    public function scanComicPicture(InputBag $inputBag, FileBag $files): string
    {
        $writeDto = $this->dtofactory->writeDtoFromInputBag($inputBag, $files);
        $violations = $this->validator->validate($writeDto);
        if (count($violations) > 0) {
            throw new ValidationFailedException($writeDto, $violations);
        }
        $bookPart = 'empty';
        $imageFile = null;
        if ($writeDto->BACK_COVER) {
            $bookPart = 'BACK_COVER';
            $imageFile = $writeDto->BACK_COVER;
        } elseif ($writeDto->FRONT_COVER) {
            $bookPart = 'FRONT_COVER';
            $imageFile = $writeDto->FRONT_COVER;
        } else {
            $bookPart = 'SPINE';
            $imageFile = $writeDto->SPINE;
        }
        /** @var File $imageFile */
        $this->logger->debug(sprintf('Scan Request input: %s %s', $bookPart, $imageFile->getPath()));

        $scanRequestBody = [];
        $scanRequestBody['bookPart'] = $bookPart;
        $scanRequestBody['bookPartLabel'] = $bookPart;
        $scanRequestBody['file'] = new DataPart(
            $imageFile->getContent(),
            $imageFile->getFilename(),
            $imageFile->getMimeType(),
        );

        $formData = new FormDataPart($scanRequestBody);

        $aiScanResponse = $this->http->request(
            'POST',
            'http://bd_project_ai_service:8000/ai/scan',
            [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body' => $formData->bodyToIterable()
            ]
        );
        $content = $aiScanResponse->getContent();
        $this->logger->debug("Info received Ai Scan Response $content");
        return $content;
    }
}
