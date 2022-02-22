<?php

namespace App\Entity;

use App\Repository\MovieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
class Movie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $titre;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $tmdb_id;

    #[ORM\Column(type: 'boolean')]
    private $watched;

    #[ORM\Column(type: 'string', length: 255, nullable:true)]
    private $original_title;

    #[ORM\ManyToOne(targetEntity: Director::class, inversedBy: 'movies')]
    private $director;

    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'movies')]
    private $genre;

    #[ORM\ManyToMany(targetEntity: Country::class, inversedBy: 'movies')]
    private $countries;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private $release_date;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $imagePathRelative;

    #[ORM\Column(type: 'string', length: 1000, nullable: true)]
    private $overview;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $imagePathSmall;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $imagePathMedium;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $ImagePathFull;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $runtime;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $backdrop;

    public function __construct()
    {
        $this->genre = new ArrayCollection();
        $this->countries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getTmdbId(): ?int
    {
        return $this->tmdb_id;
    }

    public function setTmdbId(?int $tmdb_id): self
    {
        $this->tmdb_id = $tmdb_id;

        return $this;
    }

    public function getVu(): ?bool
    {
        return $this->watched;
    }

    public function setVu(bool $watched): self
    {
        $this->watched = $watched;

        return $this;
    }

    public function getOriginalTitle(): ?string
    {
        return $this->original_title;
    }

    public function setOriginalTitle(string $original_title): self
    {
        $this->original_title = $original_title;

        return $this;
    }

    public function getDirector(): ?Director
    {
        return $this->director;
    }

    public function setDirector(?Director $director): self
    {
        $this->director = $director;

        return $this;
    }

    /**
     * @return Collection|Genre[]
     */
    public function getGenre(): Collection
    {
        return $this->genre;
    }

    public function addGenre(Genre $genre): self
    {
        if (!$this->genre->contains($genre)) {
            $this->genre[] = $genre;
        }

        return $this;
    }

    public function removeGenre(Genre $genre): self
    {
        $this->genre->removeElement($genre);

        return $this;
    }

    /**
     * @return Collection|Country[]
     */
    public function getCountries(): Collection
    {
        return $this->countries;
    }

    public function addCountry(Country $country): self
    {
        if (!$this->countries->contains($country)) {
            $this->countries[] = $country;
        }

        return $this;
    }

    public function removeCountry(Country $country): self
    {
        $this->countries->removeElement($country);

        return $this;
    }

    public function getReleaseDate(): ?string
    {
        return $this->release_date;
    }

    public function setReleaseDate(?string $release_date): self
    {
        $this->release_date = $release_date;

        return $this;
    }

    public function getImagePathRelative(): ?string
    {
        return $this->imagePathRelative;
    }

    public function setImagePathRelative(?string $imagePath): self
    {
        $this->imagePathRelative = $imagePath;

        return $this;
    }

    public function getOverview(): ?string
    {
        return $this->overview;
    }

    public function setOverview(?string $overview): self
    {
        $this->overview = $overview;

        return $this;
    }

    public function getImagePathSmall(): ?string
    {
        return $this->imagePathSmall;
    }

    public function setImagePathSmall(?string $imagePathSmall): self
    {
        $this->imagePathSmall = $imagePathSmall;

        return $this;
    }

    public function getImagePathMedium(): ?string
    {
        return $this->imagePathMedium;
    }

    public function setImagePathMedium(?string $imagePathMedium): self
    {
        $this->imagePathMedium = $imagePathMedium;

        return $this;
    }

    public function getImagePathFull(): ?string
    {
        return $this->ImagePathFull;
    }

    public function setImagePathFull(?string $ImagePathFull): self
    {
        $this->ImagePathFull = $ImagePathFull;

        return $this;
    }

    public function getRuntime(): ?int
    {
        return $this->runtime;
    }

    public function setRuntime(int $runtime): self
    {
        $this->runtime = $runtime;

        return $this;
    }

    public function getBackdrop(): ?string
    {
        return $this->backdrop;
    }

    public function setBackdrop(?string $backdrop): self
    {
        $this->backdrop = $backdrop;

        return $this;
    }
}
