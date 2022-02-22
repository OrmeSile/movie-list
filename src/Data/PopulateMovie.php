<?php
namespace App\Data;

use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Director;
use App\Entity\Movie;
use App\ApiData\ApiFetcher;

class PopulateMovie {

    private $api;
    public function __construct(ApiFetcher $api)
    {
        $this->api = $api;
    }

    public function populateDirectorByMovie(ManagerRegistry $doctrine){
        $em= $doctrine->getManager();
        $movies = $em->getRepository(Movie::class)->findAll();
        foreach($movies as $movie){
            $directorsDB = $em->getRepository(Director::class);
            $directorId = $this->api->getDirectorIdByMovie($movie);

            }
            $em->flush();
    }
}