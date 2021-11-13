<?php

namespace App\Service;

use App\Entity\Instance;
use App\Repository\InstanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MauticApiService
{
    /**
     * @var string|null
     */
    private $username;

    /**
     * @var string|null
     */
    private $password;

    public function __construct(
        HttpClientInterface    $client,
        InstanceRepository     $instanceRepository,
        EntityManagerInterface $entityManager)
    {
        $this->client = $client;
        $this->instanceRepository = $instanceRepository;
        $this->entityManager = $entityManager;
    }

    public function getMonitoringData(Instance $instance)
    {
        $this->setCredentials($instance);
        $this->request("/monitor");
    }

    public function getVersion(Instance $instance): ?string
    {
        $this->setCredentials($instance);
        return $this->request("/monitor/version");
    }

    private function setCredentials(Instance $instance)
    {
        $this->username = $instance->getUsername();
        $this->password = $instance->getPassword();
    }

    private function hasCredentials(): bool
    {
        return ($this->username && $this->password);
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     */
    private function request(string $endpoint)
    {
        if (!$this->hasCredentials()) {
            return "no Credentials";
        }
        $base_url = "https://mautic2.ddev.site/api";
        $response = $this->client->request(
            "GET",
            $base_url . $endpoint,
            ['auth_basic' => [$this->username, $this->password]]);
        return json_decode($response->getContent(), true);
    }

    public function syncInstances(SymfonyStyle $io = null)
    {

        $allInstances = $this->instanceRepository->findAll();
        if($io) {
            $io->text("Starting synchronizing Instances");
            $io->createProgressBar(count($allInstances));
            $io->progressStart();
        }

        foreach ($allInstances as $instance) {
            $this->syncInstance($instance);
            if($io) $io->progressAdvance();
        }
        if($io) $io->progressFinish();
    }

    public function syncInstance(Instance $instance)
    {
        $this->setCredentials($instance);
        try {
            $instanceInfo = $this->request("/monitor/all");
            $instance->setState("up");
            $instance->setPhpVersion($instanceInfo['phpVersion']);
            $instance->setMauticVersion($instanceInfo['mauticVersion']);
            $instance->setLastUpdated();
        } catch (\Exception $e) {
            $instance->setState("down");
        }
        $this->entityManager->persist($instance);
        $this->entityManager->flush();
    }
}