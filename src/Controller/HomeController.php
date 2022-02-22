<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\ApiData\ApiFetcher;
use App\Form\FilterForm;
use App\Data\MovieData;
use App\Entity\Movie;
use App\Repository\MovieRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
class HomeController extends AbstractController
{
    private $api;

    public function __construct(ApiFetcher $api)
    {
        $this->api = $api;
    }

    #[Route('/home', name: 'home')]
    public function index(MovieRepository $movieRepository, Request $request): Response
    {   
        $query = $request->query->all();
        if(!empty($query['query'])){
            return $this->redirectToRoute('searchQuery', ['query' => $query['query']], 307);
        }
        $data = new MovieData();
        $form = $this->createForm(FilterForm::class, $data);
        $form->handleRequest($request);
        $movies = $movieRepository->findFiltered($data);

        return $this->renderForm('home/index.html.twig',[
            'movies'=>$movies,
            'form' => $form,
        ])
        ;
    }
}
