<?php

namespace App\Services\Settings;

interface SettingServiceInterface
{
    /**
     * @return object|null
     */
    public function get(): ?object;

    /**
     * @param string $yandexUrl
     * @return void
     */
    public function save(string $yandexUrl): void;
}
