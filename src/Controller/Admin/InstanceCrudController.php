<?php

namespace App\Controller\Admin;

use App\Entity\Instance;
use App\Service\GithubApiService;
use App\Service\MauticApiService;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InstanceCrudController extends AbstractCrudController
{

    public function __construct(
        GithubApiService $githubApiService,
        MauticApiService $mauticApiService)
    {
        $this->githubApiService = $githubApiService;
        $this->mauticApiService = $mauticApiService;
    }

    public static function getEntityFqcn(): string
    {
        return Instance::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->hideOnDetail()->hideOnIndex(),
            # Configurable Fields
            FormField::addPanel('Basic Configuration'),
            TextField::new('name'),
            UrlField::new('baseUrl'),

            # Monitored Fields
            FormField::addPanel('Monitored Fields')->hideOnForm(),
            TextField::new('mauticVersion')->hideOnForm()
                ->formatValue(fn($value) => $this->githubApiService->compareVersionAgainstLatestVersion($value)
                    ? $value
                    : [$value, $this->githubApiService->getLatestStableVersionForMajorVersion($value)])
                ->setTemplatePath('admin/field/mautic_version.html.twig'),
            TextField::new('phpVersion')->hideOnForm(),
            DateTimeField::new('lastUpdated')->hideOnForm()
                ->setFormat('dd.MM.YY HH:mm'),

            # Private Fields
            FormField::addPanel('User Details'),
            TextField::new('username')->hideOnIndex(),
            TextField::new('password')->hideOnIndex()
                ->formatValue(fn($value) => '********'),
        ];
    }

    public function refreshInstance(AdminContext $context): RedirectResponse
    {
        $this->syncInstanceWithMessage($context->getEntity()->getInstance());
        return $this->redirect($context->getReferrer());
    }

    public function refreshAllInstances(BatchActionDto $actionDto): RedirectResponse
    {
        $entityManager = $this->getDoctrine()->getManagerForClass($actionDto->getEntityFqcn());
        foreach ($actionDto->getEntityIds() as $id) {
            $instance = $this->getDoctrine()->getRepository(Instance::class)->find($id);
            $this->syncInstanceWithMessage($instance);
        }
        return $this->redirect($actionDto->getReferrerUrl());
    }

    /**
     * @param Instance $instance
     */
    private function syncInstanceWithMessage($instance)
    {
        $this->mauticApiService->syncInstance($instance);

        if ($instance->getState() === "down"){
            $this->addFlash("warning", $instance->getName() . " is not reachable");
        }
    }

    /**
     * @param Request $request
     * @param GithubApiService $githubApi
     * @return Response
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     *
     * @Route ("/updateVersionsAsync", name="updateVersionsAsync")
     */
    public function updateGithubVersions(Request $request, GithubApiService $githubApi): Response
    {
        $githubApi->updateAssociativeArrayOfLatestVersions();
        return $this->json(true);
    }

}
