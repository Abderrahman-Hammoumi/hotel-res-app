<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Check if a room has overlapping reservations within a period.
     */
    public function hasOverlap(Room $room, \DateTimeInterface $start, \DateTimeInterface $end, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.room = :room')
            ->andWhere('r.CheckIn < :end')
            ->andWhere('r.CheckOut > :start')
            ->andWhere('r.status != :canceled')
            ->setParameter('room', $room)
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->setParameter('canceled', 'canceled');

        if ($excludeId !== null) {
            $qb->andWhere('r.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @return Reservation[]
     */
    public function search(?string $term): array
    {
        $qb = $this->createSearchQueryBuilder($term);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array{data: Reservation[], total: int}
     */
    public function searchPaginated(?string $term, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $qb = $this->createSearchQueryBuilder($term)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb, true);

        return [
            'data' => iterator_to_array($paginator),
            'total' => count($paginator),
        ];
    }

    private function createSearchQueryBuilder(?string $term): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.room', 'room')->addSelect('room')
            ->leftJoin('r.customer', 'customer')->addSelect('customer')
            ->orderBy('r.CheckIn', 'DESC');

        $term = trim((string) $term);

        if ($term !== '') {
            $like = '%'.mb_strtolower($term).'%';
            $qb->andWhere('LOWER(room.type) LIKE :likeTerm OR LOWER(customer.FullName) LIKE :likeTerm OR LOWER(customer.email) LIKE :likeTerm OR LOWER(r.status) LIKE :likeTerm')
               ->setParameter('likeTerm', $like);

            if (ctype_digit($term)) {
                $qb->orWhere('room.number = :roomNumber')
                   ->setParameter('roomNumber', (int) $term);
            }
        }

        return $qb;
    }

//    /**
//     * @return Reservation[] Returns an array of Reservation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Reservation
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
