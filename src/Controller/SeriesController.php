<?php

namespace App\Controller;

use App\Entity\Genre;
use App\Entity\Country;
use App\Repository\SeriesRepository;
use App\Repository\SeasonRepository;
use App\Repository\RatingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/series")
 */
class SeriesController extends AbstractController
{
    /**
     * @Route("/show/{serieId}", name="serie_show", methods={"GET"})
     */
    public function show(SeriesRepository $sr, RatingRepository $rr, int $serieId): Response
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $serie = $sr->findAllSeasonsAndEpisodesById($serieId)[0];
        $nbComment = $rr->findByCountOfUserCommentsPerSerie($user, $serieId);
        if ($nbComment <= 0) $canComment = true;
        else {
            $lastPostDateWeek = (int)date('W', $rr->findAll_N_LastsCommentsByUser($user, 1)[0]->getDate()->getTimestamp());
            $currentWeek = (int)date('W', time());
            $canComment = ($nbComment <= 4 and $lastPostDateWeek != $currentWeek);
        }
        return $this->render('series/show.html.twig', [
            'serie' => $serie,
            'canComment' => $canComment
        ]);
    }


    /**
     * @Route("/{serieId}/{seasonId}/episode", name="episodes")
     */
    public function episode(int $serieId, int $seasonId, SeasonRepository $seaR, SeriesRepository $sr): Response
    {
        $serie = $sr->findOneBy([
            'id' => $serieId,
        ]);
        $season = $seaR->findByIdHydrate(
            $seasonId
        )[0];

        return $this->render('series/episode.html.twig', [
            'serie' => $serie,
            'season' => $season,
            'episodes' => $season->getEpisodes()
        ]);
    }

    /**
     * 
     * @Route("/{case}/{value}/{page}/{condition}",  name="series_list")
     */
    public function listSeries(Request $r, SeriesRepository $sr, int $page = 1, String $condition = "", String $case = "title", String $value = "ASC"): Response
    {
        $em = $this->getDoctrine()->getManager();
        $gr = $em->getRepository(Genre::class);
        $cr = $em->getRepository(Country::class);

        if ($condition == "" || $r->request->get('title') != "") {
            $condition = $r->request->get('title');
            if ($condition == null) $condition = "";
        }
        $limit = 10;

        [$series, $total] = $this->listSeriesCase($condition, $page, $limit, $case, $value, $sr);

        $maxPage = ceil($total / $limit);

        $countries = $cr->findAll();
        $genres = $gr->findAll();

        return $this->render('series/list_series.html.twig', [
            'series' => $series,
            'countries' => $countries,
            'genres' => $genres,
            'page' => $page,
            'limit' => $limit,
            'max' => $maxPage,
            'condition' => $condition,
            'total' => $total,
            'case' => $case,
            'value' => $value
        ]);
    }

    public function listSeriesCase(String $condition, int $page, int $limit, String $case, String $value, SeriesRepository $sr)
    {
        switch ($case) {
            case "title": //by name ASC & DESC
                $series = $sr->findByCondition($condition, $page, $limit, $case, $value);
                $total = $sr->findByCountOfSeriesWithCondition($condition);
                break;
            case "yearStart": //by start year ASC & DESC
                $series = $sr->findByCondition($condition, $page, $limit, $case, $value);
                $total = $sr->findByCountOfSeriesWithCondition($condition);
                break;
            case "rating": //by website rating ASC & DESC
                $series = $sr->findAllWithRating();
                if ($value == "ASC") usort($series, array($this, "cmpASC"));
                else usort($series, array($this, "cmpDESC"));
                $total = $sr->findByOfSeriesCountWithRating();
                break;
            case "country": //by country ASC
                $series = $sr->findBySeriesWithConditionByCountryOrGenre($condition, $page, $limit, $case, $value);
                $total = $sr->findByCountOfSeriesWithConditionByCountryOrGenre($condition, $case, $value);
                break;
            case "genre": //by genre ASC
                $series = $sr->findBySeriesWithConditionByCountryOrGenre($condition, $page, $limit, $case, $value);
                $total = $sr->findByCountOfSeriesWithConditionByCountryOrGenre($condition, $case, $value);
                break;
            case "actual": //by not finished series, order by name ASC
                $series = $sr->findByCurrentSeries($condition, $page, $limit, $value);
                $total = $sr->findByCountOfCurrentSeries($condition, $case, $value);
                break;
            default:
                $series = null;
                $total = 0;
                break;
        }

        return ([$series, $total]);
    }

    public function cmpASC($a,  $b)
    {
        if ($a->getRating() == $b->getRating()) {
            return 0;
        }
        return ($a->getRating() > $b->getRating()) ? -1 : 1;
    }
    public function cmpDESC($a,  $b)
    {
        if ($a->getRating() == $b->getRating()) {
            return 0;
        }
        return ($a->getRating() < $b->getRating()) ? -1 : 1;
    }
}
