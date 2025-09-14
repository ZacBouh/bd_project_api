<?php

namespace App\Service;

use App\DTO\Scan\ScanDTOFactory;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
     * @return array<mixed>
     */
    public function scanComicPicture(InputBag $inputBag, FileBag $files): array
    {
        if ($files->count() === 0) {
            $message = 'Missing Picture to scan';
            throw new InvalidArgumentException($message);
        }
        if ($files->count() > 1) {
            $message = 'Cannot scan more than one picture';
            throw new InvalidArgumentException($message);
        }
        // $fileKey = $files->keys()[0];
        // $inputs = $inputBag->all();
        $writeDto = $this->dtofactory->writeDtoFromInputBag($inputBag, $files);
        $imageFile = $writeDto->imageFile;
        $bookPart = $writeDto->bookPart->value;
        $bookPartLabel = $writeDto->bookPartLabel;
        $this->logger->debug(sprintf('Scan Request input: %s %s %s', $bookPartLabel, $bookPart, $imageFile->getPath()));

        $scanRequestBody = [];
        $scanRequestBody['bookPart'] = $writeDto->bookPart->value;
        $scanRequestBody['bookPartLabel'] = $writeDto->bookPartLabel;
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
        $content = $aiScanResponse->toArray();
        $this->logger->debug('Info received Ai Scan Response' . json_encode($content));
        return ['response' => $content];
    }
}
