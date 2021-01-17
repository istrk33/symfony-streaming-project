<?php

namespace App\Controller;

use App\Entity\Actor;
use App\Entity\Country;
use App\Entity\Episode;
use App\Entity\Genre;
use App\Entity\Season;
use App\Entity\Series;
use App\Repository\SeriesRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin/addOmdbSerie/{message}", name="add_omdb_series")
     */
    public function index(Request $r, string $message = "Nothing to find"): Response
    {
        if ($r->request->get('title') != null && $r->request->get('imdb') == null) {
            $toSearch = $this->cleanString($r->request->get('title'));
            $isTitle = true;
        } else {
            $toSearch = $this->cleanString($r->request->get('imdb'));
            $isTitle = false;
        }
        $data = null;
        if ($r->getMethod() == 'POST' && $toSearch != "") {
            if ($isTitle) {
                $toSearch = 'http://www.omdbapi.com/?apikey='.$_ENV['OMDB_API_KEY'].'&type=series&plot=full&t=' . $toSearch;
            } else {
                $toSearch = 'http://www.omdbapi.com/?apikey='.$_ENV['OMDB_API_KEY'].'&type=series&plot=full&i=' . $toSearch;
            }
            $curl = curl_init($toSearch);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $data = curl_exec($curl);
            if (!$data) {
                dump(curl_error($curl));
            } else {
                $data = json_decode($data, true);
            }
            curl_close($curl);
        }
        if ($data == null || $data["Response"] == "False") {
            return $this->render('admin/index.html.twig', [
                'searchData' => 'Nothing to found',
                'message' => $message
            ]);
        } else {
            $session = $r->getSession();
            $session->set('currentSerie', $data);
            $message = "";
            return $this->render('admin/index.html.twig', [
                'searchData' => $data,
                'message' => $message
            ]);
        }
    }

    /**
     * @Route("/admin/addOmdbSerieAction", name="add_omdb")
     */
    public function addNewSerie(Request $r, SeriesRepository $sr)
    { 
        $entityManager = $this->getDoctrine()->getManager();

        $ar = $entityManager->getRepository(Actor::class);
        $gr = $entityManager->getRepository(Genre::class);
        $cr = $entityManager->getRepository(Country::class);

        $session = $r->getSession();
        $serieToDatabase = new Series();
        $serie = $session->get('currentSerie');
        //if the serie already exist
        if ($sr->findOneBy(['imdb' => $serie['imdbID']]) == null) {
            $serieToDatabase->setTitle($serie['Title']);
            $serieToDatabase->setPlot($serie['Plot']);
            $serieToDatabase->setImdb($serie['imdbID']);
            if ($serie['Poster'] != "N/A") {
                $serieToDatabase->setPoster(fopen($serie['Poster'], 'rb'));
            }
            $serieToDatabase->setDirector($serie['Director']);
            $serieToDatabase->setAwards($serie['Awards']);
            $serieToDatabase->setYoutubeTrailer("https://www.youtube.com/watch?v=" . $this->getVideo($serie['Title']));
            if (str_contains($serie['Year'], '–')) {
                $years = explode('–', $serie['Year']);
                $serieToDatabase->setYearStart((int)trim($years[0]));
                ((int)trim($years[1]) == null) ? $serieToDatabase->setYearEnd(null) : $serieToDatabase->setYearEnd((int)trim($years[1]));
            } else {
                $serieToDatabase->setYearStart((int)trim($serie['Year']));
            }
            //add Genre
            $genres = explode(',', $serie['Genre']);
            foreach ($genres as $genre) {
                $objectGenre = $gr->findOneBy([
                    'name' => trim($genre),
                ]);
                if ($objectGenre == null) {
                    $objectGenre = new Genre();
                    $objectGenre->setName($genre);
                    $entityManager->persist($objectGenre);
                    $entityManager->flush();
                }
                $serieToDatabase->addGenre($objectGenre);
            }
            //Add Actor
            $actors = explode(',', $serie['Actors']);
            foreach ($actors as $actor) {
                $objectActor = $ar->findOneBy([
                    'name' => trim($actor),
                ]);
                if ($objectActor == null) {
                    $objectActor = new Actor();
                    $objectActor->setName($actor);
                    $entityManager->persist($objectActor);
                    $entityManager->flush();
                }
                $serieToDatabase->addActor($objectActor);
            }
            //add Country
            $countries = explode(',', $serie['Country']);
            foreach ($countries as $country) {
                $objectCountry = $cr->findOneBy([
                    'name' => trim($country),
                ]);
                if ($objectCountry == null) {
                    $objectCountry = new Country();
                    $objectCountry->setName($country);
                    $entityManager->persist($objectCountry);
                    $entityManager->flush();
                }
                $serieToDatabase->addCountry($objectCountry);
            }
            $entityManager->persist($serieToDatabase);
            $entityManager->flush();

            //ADD SEASONS AND EPISODES
            $nbSeasons = (int)$serie['totalSeasons'];
            for ($seasonNumber = 1; $seasonNumber <= $nbSeasons; $seasonNumber++) {
                $toSearch = 'http://www.omdbapi.com/?apikey='.$_ENV['OMDB_API_KEY'].'&plot=full&t=' . $this->cleanString($serieToDatabase->getTitle()) . "&Season=" . $seasonNumber;
                $curl = curl_init($toSearch);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $data = curl_exec($curl);
                if (!$data) {
                    dump(curl_error($curl));
                } else {
                    $data = json_decode($data, true);
                }
                curl_close($curl);
                $seasonToDatabase = new Season();
                $seasonToDatabase->setNumber($seasonNumber);
                $seasonToDatabase->setSeries($serieToDatabase);
                $entityManager->persist($seasonToDatabase);
                $entityManager->flush();
                if (isset($data['Episodes'])) {
                    foreach ($data['Episodes'] as $episode) {
                        $episodeToDatabase = new Episode();
                        $episodeToDatabase->setTitle($episode['Title']);
                        $episodeToDatabase->setSeason($seasonToDatabase);
                        $time = $episode['Released'];
                        $episodeToDatabase->setDate(new DateTime(date("Y-m-d", strtotime($time))));
                        $episodeToDatabase->setImdb($episode['imdbID']);
                        $episodeToDatabase->setImdbrating((float)trim($episode['imdbRating']));
                        $episodeToDatabase->setNumber((int)trim($episode['Episode']));
                        $entityManager->persist($episodeToDatabase);
                        $entityManager->flush();
                    }
                }
            }
            $this->addFlash('success', 'New serie added !');
            $message = "success";
        } else {
            $message = "failed";
        }
        //close session
        $session->clear();
        return $this->redirectToRoute('add_omdb_series', ['message' => $message]);
    }

    function cleanString($string)
    {
        $string = trim($string);
        $string = str_replace(' ', '+', $string);
        return $string;
    }

    function getVideo($yt_search)
    {
        $yt_source = file_get_contents('https://www.googleapis.com/youtube/v3/search?part=snippet&maxResults=1&order=relevance&q=' . $this->cleanString($yt_search) . '+serie+trailer&key=' .$_ENV['YOUTUBE_API_KEY']);
        $yt_decode = json_decode($yt_source, true);
        return $yt_decode['items'][0]['id']['videoId'];
    }
}
