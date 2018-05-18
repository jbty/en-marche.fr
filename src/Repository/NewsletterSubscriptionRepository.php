<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Adherent;
use AppBundle\Entity\NewsletterSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class NewsletterSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsletterSubscription::class);
    }

    /**
     * Finds the list of newsletter subscribers managed by the given referent.
     *
     * @param Adherent $referent
     *
     * @return NewsletterSubscription[]
     */
    public function findAllManagedBy(Adherent $referent)
    {
        if (!$referent->isReferent()) {
            return [];
        }

        $hasFranceManagedArea = false;
        foreach ($referent->getManagedArea()->getTags() as $tag) {
            if (is_numeric($tag->getCode())) {
                $hasFranceManagedArea = true;
                break;
            }
        }

        if (!$hasFranceManagedArea) {
            return [];
        }

        $qb = $this->createQueryBuilder('n')
            ->select('n')
            ->orderBy('n.createdAt', 'DESC')
            ->where('n.email != :self')
            ->setParameter('self', $referent->getEmailAddress())
            ->andWhere('LENGTH(n.postalCode) = 5')
        ;

        $codesFilter = $qb->expr()->orX();

        foreach ($referent->getManagedArea()->getTags() as $key => $tag) {
            if (is_numeric($code = $tag->getCode())) {
                // Postal code prefix
                $codesFilter->add($qb->expr()->like('n.postalCode', ":code_$key"));
                $qb->setParameter("code_$key", "$code%");
            }
        }

        $qb->andWhere($codesFilter);

        return $qb->getQuery()->getResult();
    }

    public function isSubscribed(string $email): bool
    {
        return (bool) $this
            ->createQueryBuilder('newsletter')
            ->select('COUNT(newsletter)')
            ->where('newsletter.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
