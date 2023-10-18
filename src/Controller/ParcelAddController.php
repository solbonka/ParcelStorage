<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ParcelDto;
use App\Service\ParcelService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;

class ParcelAddController extends AbstractController
{
    private ParcelService $parcelService;

    public function __construct(ParcelService $parcelService)
    {
        $this->parcelService = $parcelService;
    }

    #[OA\Tag(name: 'Parcel')]
    #[OA\Response(
        response: 201,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ParcelDto::class, groups: ['full'])),
        )
    )]
    #[OA\RequestBody(
        description: 'Data packet for Test',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'sender',
                    properties: [
                        new OA\Property(property: 'fullName',
                            properties: [
                                new OA\Property(property: 'firstName', type: 'string'),
                                new OA\Property(property: 'lastName', type: 'string'),
                                new OA\Property(property: 'middleName', type: 'string'),
                            ]
                        ),
                        new OA\Property(property: 'phone', type: 'string'),
                        new OA\Property(property: 'address',
                            properties: [
                                new OA\Property(property: 'country', type: 'string'),
                                new OA\Property(property: 'city', type: 'string'),
                                new OA\Property(property: 'street', type: 'string'),
                                new OA\Property(property: 'house', type: 'string'),
                                new OA\Property(property: 'apartment', type: 'string'),
                            ]
                        ),
                    ]
                ),
                new OA\Property(property: 'recipient',
                    properties: [
                        new OA\Property(property: 'fullName',
                            properties: [
                                new OA\Property(property: 'firstName', type: 'string'),
                                new OA\Property(property: 'lastName', type: 'string'),
                                new OA\Property(property: 'middleName', type: 'string'),
                            ]
                        ),
                        new OA\Property(property: 'phone', type: 'string'),
                        new OA\Property(property: 'address',
                            properties: [
                                new OA\Property(property: 'country', type: 'string'),
                                new OA\Property(property: 'city', type: 'string'),
                                new OA\Property(property: 'street', type: 'string'),
                                new OA\Property(property: 'house', type: 'string'),
                                new OA\Property(property: 'apartment', type: 'string'),
                            ]
                        ),
                    ]
                ),
                new OA\Property(property: 'dimensions',
                    properties: [
                        new OA\Property(property: 'weight', type: 'number'),
                        new OA\Property(property: 'length', type: 'number'),
                        new OA\Property(property: 'height', type: 'number'),
                        new OA\Property(property: 'width', type: 'number'),
                    ]
                ),
                new OA\Property(property: 'estimatedCost', type: 'number'),
            ]
        ),
    )]
    #[Route('/api/parcel', name: 'api_parcel_add', methods: 'POST')]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $errors = $this->validateData($data);
        if ($errors) {
            return $this->json(['errors' => $errors], 400);
        }
        $parcel = $this->parcelService->createParcel($data);

        return $this->json([$parcel->toArray()], 201, headers: ['Content-Type' => 'application/json;charset=UTF-8']);
    }

    private function validateData(?array $data): array
    {
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'sender' => $this->getPersonConstraints(),
            'recipient' => $this->getPersonConstraints(),
            'dimensions' => [
                new Assert\Collection([
                    'weight' => [
                        new Assert\NotBlank(),
                        new Assert\NotNull(),
                        new Assert\Type('integer'),
                    ],
                    'length' => [
                        new Assert\NotBlank(),
                        new Assert\NotNull(),
                        new Assert\Type('integer'),
                    ],
                    'height' => [
                        new Assert\NotBlank(),
                        new Assert\NotNull(),
                        new Assert\Type('integer'),
                    ],
                    'width' => [
                        new Assert\NotBlank(),
                        new Assert\NotNull(),
                        new Assert\Type('integer'),
                    ],
                ]),
            ],
            'estimatedCost' => [
                new Assert\NotBlank(),
                new Assert\NotNull(),
                new Assert\Type('integer'),
            ],
        ]);
        if (empty($data)) {
            return ['error' => 'Empty data'];
        }
        $violations = $validator->validate($data, $constraints);
        $errors = [];
        /** @var $violation ConstraintViolationInterface */
        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $errors[$propertyPath] = $violation->getMessage();
        }

        return $errors;
    }

    public function getPersonConstraints(): array
    {
        return [
            new Assert\Collection([
                'fullName' => new Assert\Collection([
                    'firstName' => [
                        new Assert\NotBlank(),
                        new Assert\NotNull(),
                        new Assert\Type('string'),
                    ],
                    'lastName' => [
                        new Assert\NotBlank(),
                        new Assert\NotNull(),
                        new Assert\Type('string'),
                    ],
                    'middleName' => [
                        new Assert\NotBlank(),
                        new Assert\NotNull(),
                        new Assert\Type('string'),
                    ],
                ]),
                'phone' => [
                    new Assert\NotBlank(),
                    new Assert\NotNull(),
                    new Assert\Type('string'),
                ],
                'address' => new Assert\Collection([
                    'country' => [
                        new Assert\NotBlank(),
                        new Assert\NotNull(),
                        new Assert\Type('string'),
                    ],
                    'city' => [
                        new Assert\NotBlank(),
                        new Assert\NotNull(),
                        new Assert\Type('string'),
                    ],
                    'street' => [
                        new Assert\NotBlank(),
                        new Assert\NotNull(),
                        new Assert\Type('string'),
                    ],
                    'house' => [
                        new Assert\NotBlank(),
                        new Assert\NotNull(),
                        new Assert\Type('string'),
                    ],
                    'apartment' => [
                        new Assert\NotBlank(),
                        new Assert\NotNull(),
                        new Assert\Type('string'),
                    ],
                ]),
            ]),
        ];
    }
}
