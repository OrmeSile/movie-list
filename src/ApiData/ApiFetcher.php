<?php

namespace App\ApiData;

use App\Entity\Director;
use App\Entity\Movie;
use App\Repository\CountryRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\DirectorRepository;
use App\Repository\GenreRepository;

class ApiFetcher{
    private $apiConfig;
    private $directorRepository;
    private $genreRepository;
    private $countryRepository;

    public function __construct(DirectorRepository $directorRepository, GenreRepository $genreRepository, CountryRepository $countryRepository)
    {
        $this->genreRepository = $genreRepository;
        $this->directorRepository = $directorRepository;
        $this->countryRepository = $countryRepository;
        $this->apiConfig = json_decode(file_get_contents("https://api.themoviedb.org/3/configuration?api_key={$_SERVER["API_KEY"]}"));
    }

/**
 * build the full image path from a partial path.
 *
 * @param integer $widthParam width of image. 0 => 92px, 1 => 154px, 2 => 185px, 3 => 342px, 4 => 500px, 5 => 780px, 6 => original
 * @param string|null $partialPath stored in database, or contained in {movieJSON}->poster_path
 * @return null|string
 */
    public function getFullImagePath(int $widthParam, ?string $partialPath): ?string
    {
        if(!$partialPath){
            return null;
        }
        $baseUrl = $this->apiConfig->images->base_url;
        $size = $this->apiConfig->images->poster_sizes[$widthParam];
        return $baseUrl.$size.$partialPath;
    }
    /**
     * get directors partial image path
     *
     * @param string $id director id
     * @return string|null
     */
    public function getDirectorImagePath($id){
        $json = file_get_contents("https://api.themoviedb.org/3/person/{$id}/images?api_key={$_SERVER["API_KEY"]}");
        $decoded = json_decode($json);
        if(empty($decoded->profiles)){
            return null;
        }
        return $decoded->profiles[0]->file_path;
        
    }
    /**
     * Fetch general movie info from api with movie ID only
     *
     * @param string $id tmdb_ID
     * @return null|object
     */
    public function getMovieInfoWithId(string $id, string $locale = 'fr'): ?object
    {
        $result = file_get_contents("https://api.themoviedb.org/3/movie/{$id}?api_key={$_SERVER["API_KEY"]}&language={$locale}");
        return json_decode($result);
    }
    /**
     * Get movie info from full movie object
     *
     * @param Movie $movie
     * @return object
     */
    private function getMovieInfo(Movie $movie, string $locale = 'fr'): object
    {
        $movieId = $movie->getTmdbId();
        $result = file_get_contents("https://api.themoviedb.org/3/movie/$movieId?api_key={$_SERVER["API_KEY"]}&language={$locale}");
        return json_decode($result);
    }

    /**
     * get partial poster path from api movie info
     *
     * @param Movie $movie
     * @return string
     */
    public function getRelativeImagePath(Movie $movie):string
    {
        return $this->getMovieInfo($movie)->poster_path;
    }

    /**
     * get full image path and movie ID in an array where 'tmdb_id' => id, 'url' => image path
      *
     * @param integer $widthParam width of image. 0 => 92px, 1 => 154px, 2 => 185px, 3 => 342px, 4 => 500px, 5 => 780px, 6 => original
     * @param Movie $movie
     * @return array|null
     */
    public function getImagePathWithId(int $widthParam, Movie $movie): ?array
    {
        return array('tmdb_id'=>$movie->getTmdbId(), 'url' => $this->getFullImagePath($widthParam,$movie->getImagePathRelative()));
    }

