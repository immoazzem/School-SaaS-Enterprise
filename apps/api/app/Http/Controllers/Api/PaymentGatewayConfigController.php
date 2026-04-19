<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewayConfig;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentGatewayConfigController extends Controller
{
    public function index(Request $request, School $school): JsonResponse
    {
        $this->authorizeGatewayManagement($request, $school);

        return response()->json($this->paginated(
            $school->paymentGatewayConfigs()
                ->when($request->filled('gateway'), fn ($query) => $query->where('gateway', $request->string('gateway')))
                ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
                ->orderBy('gateway')
                ->paginate($this->perPage($request))
        ));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeGatewayManagement($request, $school);
        $validated = $this->validatedPayload($request, $school);

        $config = $school->paymentGatewayConfigs()->create($validated);

        $this->recordAudit($request, $school, 'payment_gateway_config.created', $config, [
            'gateway' => $config->gateway,
            'is_active' => $config->is_active,
            'test_mode' => $config->test_mode,
            'credential_keys' => $config->credential_keys,
        ]);

        return response()->json(['data' => $config], 201);
    }

    public function show(Request $request, School $school, PaymentGatewayConfig $paymentGatewayConfig): JsonResponse
    {
        $this->authorizeGatewayManagement($request, $school);
        abort_unless($paymentGatewayConfig->school_id === $school->id, 404);

        return response()->json(['data' => $paymentGatewayConfig]);
    }

    public function update(Request $request, School $school, PaymentGatewayConfig $paymentGatewayConfig): JsonResponse
    {
        $this->authorizeGatewayManagement($request, $school);
        abort_unless($paymentGatewayConfig->school_id === $school->id, 404);

        $validated = $this->validatedPayload($request, $school, $paymentGatewayConfig);
        $paymentGatewayConfig->update($validated);
        $paymentGatewayConfig = $paymentGatewayConfig->fresh();

        $this->recordAudit($request, $school, 'payment_gateway_config.updated', $paymentGatewayConfig, [
            'gateway' => $paymentGatewayConfig->gateway,
            'changed' => array_values(array_diff(array_keys($validated), ['credentials_encrypted'])),
            'credential_keys' => array_key_exists('credentials_encrypted', $validated) ? $paymentGatewayConfig->credential_keys : null,
        ]);

        return response()->json(['data' => $paymentGatewayConfig]);
    }

    public function destroy(Request $request, School $school, PaymentGatewayConfig $paymentGatewayConfig): JsonResponse
    {
        $this->authorizeGatewayManagement($request, $school);
        abort_unless($paymentGatewayConfig->school_id === $school->id, 404);

        $paymentGatewayConfig->delete();

        $this->recordAudit($request, $school, 'payment_gateway_config.deleted', $paymentGatewayConfig, [
            'gateway' => $paymentGatewayConfig->gateway,
        ]);

        return response()->json(status: 204);
    }

    private function authorizeGatewayManagement(Request $request, School $school): void
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'payment_gateways.manage'), 403);
    }

    private function validatedPayload(Request $request, School $school, ?PaymentGatewayConfig $config = null): array
    {
        $validated = $request->validate([
            'gateway' => [
                $config ? 'sometimes' : 'required',
                Rule::in(['bkash', 'nagad', 'sslcommerz', 'stripe']),
                Rule::unique('payment_gateway_configs', 'gateway')
                    ->where('school_id', $school->id)
                    ->whereNull('deleted_at')
                    ->ignore($config?->id),
            ],
            'credentials' => [$config ? 'sometimes' : 'required', 'array', 'min:1'],
            'credentials.*' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'test_mode' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('credentials', $validated)) {
            $validated['credentials_encrypted'] = collect($validated['credentials'])
                ->filter(fn ($value): bool => filled($value))
                ->all();
            unset($validated['credentials']);
        }

        return $validated;
    }
}
