<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Adherent;
use AppBundle\Entity\ConsularEvent;
use AppBundle\Entity\Event;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ConsularEventRepository extends EventRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ConsularEvent::class);
    }

    public function countEventForOrganizer(Adherent $organizer): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(1)')
            ->where('e.status = :status')
            ->andWhere('e.organizer = :organizer')
            ->setParameter('organizer', $organizer)
            ->setParameter('status', Event::STATUS_SCHEDULED)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function getAllCategories(): array
    {
        $results = $this->createQueryBuilder('event')
            ->select('DISTINCT category.name')
            ->innerJoin('event.category', 'category')
            ->where('event.status = :scheduled')
            ->andWhere('event.finishAt > :now')
            ->setParameter('scheduled', Event::STATUS_SCHEDULED)
            ->setParameter('now', new \DateTime())
            ->orderBy('category.name', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        return \array_column($results, 'name');
    }

    public function findCategoriesForPostalCode(array $postalCodes): array
    {
        $results = $this->createQueryBuilder('event')
            ->select('DISTINCT category.name')
            ->innerJoin('event.category', 'category')
            ->where('event.status = :scheduled')
            ->andWhere('event.finishAt > :now')
            ->andWhere('event.postAddress.postalCode IN (:codes)')
            ->setParameters([
                'scheduled' => Event::STATUS_SCHEDULED,
                'now' => new \DateTime(),
                'codes' => $postalCodes,
            ])
            ->orderBy('category.name', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        return \array_column($results, 'name');
    }
}