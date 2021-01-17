<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Rating;
use DateTime;
use App\Form\UserRatingsFormType;
use App\Repository\RatingRepository;
use App\Repository\SeriesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserRatingsController extends AbstractController
{

    /**
     * @Route("/rating/edit/{serieId}/{ratingId}/{lastRoute}", name="edit_user_rating", methods={"GET","POST"})
     */
    public function edit(Request $request, int $ratingId, int $serieId,  $lastRoute, RatingRepository $rr): Response
    {
        $rating = $rr->findOneBy([
            'id' => $ratingId,
        ]);
        $form = $this->createForm(UserRatingsFormType::class, $rating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            if ($lastRoute == 'serie_show') {
                return $this->redirectToRoute($lastRoute, ['serieId' => $serieId]);
            } else {
                return $this->redirectToRoute($lastRoute);
            }
        } else {
            return $this->render('user_ratings/edit.html.twig', [
                'serieId' => $serieId,
                'lastRoute' => $lastRoute,
                'comment' => $rating,
                'form' => $form->createView(),
            ]);
        }
    }

    /**
     * @Route("/rating/add/{serieId}", name="add_user_rating")
     */
    public function addRating(Request $request, int $serieId, SeriesRepository $sr): Response
    {
        $rating = new Rating();
        $form = $this->createForm(UserRatingsFormType::class, $rating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            date_default_timezone_set('Europe/Paris');
            $rating->setDate(new DateTime(date('m/d/Y h:i:s a', time())));
            $rating->setUser($this->get('security.token_storage')->getToken()->getUser());
            $rating->setSeries($sr->findOneBy([
                'id' => $serieId,
            ]));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($rating);
            $entityManager->flush();
            // do anything else you need here, like send an email
            return $this->redirectToRoute('serie_show', ['serieId' => $serieId]);
        }

        return $this->render('user_ratings/index.html.twig', [
            'ratingForm' => $form->createView(),
            'serieId' => $serieId
        ]);
    }

    /**
     * @Route("/rating/delete/{serieId}/{ratingId}/{lastRoute}", name="delete_user_rating")
     */
    public function deleteRating($lastRoute, int $ratingId, int $serieId, RatingRepository $rr): Response
    {
        $rating = $rr->findOneBy([
            'id' => $ratingId,
        ]);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($rating);
        $entityManager->flush();
        if ($lastRoute == 'serie_show') {
            return $this->redirectToRoute($lastRoute, ['serieId' => $serieId]);
        } else {
            return $this->redirectToRoute($lastRoute); //[TODO A tester si on ne peut pas combiner ces deux lignes np l'id envoyé en trop]
        }
    }

    /**
     * @Route("/user/addEpisode/{episodeId}", name="add_user_episode")
     */
    public function addEpisode($episodeId)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $eRepository = $em->getRepository(Episode::class);
        $episode = $eRepository->findOneBy([
            'id' => $episodeId,
        ]);
        $user->addEpisode($episode);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        $season = $episode->getSeason();
        $serie = $season->getSeries();
        return $this->redirectToRoute('episodes', [
            'serieId' => $serie->getId(),
            'seasonId' => $season->getId()
        ]);
    }

    /**
     * @Route("/user/removeEpisode/{episodeId}", name="remove_user_episode")
     */
    public function removeEpisode($episodeId)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $eRepository = $em->getRepository(Episode::class);
        $episode = $eRepository->findOneBy([
            'id' => $episodeId,
        ]);
        $user->removeEpisode($episode);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        $season = $episode->getSeason();
        $serie = $season->getSeries();
        return $this->redirectToRoute('episodes', [
            'serieId' => $serie->getId(),
            'seasonId' => $season->getId()
        ]);
    }
}
