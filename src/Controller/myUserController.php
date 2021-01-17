<?php

namespace App\Controller;

use App\Repository\SeriesRepository;
use App\Repository\RatingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/user")
 */
class myUserController extends AbstractController
{
    /**
     * @Route("/", name="menu")
     */
    public function index(SeriesRepository $sRepository, RatingRepository $rRepository): Response
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $maxResultsSeries = min(10, $user->getSeries()->count());
        $series = $sRepository->findAll_N_LastsUserSeries($user->getSeries(), "", $maxResultsSeries);

        $totalComments = $rRepository->findByCountOfUserComments($user);
        $maxResultsComments = min(10, $totalComments);
        $lastsComments = $rRepository->findAll_N_LastsCommentsByUser($user, $maxResultsComments);

        return $this->render('myUser/index.html.twig', [
            "series" => $series,
            "nbResultsSeries" => $maxResultsSeries,
            "comments" => $lastsComments,
            "nbResults" => $maxResultsComments,
            "totalNbComments" => $totalComments,
            "route" => "user_comments",
            "changeroute" => "all_comments"
        ]);
    }

    /**
     * @Route("/mySeries", name="user_series")
     */
    public function list_series(Request $r, SeriesRepository $sr): Response
    {
        $condition = $r->request->get('title');
        if ($condition == null) $condition = "";

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $nbSeries =  $user->getSeries()->count();
        return $this->render('myUser/list_series.html.twig', [
            "series" => $sr->findAll_N_LastsUserSeries($user->getSeries(), $condition, $nbSeries),
            "condition" => $condition,
            'total' => $nbSeries,
            'limit' => $nbSeries
        ]);
    }

    /**
     * @Route("/mySeries/add/{serieId}/{lastRoute}/{page}/{condition}", name="add_user_series", requirements={"serieId"="\d+","lastRoute"="[a-z_a-z]+"}, defaults={"page" = null,"condition" =null})
     */
    public function addInMySeries($lastRoute, $page, $condition, int $serieId, SeriesRepository $sr) //: Response
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $serie = $sr->findOneBy([
            'id' => $serieId,
        ]);
        $user->addSeries($serie);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        if ($lastRoute == 'serie_show') {
            return $this->redirectToRoute($lastRoute, ['serieId' => $serieId, 'page' => $page, 'condition' => $condition]);
        } else if ($condition == "NULL") {
            return $this->redirectToRoute($lastRoute, ['page' => $page]);
        } else {
            return $this->redirectToRoute($lastRoute, ['page' => $page, 'condition' => $condition]);
        }
    }

    /**
     * @Route("/mySeries/remove/{serieId}/{lastRoute}/{page}/{condition}", name="remove_user_series",  requirements={"serieId"="\d+","lastRoute"="[a-z_a-z]+"}, defaults={"page" = null,"condition" =null})
     */
    public function removeFromMySeries($lastRoute, $page, $condition, int $serieId, SeriesRepository $sr) //: Response
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $serie = $sr->findOneBy([
            'id' => $serieId,
        ]);
        $user->removeSeries($serie);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        if ($lastRoute == 'serie_show') {
            return $this->redirectToRoute($lastRoute, ['serieId' => $serieId]);
        } else if ($condition == "NULL") {
            return $this->redirectToRoute($lastRoute, ['page' => $page]);
        } else {
            return $this->redirectToRoute($lastRoute, ['page' => $page, 'condition' => $condition]);
        }
    }

    /**
     * @Route("/comments/all/{nbResults}", name="all_comments")
     */
    public function allMessages(RatingRepository $repository, int $nbResults = 10): Response
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $nbResults = min($nbResults, $repository->findByCountOfAllComments());
        $allComments = $repository->findAll_N_Comments($nbResults);
        $usercomments = $repository->findAllMyComments($user);
        $totalComments = $repository->findByCountOfAllComments();
        return $this->render('myUser/allComments.html.twig', [
            "comments" => $allComments,
            "usercomments" => $usercomments,
            "nbResults" => $nbResults,
            "totalNbComments" => $totalComments,
            "route" => "all_comments",
            "changeroute" => "user_comments"
        ]);
    }
    /**
     * @Route("/comments/{nbResults}", name="user_comments")
     */
    public function myMessages(RatingRepository $repository, int $nbResults = 10): Response
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $nbResults = min($nbResults, $repository->findByCountOfUserComments($user));
        $myComments = $repository->findAll_N_MyComments($user, $nbResults);
        $totalComments = $repository->findByCountOfUserComments($user);
        return $this->render('myUser/myComments.html.twig', [
            "comments" => $myComments,
            "user" => $user,
            "nbResults" => $nbResults,
            "totalNbComments" => $totalComments,
            "route" => "user_comments",
            "changeroute" => "all_comments"
        ]);
    }
}
