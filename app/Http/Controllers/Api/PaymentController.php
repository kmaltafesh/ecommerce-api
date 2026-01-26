<?php

namespace App\Http\Controllers\Api;

use App\Enum\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Exception;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class PaymentController extends Controller
{
    public function __construct()
    {
        // ضبط مفتاح الربط مع Stripe
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createPayment(Request $request, Order $order)
    {
        // 1. التحقق من صحة المزود المرسل
        $request->validate([
            'provider' => ['required', Rule::enum(PaymentProvider::class)],
        ]);

        // 2. التحقق من الملكية لضمان الأمان
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 3. التحقق من إمكانية الدفع (مثلاً: ليس مدفوعاً أو ملغى)
        if (!$order->canAcceptPayment()) {
            return response()->json(['message' => 'Order status does not allow payment'], 400);
        }

        $provider = PaymentProvider::from($request->provider);

        return match ($provider) {
            PaymentProvider::STRIPE => $this->createStripePayment($order),
            default => response()->json(['message' => 'Provider not supported'], 501),
        };
    }

    private function createStripePayment(Order $order)
    {
        try {
            // إنشاء نية الدفع (PaymentIntent)
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($order->total * 100), // تحويل المبلغ للسنتات
                'currency' => strtolower($order->currency ?? 'usd'),
                'metadata' => ['order_id' => $order->id],
            ], [
                // منع تكرار العملية في حال إعادة الإرسال (Idempotency)
                'idempotency_key' => 'order_pay_' . $order->id,
            ]);

            // تسجيل عملية الدفع في قاعدة بياناتك بحالة "قيد الانتظار"
            $order->payments()->create([
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $order->total,
                'status' => 'pending', 
                'provider' => PaymentProvider::STRIPE,
            ]);

            return response()->json([
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return response()->json(['message' => 'Payment Gateway Error'], 502);
        }
    }

    public function stripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature'); 
        $webhookSecret = config('services.stripe.webhook.secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    return $this->handleSuccessfulPayment($event->data->object);
                case 'payment_intent.payment_failed':
                    return $this->handleFailedPayment($event->data->object);
                default:
                    return response()->json(['status' => 'ignored']);
            }
        } catch (UnexpectedValueException | SignatureVerificationException $e) {
            Log::error('Stripe Webhook Error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid Request'], 400);
        }
    }

    protected function handleSuccessfulPayment($paymentIntent)
    {
        $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();

        if ($payment && !$payment->isFinal()) {
            $payment->markAsCompleted([
                'stripe_data' => [
                    'amount' => $paymentIntent->amount / 100,
                    'status' => $paymentIntent->status,
                    'completed_at' => now()->toIso8601String(),
                ]
            ]);
            return response()->json(['success' => true]);
        }
        
        return response()->json(['message' => 'Payment not found or already processed'], 404);
    }

    protected function handleFailedPayment($paymentIntent)
    {
        $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();

        if ($payment && !$payment->isFinal()) {
            $payment->markAsFailed([
                'error' => $paymentIntent->last_payment_error?->message ?? 'Unknown error'
            ]);
        }
        return response()->json(['success' => true]);
    }
}