<?php

namespace App\Controller;

use App\Entity\Series;
use App\Repository\SeriesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AllPersonsMenuController extends AbstractController
{
    /**
     * @Route("/", name="menu_without_connection")
     */
    public function index(): Response
    {
        return $this->render('all_persons_menu/display_logo.twig');
    }

    /**
     * 
     * @Route("/homepage",  name="homepage")
     */
    public function homepage(SeriesRepository $repository): Response
    {
        $series = $repository->findByRandom(10);

        return $this->render('all_persons_menu/index.html.twig', [
            "series" => $series
        ]);
    }

    /**
     * 
     * @Route("/{id}/poster",  name="poster", methods={"GET"})
     */
    public function posterDisplay(Series $serie)
    {
        $posterName = $serie->__toString() . ".png";

        return new Response(
            stream_get_contents($serie->getPoster()),
            200,
            [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'inline; filename="' . $posterName . '"',
            ]
        );
    }
}
