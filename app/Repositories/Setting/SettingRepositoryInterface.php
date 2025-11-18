<?php

namespace App\Repositories\Setting;

interface SettingRepositoryInterface
{
    /**
     * @return object|null
     */
    public function get(): ?object;

    /**
     * @param array $data
     * @return void
     */
    public function update(array $data): void;

    /**
     * @return string|null
     */
    public function getOrganizationId(): ?string;
}
