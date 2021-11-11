<?php

namespace App\Controller\Admin;

use App\Entity\Instance;
use App\Service\GithubApiService;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class InstanceCrudController extends AbstractCrudController
{

    public function __construct(GithubApiService $githubApiService)
    {
        $this->githubApiService = $githubApiService;
    }

    public static function getEntityFqcn(): string
    {
        return Instance::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            # Configurable Fields
            FormField::addPanel('Basic Configuration'),
            TextField::new('name'),
            UrlField::new('baseUrl'),

            # Monitored Fields
            FormField::addPanel('Monitored Fields')->hideOnForm(),
            TextField::new('phpVersion')->hideOnForm(),
            TextField::new('mauticVersion')->hideOnForm()
                ->formatValue(fn($value) => $this->githubApiService->compareVersionAgainstLatestVersion($value)
                    ? sprintf('%s 🔹', $value)
                        : [$value,$this->githubApiService->getLatestStableVersionForMajorVersion($value)])
                    //: sprintf('%s 🔺', $value))
                ->setTemplatePath('admin/field/mautic_version.html.twig'),
            DateTimeField::new('lastUpdated')->hideOnForm()
                ->setFormat('dd.MM.YY HH:mm'),

            # Private Fields
            FormField::addPanel('User Details'),
            TextField::new('username')->hideOnIndex(),
            TextField::new('password')->hideOnIndex()
                ->formatValue(fn($value) => '********'),
        ];
    }

}
