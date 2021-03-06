<?php

namespace App\Service;

use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GithubApiService
{
    private $latest_versions = [
        2 => ['stable' => "2.0.0", 'dev' => "2.0.0-b"],
        3 => ['stable' => "3.0.0", 'dev' => "3.0.0-b"],
        4 => ['stable' => "3.0.0", 'dev' => "4.0.0-b"],
    ];

    public function __construct(HttpClientInterface $client)
    {
        $store = new Store('cache/');
        $this->client = new CachingHttpClient($client, $store, [
            'default_ttl' => 3600, // 1 hour
        ]);
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     */
    private function getReleasesFromGithub()
    {
        $response = $this->client->request('GET', 'https://api.github.com/repos/mautic/mautic/releases');
        return json_decode($response->getContent(), true);
    }

    private function findOutLatestReleases($json_decode)
    {
        foreach ($json_decode as $releases) {
            $version_number = $releases['tag_name'];
            if ($this->isVersionStable($version_number)) {
                $this->latest_versions = [
                    $this->getMajorVersionNumber($version_number) =>
                        ["stable" => $version_number]
                ];
            }
        }
    }

    # Function to find out if Version Tag is stable
    public function isVersionStable($version)
    {
        if (preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $version)) {
            return true;
        }
        return false;
    }

    # Function to get Major Version Number
    public function getMajorVersionNumber($version): int
    {
        if (!$version) {
            throw new \Exception("Version is not valid");
        }
        $version_number = explode(".", $version);
        return $version_number[0];
    }


    # Compare two given versions
    public function compareVersions($version1, $version2)
    {
        $version1 = explode('.', $version1);
        $version2 = explode('.', $version2);
        $version1 = array_map('intval', $version1);
        $version2 = array_map('intval', $version2);
        for ($i = 0, $iMax = count($version1); $i < $iMax; $i++) {
            if ($version1[$i] > $version2[$i]) {
                return true;
            }

            if ($version1[$i] < $version2[$i]) {
                return false;
            }
        }
        return 0;
    }

    # Get latest stable version
    public function getLatestStableVersion()
    {
        return $this->latest_versions;
    }

    # Function to build associative array of latest stable versions

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     */
    public function updateAssociativeArrayOfLatestVersions()
    {
        $releases = $this->getReleasesFromGithub();
        foreach ($releases as $release) {
            $version_number = $release['tag_name'];
            if ($this->isVersionStable($version_number)) {
                if ($this->compareVersions($version_number, $this->latest_versions[$this->getMajorVersionNumber($version_number)]['stable'])) {
                    $this->latest_versions[$this->getMajorVersionNumber($version_number)]["stable"] = $version_number;
                }
            } else {
                if ($this->compareVersions($version_number, $this->latest_versions[$this->getMajorVersionNumber($version_number)]['dev'])) {
                    $this->latest_versions[$this->getMajorVersionNumber($version_number)]["dev"] = $version_number;
                }
            }
        }
    }


    public function getLatestStableVersionForMajorVersion($majorVersion)
    {
        $versionNumber = $this->getMajorVersionNumber($majorVersion);
        return $this->latest_versions[$versionNumber]['stable'];
    }

    public function getLatestDevVersionForMajorVersion($majorVersion)
    {
        $versionNumber = $this->getMajorVersionNumber($majorVersion);
        return $this->latest_versions[$versionNumber]['dev'];
    }

    public function compareVersionAgainstLatestVersion($version)
    {
        if (!$version) {
            return false;
        }
        $majorVersion = $this->getMajorVersionNumber($version);
        if ($this->compareVersions($version, $this->getLatestStableVersionForMajorVersion($majorVersion))) {
            return true;
        }
        return false;
    }

}