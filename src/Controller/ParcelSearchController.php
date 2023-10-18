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
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;

class ParcelSearchController extends AbstractController
{
    private ParcelService $parcelService;

    public function __construct(ParcelService $parcelService)
    {
        $this->parcelService = $parcelService;
    }

    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ParcelDto::class, groups: ['full']))
        )
    )]
    #[OA\Parameter(
        name: 'searchType',
        description: 'Поле используется для определения типа поиска. Допустимые значения sender_phone и receiver_fullname',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'q',
        description: 'Поле используется для поиска по заданному значению',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'Parcel')]
    #[Route('/api/parcel', name: 'app_parcel_search', methods: 'GET')]
    public function __invoke(Request $request): JsonResponse
    {
        $validationErrors = $this->validateRequest($request);
        if ($validationErrors) {
            return $this->json(['errors' => $validationErrors], 400);
        }
        $searchType = $request->query->get('searchType');
        $q = $request->query->get('q');
        $parcels = $this->parcelService->search($searchType, $q);

        return $this->json($parcels, 200, headers: ['Content-Type' => 'application/json;charset=UTF-8']);
    }

    private function validateRequest(Request $request): array
    {
        $searchType = $request->query->get('searchType');
        $q = $request->query->get('q');
        $validator = Validation::createValidator();
        $constraints = new Assert\Collection([
            'searchType' => [
                new Assert\Choice(['sender_phone', 'receiver_fullname']),
                new Assert\NotBlank(),
            ],
            'q' => new Assert\Callback([
                'callback' => function ($q, ExecutionContextInterface $context) use ($searchType) {
                    if ($searchType === 'receiver_fullname') {
                        $words = explode(' ', $q);
                        $wordCount = count($words);
                        if ($wordCount !== 3) {
                            $context->buildViolation('Please enter your fullName in the following format: firstName lastName middleName')
                                ->addViolation();
                        }
                    }
                },
            ]),
        ]);
        $violations = $validator->validate(['searchType' => $searchType, 'q' => $q], $constraints);
        $errors = [];
        foreach ($violations as $violation) {
            $property = $violation->getPropertyPath();
            $message = $violation->getMessage();
            $errors[$property] = $message;
        }

        return $errors;
    }
}
