<div class="fi-ta-table">
    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start">
        <thead style="background:#F9FAFB;">
            <tr>
                <th class="px-3 py-2 text-xs font-semibold text-gray-600">Product</th>
                <th class="px-3 py-2 text-xs font-semibold text-gray-600">SKU</th>
                <th class="px-3 py-2 text-xs font-semibold text-gray-600 text-center">Qty</th>
                <th class="px-3 py-2 text-xs font-semibold text-gray-600 text-right">Unit Price</th>
                <th class="px-3 py-2 text-xs font-semibold text-gray-600 text-right">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($record?->items ?? [] as $item)
                <tr>
                    <td class="px-3 py-2 text-sm text-gray-900 font-medium">{{ $item->product_name }}</td>
                    <td class="px-3 py-2 text-sm text-gray-500 font-mono">{{ $item->product_sku }}</td>
                    <td class="px-3 py-2 text-sm text-gray-900 text-center">{{ $item->quantity }}</td>
                    <td class="px-3 py-2 text-sm text-gray-900 text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="px-3 py-2 text-sm text-gray-900 font-semibold text-right">${{ number_format($item->total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-3 py-4 text-sm text-gray-400 text-center">No items found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
