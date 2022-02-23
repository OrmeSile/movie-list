<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Repository\MovieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\ApiData\ApiFetcher;
use App\Entity\Director;
use App\Form\WatchedToggleForm;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

#[Route('/movies', name: 'movie')]
class MovieController extends AbstractController
{
    private $api;

    public function __construct(ApiFetcher $api){
        $this->api = $api;
    }

    #[Route('/{tmdb_id}', name: '_show', requirements: ['tmdb_id' => '\d+'])]
    public function show(Movie $movie, ManagerRegistry $doctrine, Request $request): Response
    {
        $query = $request->query->all();
        if(!empty($query['query'])){
            return $this->redirectToRoute('searchQuery', ['query' => $query['query']], 307);
        }
        $em = $doctrine->getManager();
        
        $form = $this->createForm(WatchedToggleForm::class);
        $form->handleRequest($request);
        if ($form->isSubmitted()){
            $movie->setVu(!$movie->getVu());
            $em->flush();
            return $this->redirect($request->getUri());
        }
        $removeForm = $this->createFormBuilder()
                            ->add('remove', SubmitType::class)
                            ->getForm();
        $removeForm->handleRequest($request);
        if($removeForm->isSubmitted()){
            $em->remove($movie);
            $em->flush();
            return $this->redirectToRoute('movie_show_api', ['tmdb_id'=>$movie->getTmdbId()]);
        }

        return $this->renderForm('/movie/tmdb_id/tmdb_id.html.twig',[
            'movie' => $movie,
            'form' => $form,
            'remove_form' => $removeForm,
            ]);
    }

    #[Route('/api/{tmdb_id}/', name: '_show_api', requirements: ['tmdb_id' => '\d+'])]
    public function showDistant(ManagerRegistry $doctrine, Request $request): Response
    {
        $query = $request->query->all();
        if(!empty($query['query'])){
            return $this->redirectToRoute('searchQuery', ['query' => $query['query']], 307);
        }
        $id = explode("/",$request->getPathInfo())[3];
        $em = $doctrine->getManager();
        $movie = $em->getRepository(Movie::class)->findOneBy(['tmdb_id' => $id]);

        if ($movie){
            return $this->redirectToRoute('movie_show',['tmdb_id' => $id]);
        }

        $movieInfo = $this->api->getMovieInfoWithId($id);
        $movie = $this->api->buildMovie($movieInfo);

        $form=$this->createFormBuilder()
                    ->add('add', SubmitType::class)
                    ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $director = $em->getRepository(Director::class)->findOneBy(['tmdbDirectorId' => $movie->getDirector()->getTmdbDirectorId()]);
            if (!$director){
                $em->persist($movie->getDirector());
            }
            $em->persist($movie);
            $em->flush();
            return $this->redirectToRoute('movie_show', ['tmdb_id' => $id]);
        }

        return $this->renderForm('/movie/tmdb_id/distant/api_tmdb_id.html.twig',[
            'movie' => $movie,
            'form' => $form,
            ]);
    }

    #[Route('/populate/titles')]
    public function populate(ManagerRegistry $doctrine){
        $em = $doctrine->getManager();
        $movies = $em->getRepository(Movie::class)->findAll();
        foreach($movies as $movie){
            $movieInfo = $this->api->getMovieInfoWithId($movie->getTmdbId());
            $movie->setTitre($movieInfo->title);
            dump($movieInfo->title);
        }
        $em->flush();
    }
   
    #[Route('/populate/director-bio')]
    public function populateBackdrop(ManagerRegistry $doctrine){
        $em = $doctrine->getManager();
        $directors = $em->getRepository(Director::class)->findAll();
        foreach($directors as $director){
            $director->setBiography($this->api->getPersonFields($director->getTmdbDirectorId())['biography']);
        }
        $em->flush();
    }
}