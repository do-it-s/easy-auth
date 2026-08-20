<?php

namespace DoITs\EasyAuth\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decorates the vendor login response to inject device_uuid into the JSON
 * payload when authorizeLoginUsing() (see EasyAuthServiceProvider) just
 * re-established it for a PWA re-sync, so index.js's attemptPwaResync()
 * can persist it back into localStorage. Wraps the vendor response rather
 * than replacing it outright, and is attached via Container::extend()
 * rather than a direct contract bind() override, so this doesn't depend on
 * provider registration order against PasskeysServiceProvider's own
 * singleton() binding of the same contract.
 */
class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    public function __construct(private readonly PasskeyLoginResponseContract $base) {}

    /**
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $response = $this->base->toResponse($request);

        $deviceUuid = $request->session()->pull('pwa_resync_device_uuid');

        if ($deviceUuid && $response instanceof JsonResponse) {
            $response->setData([...$response->getData(true), 'device_uuid' => $deviceUuid]);
        }

        return $response;
    }
}
