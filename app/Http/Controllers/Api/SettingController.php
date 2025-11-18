<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\StoreRequest;
use App\Services\Settings\SettingServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingServiceInterface $settings)
    {
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('settings.index', [
            'setting' => $this->settings->get(),
        ]);
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->settings->save($request->validated()['yandex_map_url']);

            return redirect()
                ->route('settings.index')
                ->with('success', 'Настройки успешно сохранены');
        } catch (Throwable $e) {
            return back()
                ->withErrors(['yandex_map_url' => $e->getMessage()])
                ->withInput();
        }
    }
}
