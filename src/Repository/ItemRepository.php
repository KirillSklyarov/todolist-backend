<?php

namespace App\Repository;

use App\Entity\Item;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Item|null find($id, $lockMode = null, $lockVersion = null)
 * @method Item|null findOneBy(array $criteria, array $orderBy = null)
 * @method Item[]    findAll()
 * @method Item[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Item::class);
    }

    /**
     * @param Item $item
     * @throws \Exception
     */
    public function create(Item $item)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            $em->persist($item);
            $em->flush($item);
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();

            throw $e;
        }
    }

    /**
     * @param Item $item
     * @throws \Exception
     */
    public function delete(Item $item)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            $em->remove($item);
            $em->flush($item);
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();

            throw $e;
        }
    }


    /**
     * @param User $user
     * @param \DateTime $date
     * @return int
     * @throws NonUniqueResultException
     */
    public function getCount(User $user, \DateTime $date): int
    {
        $qb = $this->createQueryBuilder('item');
        $qb->select('count(item.id)')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('item.date', ':date'),
                $qb->expr()->eq('item.user', ':user')
            ))
            ->setParameters([
                'user' => $user,
                'date' => $date
            ]);
        $query = $qb->getQuery();
        $count = $query->getSingleScalarResult();

        return $count;
    }

    /**
     * @param User $user
     * @param \DateTime $date
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function getLastPosition(User $user, \DateTime $date)
    {
        $qb = $this->createQueryBuilder('item');
        $qb->select('item.position')
            ->add('where', $qb->expr()->andX(
                $qb->expr()->eq('item.user', ':user'),
                $qb->expr()->eq('item.date', ':date')
            ))
            ->setParameters([
                'user' => $user,
                'date' => $date
            ])
            ->orderBy('item.position', 'desc')
            ->setMaxResults(1);
        $query = $qb->getQuery();

        $result = $query->getOneOrNullResult();


        return \is_array($result) ? $result['position'] : $result;

    }
    // /**
    //  * @return Item[] Returns an array of Item objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Item
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
