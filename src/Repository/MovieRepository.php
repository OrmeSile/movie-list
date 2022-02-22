<?php

namespace App\Repository;

use App\Data\MovieData;
use App\Entity\Movie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Movie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Movie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Movie[]    findAll()
 * @method Movie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Movie[]    findFiltered(MovieData $movieData)
 */
class MovieRepository extends ServiceEntityRepository
{
    //TODO persist new info to db
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    public function getRandomMovies(int $number): array
    {

        $qb = $this->createQueryBuilder('movie')
                   ->select('Count(movie)');

        $totalMovies = $qb->getQuery()->getSingleScalarResult();

        if ($totalMovies < 1){
            return null;
        }
        $i = 0;
        $randToQuery = [];

        while($i < $number){
            $random = mt_rand(1, $totalMovies);
            if(in_array($random, $randToQuery)){
                continue;
            }
            array_push($randToQuery, $random);
            $i++;
        }
        $finalQuery = $randToQuery;
        $randToQuery = [];

        return $this->findBy(array('id' => $finalQuery));

    }
    /** */
    public function partialSearch(string $query)
    {
        $q = $this->
            createQueryBuilder('movie')
            ->andWhere('movie.titre LIKE :query')
            ->orWhere('movie.original_title LIKE :query')
            ->setParameter('query', "%{$query}%");

        return $q->getQuery()->getResult();
    }
    /** 
     * Recupère les résultats du filtre
     * @return Movie[]
    */
    public function findFiltered(MovieData $movieData): array
    {
        $query = $this
                ->createQueryBuilder('movie')
                ->andWhere('movie.watched = :vu')
                ->orderBy('movie.titre', 'asc')
                ->setParameter('vu', $movieData->vu)
                ;
        if(!empty($movieData->vu)){
            $query = $query
                ->andWhere('movie.watched = true');
        };

        if(!empty($movieData->genres)){
            $query = $query
                    ->andWhere('g.id IN (:genre)')
                    ->join('movie.genre', 'g')
                    ->setParameter('genre', $movieData->genres);
        }
        //dd($query->getQuery()->getDQL());
        return $query->getQuery()->getResult();
    }
    
    public function search(MovieData $movieData){
        $query = $this
                ->createQueryBuilder('movie')
                ->andWhere('movie.titre LIKE %searchParam%')
                ->setParameter('searchparam', $movieData->search);

        return $query->getQuery()->getResult();
    }
    // /**
    //  * @return Movie[] Returns an array of Movie objects
    //  */
    ///*
    // public function findByExampleField($value)
    // {
    //     return $this->createQueryBuilder('m')
    //         ->andWhere('m.exampleField = :val')
    //         ->setParameter('val', $value)
    //         ->orderBy('m.id', 'ASC')
    //         ->setMaxResults(10)
    //         ->getQuery()
    //         ->getResult()
    //     ;
    // }
   // */

    /*
    public function findOneBySomeField($value): ?Movie
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
