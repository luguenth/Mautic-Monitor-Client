<?php

namespace App\Controller;

use App\Repository\InstanceRepository;
use App\Service\MauticApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MonitorController extends AbstractController
{

    public function index(Request $request, MauticApiService $mauticApi, InstanceRepository $instanceRepository): Response
    {
        $instances = $instanceRepository->findAll();
        $instanceInformation = [];
        foreach ($instances as $instance) {
            $instanceInformation[$instance->getId()] = [
                "phpVersion" => $mauticApi->getVersion($instance),
            ];
        }
        return $this->render('monitor/index.html.twig', [
            'instances' => $instanceInformation,
        ]);
    }
}
