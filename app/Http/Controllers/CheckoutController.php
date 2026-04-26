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

        return view('checkout.index', array_merge($cartData, [
            'addresses'       => $addresses,
            'wallet'          => $wallet,
            'paymentMethods'  => PaymentMethod::cases(),
            'total'           => $cartData['grand_total'],
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
            abort_unless($address->user_id === Auth::id(), 403);
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
        $order = $this->orderService->createOrder(array_merge($shippingData, [
            'payment_method' => $paymentMethod,
            'wallet_amount'  => (float) $request->input('wallet_amount', 0),
            'notes'          => $request->input('notes'),
        ]));

        // COD, Wallet, and Wallet+Partial (wallet portion already debited in createOrder)
        if (in_array($paymentMethod, [PaymentMethod::COD, PaymentMethod::Wallet, PaymentMethod::WalletPartial])) {
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
        abort_unless($order->user_id === Auth::id(), 403);
        return view('checkout.success', compact('order'));
    }

    public function razorpayCallback(Request $request)
    {
        $gateway = $this->paymentManager->driver('razorpay');
        $order   = Order::findOrFail($request->order_id ?? $request->notes['order_id'] ?? null);
        $gateway->verifyPayment($request->all());
        return redirect()->route('checkout.success', $order);
    }

    public function stripeSuccess(Request $request)
    {
        $order = Order::where('payment_reference', $request->payment_intent)->firstOrFail();
        abort_unless($order->user_id === Auth::id(), 403);
        $this->paymentManager->driver('stripe')->verifyPayment($request->all());
        return redirect()->route('checkout.success', $order);
    }

    public function paypalSuccess(Request $request)
    {
        $order = Order::findOrFail(session('paypal_order_id'));
        abort_unless($order->user_id === Auth::id(), 403);
        $this->paymentManager->driver('paypal')->verifyPayment($request->all());
        return redirect()->route('checkout.success', $order);
    }

    public function paypalCancel()
    {
        return redirect()->route('checkout')->with('error', 'PayPal payment cancelled.');
    }
}
