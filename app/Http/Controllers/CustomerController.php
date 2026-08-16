<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function customerPage(Request $request)
    {
        $userId = $request->header('id');
        $customers = Customer::where('user_id', '=', $userId)->get();

        return Inertia::render('Customer/CustomerPage', ['customers' => $customers]);
    }

    public function customerSavePage(Request $request)
    {
        $id = $request->query('id');
        $userId = $request->header('id');
        $customer = Customer::where('user_id', '=', $userId)->where('id', '=', $id)->first();

        return Inertia::render('Customer/CustomerSavePage', ['customer' => $customer]);
    }

    public function createCustomer(Request $request)
    {
        try {
            $userId = $request->header('id');
            $this->validateCustomerPayload($request, $userId);

            $customer = Customer::create([
                'user_id' => $userId,
                'name' => trim($request->input('name')),
                'email' => $this->normalizeEmail($request->input('email')),
                'mobile' => trim((string) $request->input('mobile', '')),
                'address' => trim((string) $request->input('address', '')),
                'notes' => trim((string) $request->input('notes', '')),
                'balance_due' => 0,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Customer created successfully',
                    'customer' => $customer,
                ]);
            }

            return redirect()->route('customerSavePage')->with(['status' => true, 'message' => 'Customer created successfully']);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            throw $e;
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Customer creation failed',
                ], 500);
            }

            return redirect()->route('customerSavePage')->with(['status' => false, 'message' => 'Customer creation failed']);
        }
    }

    public function updateCustomer(Request $request)
    {
        try {
            $userId = $request->header('id');
            $customerId = $request->input('id');

            $this->validateCustomerPayload($request, $userId, $customerId);

            Customer::where('id', '=', $customerId)->where('user_id', '=', $userId)->update([
                'name' => trim($request->input('name')),
                'email' => $this->normalizeEmail($request->input('email')),
                'mobile' => trim((string) $request->input('mobile', '')),
                'address' => trim((string) $request->input('address', '')),
                'notes' => trim((string) $request->input('notes', '')),
            ]);

            return redirect()->route('customerSavePage')->with(['status' => true, 'message' => 'Customer updated successfully']);
        } catch (Exception $e) {
            return redirect()->route('customerSavePage')->with(['status' => false, 'message' => 'Customer updated fail']);
        }
    }

    public function deleteCustomer(Request $request)
    {
        try {
            $userId = $request->header('id');
            $customerId = $request->query('id');
            Customer::where('id', '=', $customerId)->where('user_id', '=', $userId)->delete();

            return redirect()->route('customerPage')->with(['status' => true, 'message' => 'Customer deleted successfully']);
        } catch (Exception $e) {
            return redirect()->route('customerPage')->with(['status' => false, 'message' => 'Customer deletion failed']);
        }
    }

    private function validateCustomerPayload(Request $request, int|string $userId, ?int $customerId = null): void
    {
        $mobile = trim((string) $request->input('mobile', ''));
        $email = trim((string) $request->input('email', ''));

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'required|string|max:30',
            'address' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ];

        $request->validate($rules);

        $existingByMobile = Customer::where('user_id', $userId)
            ->where('mobile', $mobile)
            ->when($customerId, fn ($query) => $query->where('id', '!=', $customerId))
            ->first();

        if ($existingByMobile) {
            throw ValidationException::withMessages([
                'mobile' => 'Customer already exists.',
            ]);
        }

        if ($email !== '') {
            $existingByEmail = Customer::where('user_id', $userId)
                ->where('email', $email)
                ->when($customerId, fn ($query) => $query->where('id', '!=', $customerId))
                ->first();

            if ($existingByEmail) {
                throw ValidationException::withMessages([
                    'email' => 'Customer already exists with this email.',
                ]);
            }
        }
    }

    private function normalizeEmail(?string $email): string
    {
        $email = trim((string) $email);

        if ($email !== '') {
            return $email;
        }

        return 'customer-' . uniqid() . '@pos.local';
    }
}
