<?php

namespace App\Services\Settings;

use App\Repositories\Setting\SettingRepositoryInterface;
use RuntimeException;

class SettingService implements SettingServiceInterface
{
    public function __construct(private readonly SettingRepositoryInterface $settings,) {}

    /**
     * @return object|null
     */
    public function get(): ?object
    {
        return $this->settings->get();
    }

    /**
     * @param string $yandexUrl
     * @return void
     */
    public function save(string $yandexUrl): void
    {
        $orgId = $this->extractOrganizationId($yandexUrl);

        if (!$orgId) {
            throw new RuntimeException('Не удалось извлечь ID организации из ссылки');
        }

        $this->settings->update([
            'yandex_map_url' => $yandexUrl,
            'organization_id' => $orgId,
        ]);
    }

    /**
     * @param string $url
     * @return string|null
     */
    private function extractOrganizationId(string $url): ?string
    {
        $patterns = [
            '/\/org\/[^\/]+\/(\d+)\/?$/',
            '/\/(\d+)\/reviews?\/?$/',
            '/\/org\/(\d+)\/?$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $m)) {
                return $m[1];
            }
        }

        return null;
    }
}
