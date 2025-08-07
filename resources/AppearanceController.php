<?php

namespace Jexactyl\Http\Controllers\Admin\Jexactyl;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Jexactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Config\Repository;
use Jexactyl\Exceptions\Model\DataValidationException;
use Jexactyl\Exceptions\Repository\RecordNotFoundException;
use Jexactyl\Contracts\Repository\SettingsRepositoryInterface;
use Jexactyl\Http\Requests\Admin\Jexactyl\AppearanceFormRequest;
use Jexactyl\Models\SettingPaste;

class AppearanceController extends Controller
{
    /**
     * AppearanceController constructor.
     */
    public function __construct(
        private Repository $config,
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * Render the Jexactyl settings interface.
     */
    public function index(): View
    {
        $logService = SettingPaste::getValue('log_service', 'mclogs');
        return view('admin.jexactyl.appearance', [
            'name' => config('app.name'),
            'logo' => config('app.logo'),

            'admin' => config('theme.admin'),
            'user' => ['background' => config('theme.user.background')],

            'logService' => $logService,
        ]);
    }

    /**
     * Handle settings update.
     *
     * @throws DataValidationException|RecordNotFoundException
     */
    public function update(AppearanceFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            if ($key === 'log_service') {
                SettingPaste::setValue('log_service', $value);
                continue;
            }
            $this->settings->set('settings::' . $key, $value);
        }

        $this->alert->success('Jexactyl Appearance has been updated.')->flash();

        return redirect()->route('admin.jexactyl.appearance');
    }
}