    /**
     * fetch alternative title from db. Search for french title first, fallback on US title. If none found, return null
     * 
     * @param Movie $movie
     * @return string|null
     */
    private function getAlternativeTitle(Movie $movie):?string
    {
        $decode = json_decode(file_get_contents("https://api.themoviedb.org/3/movie/{$movie->getTmdbId()}/alternative_titles?api_key={$_SERVER["API_KEY"]}&country=fr"));
        $title = $decode->titles;
        if(empty($title)){
            $decode = json_decode(file_get_contents("https://api.themoviedb.org/3/movie/{$movie->getTmdbId()}/alternative_titles?api_key={$_SERVER["API_KEY"]}&country=US"));
            $title = $decode->titles;
        }
        if(empty($title[0])){
            return null;
        }
        return $title[0]->title;
    }
    /**
     * fetch original title from api
     *
     * @param Movie $movie
     * @return string|null
     */
    private function getOriginalTitle(Movie $movie):?string
    {
        $decode = $this->getMovieInfo($movie);
        return $decode->original_title;
    }
    /**
     * fetch french/fallback US and original title from api, returns array where 'fr'=>locale title, 'og'=> original title
     *
     * @param Movie $movie
     * @return array|null
     */
    public function getTitles(Movie $movie): ?array
    {
        $og = $this->getOriginalTitle($movie);
        $fr = $this->getAlternativeTitle($movie);
        dump($fr);
        if (empty($fr)){
            return ['fr'=>$og,'og'=>$og];
        }
        return ['fr'=>$fr,'og'=>$og];
    }
    /**
     * return all genres used by api
     *
     * @return object
     */
    public function getGenres():object
    {
        return json_decode(file_get_contents("https://api.themoviedb.org/3/genre/movie/list?api_key={$_SERVER['API_KEY']}&language=fr"));

    }
    /**
     * get genres of a movie
     *
     * @param Movie $movie
     * @return array|null
     */
    public function getMovieGenres(Movie $movie): ?array
    {
        $movieInfo = $this->getMovieInfo($movie);
        return $movieInfo->genres;
    }

    /**
     * get director ID of movie
     *
     * @param Movie $movie
     * @return string|null
     */
    public function getDirectorIdByMovie(Movie $movie): ?string
    {
        $movieInfo = json_decode(file_get_contents("https://api.themoviedb.org/3/movie/{$movie->getTmdbId()}/credits?api_key={$_SERVER["API_KEY"]}&language=fr"));
        $crew = $movieInfo->crew;
        foreach($crew as $person){
            if($person->job == 'Director'){
                return $person->id;
            }
        }
        return null;
    }

    /**
     * get person info where 'name'=>name , 'biography' => biography
     *
     * @param string $id
     * @return array|null
     */
    public function getPersonFields(string $id): ?array
    {
        $person = json_decode(file_get_contents("https://api.themoviedb.org/3/person/{$id}?api_key={$_SERVER["API_KEY"]}&language=fr"));
        if(!empty($person->biography)){
            return ['name' => $person->name, 'biography' => $person->biography];
        }else{
            $person = json_decode(file_get_contents("https://api.themoviedb.org/3/person/{$id}?api_key={$_SERVER["API_KEY"]}&language=en-US"));
            return ['name' => $person->name, 'biography' => $person->biography];
        }
    }

    /**
     * Returns corresponding local Director if present in db, build new Director with given ID if not found in db.
     *
     * @param string $id
     * @return Director
     */
    public function getDirectorWithId(string $id): Director
    {
        $director = $this->directorRepository->findOneBy(['tmdbDirectorId' => $id]);
        if (!$director)
        {        
            $director = new Director();
            $directorInfo = $this->getPersonFields($id);
            $director->setName($directorInfo['name']);
            $director->setBiography($directorInfo['biography']);}
            $director->setTmdbDirectorId($id);
        return $director;
    }

    /**
     * Get director from Movie, return null if no director associated
     * 
     * @param Movie $movie
     * @return Director|null
     */
    public function getDirector(Movie $movie): ?Director
    {
        $id = $this->getDirectorIdByMovie($movie)?: null;
        if(!$id){
            return $id;
        }
        return $this->getDirectorWithId($id);
    }

    public function getReleaseDate(Movie $movie): ?string
    {
        $movieInfo = json_decode(file_get_contents("https://api.themoviedb.org/3/movie/{$movie->getTmdbId()}?api_key={$_SERVER["API_KEY"]}&language=fr"));
        return $movieInfo->release_date;

    }
    /**
     * return countries used by api
     *
     * @return array
     */
    public function getCountries(): array
    {
        return json_decode(file_get_contents("https://api.themoviedb.org/3/configuration/countries?api_key={$_SERVER["API_KEY"]}"));


    }
    /**
     * get french movie overview from api, fallback on us overview
     *
     * @param Movie $movie
     * @return string|null
     */
    public function getOverView(Movie $movie): ?string
    {
        $movieInfo = json_decode(file_get_contents("https://api.themoviedb.org/3/movie/{$movie->getTmdbId()}?api_key={$_SERVER["API_KEY"]}&language=fr"));
        if(!strlen($movieInfo->overview)){
            $movieInfo = json_decode(file_get_contents("https://api.themoviedb.org/3/movie/{$movie->getTmdbId()}?api_key={$_SERVER["API_KEY"]}&language=en-US")); 
        }
        return $movieInfo->overview;
    }
    /**
     * get production countries of given Movie, returns iso country codes in an array
     *
     * @param [type] $movie
     * @return array|null
     */
    public function getProdCountries($movie): ?array
    {
        $movieInfo = $this->getMovieInfo($movie);
        $movieCountry = $movieInfo->production_countries;
        $arr = [];
        foreach($movieCountry as $country){
            array_push($arr, (string)$country->iso_3166_1);

        }
        return $arr;
    }

