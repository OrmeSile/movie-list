<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Director;
use Symfony\Component\HttpFoundation\Request;

#[Route('/director', name: 'director')]
class DirectorController extends AbstractController
{
    #[Route('/{tmdbDirectorId}', name: '_show', requirements: ['tmdbDirectorId' => '\d+'])]

    public function index(Request $request, Director $director): Response
    {
        $query = $request->query->all();
        if(!empty($query['query'])){
            return $this->redirectToRoute('searchQuery', ['query' => $query['query']], 307);
        }
        $movies = $director->getMovies();
        return $this->render('director/index.html.twig', [
            'director' => $director,
            'movies' => $movies,
        ]);
    }
}
