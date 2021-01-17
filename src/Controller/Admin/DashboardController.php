<?php

namespace App\Controller\Admin;

use App\Entity\Series;
use App\Entity\User;
use App\Entity\Rating;
use App\Entity\Episode;
use App\Entity\Season;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        $routeBuilder = $this->get(AdminUrlGenerator::class);

        return $this->redirect($routeBuilder->setController(UserCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Admin Area');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Return User Interface', 'fa fa-reply', 'menu');
        yield MenuItem::section('Users');
        yield MenuItem::linktoCrud('User Management', 'fa fa-users', User::class);
        yield MenuItem::section('Ratings');
        yield MenuItem::linktoCrud('Ratings Management', 'fa fa-comments', Rating::class);
        yield MenuItem::section('Series');
        yield MenuItem::linktoCrud('Series Management', 'fa fa-television', Series::class);
        yield MenuItem::linktoCrud('Seasons Management', 'fa fa-window-restore', Season::class);
        yield MenuItem::linktoCrud('Episodes Management', 'fa fa-film', Episode::class);
        yield MenuItem::section('Series From OMDB');
        yield MenuItem::linkToRoute('Add serie from OMDB', 'fa fa-television', 'add_omdb_series');
    }
}