    public function getruntime(Movie $movie): ?int
    {
        return $this->getMovieInfo($movie)->runtime;
    }
    /**
     *  returns first 20 results of api search based on title
     *
     * @param string $query search string
     * @return array|null
     */
    public function searchMovies(string $query): ?array
    {
        $encodedQuery = urlencode($query);
        $movies = json_decode(file_get_contents("https://api.themoviedb.org/3/search/movie?api_key={$_SERVER["API_KEY"]}&language=fr&query={$encodedQuery}&page=1&include_adult=true"));
        $movieArray = $movies->results;
        $movies =[];
        foreach($movieArray as $movieObject){
            array_push($movies, $this->buildSearchMovie($movieObject));
        }
        return $movies;
    }
    public function getFallbackInfo(Movie $movie){
        return $this->getMovieInfo($movie, 'en-US');
    }

    /**
     * creates and return a full movie object based on data from search api
     *
     * @param Object $movieObject movie info from api
     * @return Movie
     */
    public function buildMovie(Object $movieObject): Movie
    {
        $movie = new Movie();
        $movie->setTmdbId($movieObject->id);
        if(!empty($movieObject->release_date)){
            $movie->setReleaseDate($movieObject->release_date);
        }
        $movie->setVu(false);
        $movie->setTitre($movieObject->title);
        $movie->setOriginalTitle($movieObject->original_title);
        if(empty($movieObject->overview)){
            $movie->setOverview($this->getFallbackInfo($movie)->overview);
        }else{
            $movie->setOverview($movieObject->overview);
        }
        $director = $this->getDirector($movie);
        $movie->setDirector($director);
        $movie->setRuntime($this->getruntime($movie));
        if(!empty($movieObject->production_countries)){
            $prodCountries = $movieObject->production_countries;
            foreach($prodCountries as $prodCountry){
                $country = $this->countryRepository->findOneBy(['iso_name' => $prodCountry->iso_3166_1]);
                $movie->addCountry($country);
            }
        }
        if(!empty($movieObject->genre_ids)){
        $genres = $movieObject->genre_ids;
            foreach($genres as $genreId){
                $genre = $this->genreRepository->findOneBy(["genre_id" => $genreId]);
                $movie->addGenre($genre);
            }
        }
        if($movieObject->poster_path){
            $movie->setImagePathRelative($movieObject->poster_path);
            $movie->setImagePathSmall($this->getFullImagePath(2,$movieObject->poster_path));
            $movie->setImagePathMedium($this->getFullImagePath(4,$movieObject->poster_path));
            $movie->setImagePathFull($this->getFullImagePath(6,$movieObject->poster_path));
        }
        if($movieObject->backdrop_path){
            $movie->setBackdrop($this->getFullImagePath(6, $movieObject->backdrop_path));
        }
        return $movie;
    }
    /**
     * Build partial movie objects for search results, less API calls
     *
     * @param Object $movieObject
     * @return Movie
     */
    public function buildSearchMovie(Object $movieObject): Movie
    {
        $movie = new Movie();
        $movie->setTmdbId($movieObject->id);
        if(!empty($movieObject->release_date)){
            $movie->setReleaseDate($movieObject->release_date);
        }
        $movie->setTitre($movieObject->title);
        if(empty($movieObject->overview)){
            $movie->setOverview($this->getFallbackInfo($movie)->overview);
        }else{
            $movie->setOverview($movieObject->overview);
        }

        if($movieObject->poster_path){
            $movie->setImagePathSmall($this->getFullImagePath(2,$movieObject->poster_path));
        }
        return $movie;
    }
}