<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ParcelService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class ParcelDeleteController extends AbstractController
{
    private ParcelService $parcelService;

    public function __construct(ParcelService $parcelService)
    {
        $this->parcelService = $parcelService;
    }
    #[OA\Tag(name: 'Parcel')]
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            properties: [new OA\Property(property: "message", type: "string"),],
        )
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Это поле используется для удаления посылки из базы данных по заданному значению id.',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[Route('/api/parcel', name: 'app_parcel_delete', methods: 'DELETE')]
    public function __invoke(Request $request): JsonResponse
    {
        $validationError = $this->validateRequest($request);
        if ($validationError) {
            return $this->json(['errors' => $validationError], 400);
        }
        $id = $request->query->get('id');
        $message = $this->parcelService->deleteParcel($id);

        return $this->json([
            'message' => $message,
        ]);
    }

    private function validateRequest(Request $request): array
    {
        $id = $request->query->get('id');
        $validator = Validation::createValidator();
        $constraint = new Assert\Collection([
            'id' => [
                new Assert\NotNull(),
                new Assert\NotBlank(),
            ],
        ]);
        $violations = $validator->validate(['id' => $id], $constraint);
        if (count($violations) > 0) {
            $firstViolation = $violations[0];
            return [$firstViolation->getPropertyPath() => $firstViolation->getMessage()];
        }

        return [];
    }
}
