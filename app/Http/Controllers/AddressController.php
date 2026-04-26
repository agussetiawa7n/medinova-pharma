<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index()
    {
        $addresses = Auth::user()->addresses()->latest()->get();
        return view('address.index', compact('addresses'));
    }

    public function create()
    {
        return view('address.form', ['address' => new Address()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['user_id'] = Auth::id();

        DB::transaction(function () use ($data) {
            if (!empty($data['is_default'])) {
                Auth::user()->addresses()->update(['is_default' => false]);
            }
            Auth::user()->addresses()->create($data);
        });

        return redirect()->route('addresses.index')->with('success', 'Address saved successfully.');
    }

    public function edit(Address $address)
    {
        $this->authorizeOwner($address);
        return view('address.form', compact('address'));
    }

    public function update(Request $request, Address $address)
    {
        $this->authorizeOwner($address);
        $data = $this->validated($request);

        DB::transaction(function () use ($address, $data) {
            if (!empty($data['is_default'])) {
                Auth::user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }
            $address->update($data);
        });

        return redirect()->route('addresses.index')->with('success', 'Address updated.');
    }

    public function destroy(Address $address)
    {
        $this->authorizeOwner($address);
        $address->delete();
        return redirect()->route('addresses.index')->with('success', 'Address removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label'          => 'nullable|string|max:40',
            'first_name'     => 'required|string|max:60',
            'last_name'      => 'nullable|string|max:60',
            'phone'          => 'required|string|max:20',
            'address_line_1' => 'required|string|max:200',
            'address_line_2' => 'nullable|string|max:200',
            'city'           => 'required|string|max:80',
            'state'          => 'required|string|max:80',
            'postal_code'    => 'required|string|max:12',
            'country'        => 'required|string|max:80',
            'is_default'     => 'nullable|boolean',
        ]);
    }

    private function authorizeOwner(Address $address): void
    {
        abort_unless($address->user_id === Auth::id(), 403);
    }
}
