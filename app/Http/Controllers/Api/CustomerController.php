<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search', ''));

        $customers = Customer::where('business_id', $request->user()->business_id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('ident', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 25));

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request): CustomerResource
    {
        $customer = Customer::create([
            ...$request->validated(),
            'business_id' => $request->user()->business_id,
        ]);

        return new CustomerResource($customer);
    }

    public function show(Request $request, Customer $customer): CustomerResource
    {
        $this->assertBelongsToBusiness($request, $customer);

        return new CustomerResource($customer);
    }

    public function update(StoreCustomerRequest $request, Customer $customer): CustomerResource
    {
        $this->assertBelongsToBusiness($request, $customer);

        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        $this->assertBelongsToBusiness($request, $customer);

        $customer->delete();

        return response()->json(['message' => 'Cliente eliminado.']);
    }

    private function assertBelongsToBusiness(Request $request, Customer $customer): void
    {
        abort_if($customer->business_id !== $request->user()->business_id, 404);
    }
}
