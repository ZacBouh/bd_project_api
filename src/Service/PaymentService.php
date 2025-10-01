<?php

namespace App\Service;

use App\Entity\Copy;
use App\Entity\User;
use App\Repository\CopyRepository;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stripe\StripeClient;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationInterface;

class PaymentService
{
    public function __construct(
        private StripeClient $stripe,
        private ValidatorInterface $validator,
        private CopyRepository $copyRepo,
        private LoggerInterface $logger,
        private Security $security
    ) {}

    /**
     * @param Copy[] $copies
     */
    public function createStripeCheckoutSession(array $copies): string
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $items = [];
        $metadata = ['user' => $user->getId(), 'copies' => []];
        foreach ($copies as $copy) {
            $items[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int) round($copy->getPrice() * 100),
                    'product_data' => [
                        'name' => $copy->getId() . "_" . $copy->getTitle()?->getName()
                    ]
                ],
                'quantity' => 1
            ];
            $metadata['copies'][] = [
                'id' => $copy->getId(),
                'name' => $copy->getTitle()?->getName() ?? 'No Name',
                'price' => $copy->getPrice(),
                'currency' => $copy->getCurrency()?->value
            ];
        }

        $checkoutSession = $this->stripe->checkout->sessions->create([
            'line_items' => $items,
            'mode' => 'payment',
            'success_url' => 'http://localhost:8082/shopping-cart?success=true',
            'cancel_url' => 'http://localhost:8082/shopping-cart?canceled=true',
            'metadata' => ['items' => json_encode($metadata)]
        ]);

        if (is_null($checkoutSession->url)) {
            throw new RuntimeException('Checkout Session url is null');
        }

        return $checkoutSession->url;
    }

    /** @return string|array<array<string, string>> */
    public function getPaymentUrl(Request $request): string|array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent(), true);
        $copyIds = $data['copies'] ?? null;
        $violations = $this->validator->validate(
            $copyIds,
            new Assert\Sequentially([
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\Type('integer')
                ])
            ])
        );
        if (count($violations) > 0) {
            $errors = [];
            /** @var ConstraintViolationInterface $violation  */
            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage()
                ];
            }
            return $errors;
        }

        /** 
         * @var Copy[] $copies 
         * @var int[] $copyIds
         * */
        $copies = $this->copyRepo->findBy(['id' => $copyIds]);
        if (count($copies) !== count($copyIds)) {
            throw new InvalidArgumentException("Invalid Copy list, none or some where not found");
        }
        $url = $this->createStripeCheckoutSession($copies);
        return $url;
    }

    public function handleStripeEvent(Request $request): void
    {
        $event = \Stripe\Event::constructFrom(json_decode($request->getContent(), true)); //@phpstan-ignore-line
        $this->logger->debug(sprintf('Received Stripe Event : %s', $request->getContent()));
        /** @var string $signatureHeader */
        $signatureHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        /** @var string $webHookSecret */
        $webHookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        $event = \Stripe\Webhook::constructEvent(
            $request->getContent(),
            $signatureHeader,
            $webHookSecret
        );

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->logger->debug(sprintf("CHECKOUT COMPLETED : %s", $event->data->object->metadata));
                break;
            case 'checkout.session.expired':
                $this->logger->debug("CHECKOUT SESSION EXPIRED");
                break;
        }
    }
}
