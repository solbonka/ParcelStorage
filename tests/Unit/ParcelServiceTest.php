<?php

namespace App\Tests\Unit;

use App\Entity\Address;
use App\Entity\Dimensions;
use App\Entity\FullName;
use App\Entity\Parcel;
use App\Entity\Recipient;
use App\Entity\Sender;
use App\Repository\ParcelRepository;
use App\Service\ParcelService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ParcelServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ParcelRepository $parcelRepository;
    private ParcelService $parcelService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->parcelRepository = $this->createMock(ParcelRepository::class);
        $this->parcelService = new ParcelService($this->entityManager, $this->parcelRepository);
    }

    public function testCreateParcel()
    {
        $data = [
            'sender' => [
                'fullName' => [
                    'firstName' => 'Solbon',
                    'lastName' => 'Ayusheevich',
                    'middleName' => 'Gomboev',
                ],
                'address' => [
                    'country' => 'Russia',
                    'city' => 'Tomsk',
                    'street' => 'Pervomayskaya',
                    'house' => '65',
                    'apartment' => '38',
                ],
                'phone' => '+1234567890',
            ],
            'recipient' => [
                'fullName' => [
                    'firstName' => 'Ivan',
                    'lastName' => 'Ivanovich',
                    'middleName' => 'Ivanov',
                ],
                'address' => [
                    'country' => 'Russia',
                    'city' => 'Tula',
                    'street' => 'Lenina',
                    'house' => '3',
                    'apartment' => '4',
                ],
                'phone' => '+9876543210',
            ],
            'dimensions' => [
                'weight' => 5,
                'length' => 10,
                'height' => 15,
                'width' => 20,
            ],
            'estimatedCost' => 100,
        ];

        $parcel = $this->parcelService->createParcel($data);

        $this->assertInstanceOf(Parcel::class, $parcel);
        $this->assertInstanceOf(Sender::class, $parcel->getSender());
        $this->assertInstanceOf(Recipient::class, $parcel->getRecipient());
        $this->assertInstanceOf(Dimensions::class, $parcel->getDimensions());
        $this->assertEquals($data['estimatedCost'], $parcel->getEstimatedCost());
    }

    public function testSearchBySenderPhone()
    {
        $searchType = 'sender_phone';
        $q = '+852258741';

        $results = [
            new Parcel(
                new Sender(
                    new FullName('Bair', 'Tsydenovich', 'Soktoev'),
                    '+852258741',
                    new Address('Russia', 'Ulan-Ude', 'Borsoeva', '33', '42')
                ),
                new Recipient(
                    new FullName('Lev', 'Vladimirovich', 'Matveev'),
                    '+8524458741',
                    new Address('Russia', 'Irkutsk', 'Lesnaya', '46', '2')
                ),
                new Dimensions(4, 4, 4, 4),
                4
            ),
            new Parcel(
                new Sender(
                    new FullName('Bair', 'Tsydenovich', 'Soktoev'),
                    '+852258741',
                    new Address('Russia', 'Ulan-Ude', 'Borsoeva', '33', '42')
                ),
                new Recipient(
                    new FullName('Temuujin', 'Esugein', 'Borjigin'),
                    '+7777777',
                    new Address('Mongolia', 'Karakorum', 'Ordon', '1', '1')
                ),
                new Dimensions(5, 5, 5, 5),
                5
            ),
        ];
        $results[0]->setId('321');
        $results[1]->setId('322');

        $this->parcelRepository->expects($this->once())->method('findBySenderPhone')->with($q)->willReturn($results);

        $parcels = $this->parcelService->search($searchType, $q);

        $this->assertCount(2, $parcels);
    }

    public function testSearchByRecipientName()
    {
        $searchType = 'recipient_name';
        $q = 'Gaius Iulius Caesar';

        $results = [
            new Parcel(
                new Sender(
                    new FullName('Bulat', 'Nimaevich', 'Damdinov'),
                    '+752258741',
                    new Address('Russia', 'Chita', 'Gornaya', '53', '42')
                ),
                new Recipient(
                    new FullName('Gaius', 'Iulius', 'Caesar'),
                    '+7777777',
                    new Address('Roamn Empire', 'Rome', 'Palatino Parco', 'Domus Augusti', '1')
                ),
                new Dimensions(9, 5, 9, 5),
                8
            ),
        ];
        $results[0]->setId('31');

        $this->parcelRepository->expects($this->once())->method('findByRecipientName')->with($q)->willReturn($results);
        $parcels = $this->parcelService->search($searchType, $q);

        $this->assertCount(1, $parcels);
    }

    public function testDeleteParcelNotFound()
    {
        $id = '123';

        $this->parcelRepository->expects($this->once())->method('findOneBy')->with(['id' => $id])->willReturn(null);

        $result = $this->parcelService->deleteParcel($id);

        $this->assertEquals('Посылка не найдена', $result);
    }

    public function testDeleteParcel()
    {
        $id = '123';
        $parcel = new Parcel(
            new Sender(
                new FullName('Bair', 'Tsydenovich', 'Soktoev'),
                '+752258741',
                new Address('Russia', 'Ulan-Ude', 'Borsoeva', '33', '42')
            ),
            new Recipient(
                new FullName('Temuujin', 'Esugein', 'Borjigin'),
                '+7777777',
                new Address('Mongolia', 'Karakorum', 'Ordon', '1', '1')
            ),
            new Dimensions(5, 5, 5, 5),
            5
        );
        $parcel->setId($id);

        $this->parcelRepository->expects($this->once())->method('findOneBy')->with(['id' => $id])->willReturn($parcel);

        $this->entityManager->expects($this->once())->method('remove')->with($parcel);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->parcelService->deleteParcel($id);

        $this->assertEquals("Посылка №$id удалена", $result);
    }
}
