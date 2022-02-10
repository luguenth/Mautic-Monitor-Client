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

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @param HttpClientInterface $client
     * @param InstanceRepository $instanceRepository
     * @param EntityManagerInterface $entityManager
     */
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
        dump($instance);
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
        $this->baseUrl = $instance->getBaseUrl();
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

        dump($this->baseUrl . $endpoint);

        $response = $this->client->request(
            "GET",
            $this->baseUrl . "/api" . $endpoint,
            ['auth_basic' => [$this->username, $this->password]]);
        dump($response->getContent());
        error_log($response->getContent());
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
            error_log("Syncing instance " . $instance->getName(),4);
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
