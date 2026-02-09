<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use Illuminate\Http\Request;

/**
 * Gestion des clients particuliers - Caissière
 */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $customers = $query->active()->latest()->paginate(20);

        return view('cashier.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('cashier.customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|unique:customers,phone',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create($validated);

        ActivityLog::log('create', $customer, null, $customer->toArray(), "Création client: {$customer->full_name}");

        // Si requête AJAX (depuis le formulaire de vente)
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'customer' => $customer,
                'message' => 'Client créé avec succès.',
            ]);
        }

        return redirect()->route('cashier.customers.index')
            ->with('success', 'Client créé avec succès.');
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'sales' => fn($q) => $q->latest()->take(10),
            'repairs' => fn($q) => $q->latest()->take(10),
        ]);

        return view('cashier.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('cashier.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|unique:customers,phone,' . $customer->id,
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $oldValues = $customer->toArray();
        $customer->update($validated);

        ActivityLog::log('update', $customer, $oldValues, $customer->toArray(), "Modification client: {$customer->full_name}");

        return redirect()->route('cashier.customers.show', $customer)
            ->with('success', 'Client mis à jour.');
    }

    /**
     * Recherche AJAX
     */
    public function search(Request $request)
    {
        $search = $request->get('q');

        $customers = Customer::search($search)
            ->active()
            ->take(10)
            ->get(['id', 'first_name', 'last_name', 'phone']);

        return response()->json($customers);
    }
}
