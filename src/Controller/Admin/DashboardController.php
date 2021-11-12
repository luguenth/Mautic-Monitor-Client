<?php

namespace App\Controller\Admin;

use App\Entity\Instance;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/", name="admin")
     */
    public function index(): Response
    {
        return $this->render('admin/instanceDetail.html.twig');
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setEntityLabelInPlural('Instances')
            ->showEntityActionsInlined();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Mautic Monitor Client')
            ->renderContentMaximized()
            ->renderSidebarMinimized()
            ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Instances', 'fas fa-list', Instance::class);
    }

    public function configureActions(): Actions
    {
        dump($this);
        return parent::configureActions()

            #Index Actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit')->setLabel(false);
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash')->setLabel(false);
            })
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel(false);
            })
            ->add(Crud::PAGE_INDEX, Action::new('refresh')
                ->setIcon('fas fa-sync')
                ->setLabel(false)
                ->linkToCrudAction('refreshInstance'))

            #Batch actions
            ->addBatchAction(Action::new('refreshAll')
                ->setLabel('Refresh')
                ->linkToCrudAction('refreshAllInstances'));

    }
}
