<?php

namespace App\Controller;

use App\Data\SearchData;
use App\Form\MovieSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\ApiData\ApiFetcher;
use App\Repository\DirectorRepository;
use App\Repository\MovieRepository;

class SearchController extends AbstractController
{
    private $api;

    public function __construct(ApiFetcher $api)
    {
        $this->api = $api;
    }

    #[Route('/search_index', name: 'search')]
    public function index(Request $request): Response
    {
        $data = new SearchData;
        $form = $this->createForm(MovieSearchType::class, $data);
        return $this->renderForm('snippets/_search_field.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/search', name: 'searchQuery')]
    public function search(Request $request, MovieRepository $movieRepo, DirectorRepository $directorRepo): Response
    {
        $query = $request->query->get('query');
        $movies = $movieRepo->partialSearch($query);
        $apiMovies = $this->api->searchMovies($query);
        $directors = $directorRepo->search($query);
        
        return $this->render('search/index.html.twig',[
            'movies'=>$movies,
            'apiMovies'=>$apiMovies,
            'directors'=>$directors,
        ]);
    }
}
