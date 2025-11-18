<?php

namespace App\Repositories\Setting;

use App\Models\Setting;

class SettingRepository implements SettingRepositoryInterface
{
    /**
     * @return object|null
     */
    public function get(): ?object
    {
        return Setting::query()->first();
    }

    /**
     * @param array $data
     * @return void
     */
    public function update(array $data): void
    {
        Setting::query()->updateOrCreate(['id' => 1], $data);
    }

    /**
     * @return string|null
     */
    public function getOrganizationId(): ?string
    {
        $setting = Setting::query()->first();

        if (!$setting || !$setting->yandex_map_url) {
            return null;
        }

        $url = $setting->yandex_map_url;

        // Извлекаем ID организации из ссылки
        if (preg_match('/\/org\/[^\/]+\/(\d+)/', $url, $matches)) {
            return $matches[1]; // ← например 1010501395
        }

        return null;
    }
}
