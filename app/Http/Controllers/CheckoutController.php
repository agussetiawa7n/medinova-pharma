<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payment\PaymentGatewayManager;
use App\Models\Order;
use App\Models\Address;
use App\Enums\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cart,
        private OrderService $orderService,
        private PaymentGatewayManager $paymentManager
    ) {}

    public function index()
    {
        $cartData  = $this->cart->getCartData();
        if (empty($cartData['items'])) {
            return redirect()->route('cart')->with('error', 'Your cart is empty.');
        }

        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $addresses = $user->addresses()->latest()->get();
        $wallet    = $user->wallet;

        $enabledMethods = $this->paymentManager->availableMethods();

        return view('checkout.index', array_merge($cartData, [
            'addresses'      => $addresses,
            'wallet'         => $wallet,
            'paymentMethods' => PaymentMethod::cases(),
            'enabledMethods' => $enabledMethods,
            'total'          => $cartData['grand_total'],
        ]));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user          = Auth::user();
        $walletBalance = (float) ($user->wallet?->balance ?? 0);
        $paymentValues = collect(PaymentMethod::cases())->pluck('value')->implode(',');

        $validated = $request->validate([
            'address_id'       => 'nullable|integer|exists:addresses,id,user_id,' . $user->id,
            'shipping_name'    => 'required_without:address_id|string|max:100',
            'shipping_phone'   => 'required_without:address_id|string|max:20',
            'shipping_address' => 'required_without:address_id|string|max:255',
            'shipping_city'    => 'required_without:address_id|string|max:100',
            'shipping_state'   => 'required_without:address_id|string|max:100',
            'shipping_pincode' => 'required_without:address_id|string|max:10',
            'payment_method'   => 'required|string|in:' . $paymentValues,
            'wallet_amount'    => ['nullable', 'numeric', 'min:0', 'max:' . $walletBalance],
            'notes'            => 'nullable|string|max:500',
        ]);

        $cartData = $this->cart->getCartData();
        if (empty($cartData['items'])) {
            return redirect()->route('cart')->with('error', 'Cart is empty.');
        }

        // Build shipping details
        if ($request->address_id) {
            $address = Address::findOrFail($request->address_id);
            Gate::authorize('owns-address', $address);
            $shippingData = [
                'shipping_name'           => trim(($address->first_name ?? '') . ' ' . ($address->last_name ?? '')),
                'shipping_phone'          => $address->phone,
                'shipping_address_line_1' => $address->address_line_1,
                'shipping_address_line_2' => $address->address_line_2,
                'shipping_city'           => $address->city,
                'shipping_state'          => $address->state,
                'shipping_postal_code'    => $address->postal_code,
                'shipping_country'        => $address->country ?? 'India',
            ];
        } else {
            $shippingData = [
                'shipping_name'           => $request->shipping_name,
                'shipping_phone'          => $request->shipping_phone,
                'shipping_address_line_1' => $request->shipping_address,
                'shipping_city'           => $request->shipping_city,
                'shipping_state'          => $request->shipping_state,
                'shipping_postal_code'    => $request->shipping_pincode,
                'shipping_country'        => 'India',
            ];
        }

        $paymentMethod = PaymentMethod::from($request->payment_method);

        // Wallet validation
        if ($paymentMethod === PaymentMethod::Wallet) {
            $wallet = \App\Models\Wallet::where('user_id', Auth::id())->first();
            if (!$wallet || $wallet->balance <= 0) {
                return back()->withErrors(['payment_method' => 'Wallet balance is empty. Please top up or choose another payment method.']);
            }
            $walletAmount = $this->cart->getSubtotal();
        } else {
            $walletAmount = (float) $request->input('wallet_amount', 0);
        }

        $order = $this->orderService->createOrder(array_merge($shippingData, [
            'payment_method' => $paymentMethod,
            'wallet_amount'  => $walletAmount,
            'notes'          => $request->input('notes'),
        ]));

        // COD / Wallet: mark as paid (wallet already debited in createOrder)
        if (in_array($paymentMethod, [PaymentMethod::Wallet])) {
            $this->orderService->markPaid($order, 'wallet');
        }
        if (in_array($paymentMethod, [PaymentMethod::COD, PaymentMethod::Wallet])) {
            return redirect()->route('checkout.success', $order);
        }

        // Online payment gateways
        $gateway = $this->paymentManager->driver($paymentMethod);
        $payload = $gateway->createPayment(
            (float) $order->total,
            $order->currency,
            ['order_id' => $order->id, 'order_number' => $order->order_number]
        );

        // PayPal: redirect directly to approval URL
        if ($paymentMethod === PaymentMethod::PayPal && !empty($payload['approve_url'])) {
            session(['paypal_order_id' => $order->id]);
            return redirect($payload['approve_url']);
        }

        return view('checkout.payment', compact('order', 'payload'));
    }

    public function success(Order $order)
    {
        Gate::authorize('owns-order', $order);
        return view('checkout.success', compact('order'));
    }

    public function handlePaymentCallback(string $gateway, Request $request)
    {
        $order = match ($gateway) {
            'razorpay' => Order::findOrFail($request->order_id ?? $request->notes['order_id'] ?? null),
            'stripe'   => Order::where('payment_reference', $request->payment_intent)->firstOrFail(),
            'paypal'   => Order::findOrFail(session('paypal_order_id')),
            default    => abort(400, 'Unknown payment gateway.'),
        };

        Gate::authorize('owns-order', $order);

        if (!$this->paymentManager->driver($gateway)->verifyPayment($request->all())) {
            return redirect()->route('checkout')->with('error', 'Payment verification failed. Please try again or contact support.');
        }

        $paymentId = match ($gateway) {
            'razorpay' => $request->razorpay_payment_id ?? '',
            'stripe'   => $request->payment_intent ?? '',
            'paypal'   => $request->token ?? '',
            default    => '',
        };
        $this->orderService->markPaid($order, $paymentId);

        return redirect()->route('checkout.success', $order);
    }

    public function handlePaymentCancel(string $gateway)
    {
        $message = match ($gateway) {
            'razorpay' => 'Razorpay payment cancelled.',
            'stripe'   => 'Stripe payment cancelled.',
            'paypal'   => 'PayPal payment cancelled.',
            default    => 'Payment cancelled.',
        };

        return redirect()->route('checkout')->with('error', $message);
    }
}
