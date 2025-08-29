<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class PayloadCmsService
{
    private HttpClientInterface $httpClient;
    private string $payloadApiUrl;
    private string $payloadApiKey;
    private LoggerInterface $logger;

    public function __construct(
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $this->httpClient = HttpClient::create();
        $this->payloadApiUrl = $params->get('payload_cms_api_url');
        $this->payloadApiKey = $params->get('payload_cms_api_key');
        $this->logger = $logger;
    }

    /**
     * Crée un nouvel utilisateur dans Payload CMS
     */
    public function createUser(array $userData): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->payloadApiUrl . '/api/users', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->payloadApiKey,
                ],
                'json' => $userData,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode !== 201 && $statusCode !== 200) {
                $this->logger->error('Erreur lors de la création de l\'utilisateur dans Payload CMS', [
                    'status_code' => $statusCode,
                    'response' => $content,
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la création de l\'utilisateur',
                    'data' => $content,
                ];
            }

            return [
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'data' => $content,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Exception lors de la création de l\'utilisateur dans Payload CMS', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Prépare les données du formulaire d'onboarding pour Payload CMS
     */
    public function prepareUserDataForPayload(array $onboardingData): array
    {
        return [
            'firstName' => $onboardingData['firstName'],
            'lastName' => $onboardingData['lastName'],
            'email' => $onboardingData['email'],
            'password' => $onboardingData['password'],
            'role' => 'student',
            'studyYear' => $onboardingData['studyYear'],
            'examDate' => $onboardingData['examDate'],
            'studyProfile' => [
                'targetScore' => $onboardingData['targetScore'],
                'studyHoursPerWeek' => $onboardingData['studyHoursPerWeek'],
            ],
            'onboardingComplete' => true,
        ];
    }
}
