<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShiprocketCredentialRequest;
use CoreComponentRepository;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\PickupAddress;
use App\Models\ShippingBoxSize;
use App\Models\ShiprocketCredential;
use App\Services\ShiprocketService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShiprocketController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:manage_shipping_system'])->only('update');
    }

    public function update(ShiprocketCredentialRequest $request)
    {
        $user_id = Auth::id();

        $credential = ShiprocketCredential::firstOrNew(['user_id' => $user_id]);

        $credential->user_id  = $user_id;
        $credential->email    = $request->SHIPROCKET_EMAIL;
        $credential->password = $request->SHIPROCKET_PASSWORD;
        $credential->save();

        return response()->json([
            'success' => true,
            'message'  => translate('Shiprocket Credential has been updated successfully'),
            'redirect' => url()->previous()
        ]);
    }

    public function createOrderShiprocket(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'pickup_address_id' => 'required|exists:pickup_addresses,id',
            'shipping_box_size_id' => 'required|exists:shipping_box_sizes,id'
        ]);

        $order = Order::with('user', 'orderDetails.product.stocks')->findOrFail($request->order_id);
        $pickupAddress = PickupAddress::findOrFail($request->pickup_address_id);
        $boxSizes = ShippingBoxSize::findOrFail($request->shipping_box_size_id);

        $payment_method = $order->payment_status === 'paid' ? "Prepaid" : "COD";

        try {

            $service = new ShiprocketService();
            // dd($service);
            // Safe JSON decode
            $shippingAddress = json_decode($order->shipping_address ?? '{}') ?: (object)[];
            $billingAddress  = json_decode($order->billing_address ?? '{}') ?: (object)[];

            // Defensive fallbacks
            $customerName = $shippingAddress->name ?? ($order->user->name ?? 'Customer');
            $customerPhone = $shippingAddress->phone ?? $order->user->phone ?? '';
            $customerEmail = $shippingAddress->email ?? $order->user->email ?? '';

            $totalWeight = 0;
            $orderItems  = [];

            foreach ($order->orderDetails as $detail) {

                $product = $detail->product;
                $stock = $product?->stocks?->first();

                $quantity = (int) $detail->quantity;
                $unitWeight = $product->weight ?? 0.1;
                $totalWeight += max($unitWeight * $quantity, 0.1);

                $orderItems[] = [
                    "name" => $product->name ?? 'Product',
                    "sku" => $stock->sku ?? 'NA-' . strtoupper(Str::random(12)),
                    "units" => $quantity,
                    "selling_price" => round($detail->price / max($quantity, 1), 2),
                    "discount" => "",
                    "tax" => "",
                    "hsn" => $product->hsn_code ?? "000000"
                ];
            }

            $data = [
                "order_id" => "ORDER-" . now()->timestamp . $order->id,
                "order_date" => now()->format('Y-m-d H:i'),
                "pickup_location" => $pickupAddress->address_nickname,
                "comment" => "",
                "reseller_name" => "",
                "company_name" => env('APP_NAME') ?? 'Active Ecommerce CMS',

                // Billing
                "billing_customer_name" => $customerName,
                "billing_last_name" => "",
                "billing_address" => $billingAddress->address,
                "billing_address_2" => "",
                "billing_isd_code" => "",
                "billing_city" => $billingAddress->city,
                "billing_pincode" => $billingAddress->postal_code,
                "billing_state" => $billingAddress->state,
                "billing_country" => $billingAddress->country,
                "billing_email" => $customerEmail,
                "billing_phone" => $customerPhone,
                "billing_alternate_phone" => "",

                // Shipping same as billing
                "shipping_is_billing" => false,
                "shipping_customer_name" => $shippingAddress->name,
                "shipping_last_name" => "",
                "shipping_address" => $shippingAddress->address,
                "shipping_address_2" => "",
                "shipping_city" => $shippingAddress->city,
                "shipping_pincode" => $shippingAddress->postal_code,
                "shipping_state" => $shippingAddress->state,
                "shipping_country" => $shippingAddress->country,
                "shipping_email" => $shippingAddress->email,
                "shipping_phone" => $shippingAddress->phone,

                // Items
                "order_items" => $orderItems,

                // Pricing
                "payment_method" => $payment_method,
                "shipping_charges" => "",
                "giftwrap_charges" => "",
                "transaction_charges" => "",
                "total_discount" => 0.0,
                "sub_total" => $order->grand_total,

                // Dimensions
                "length" => $boxSizes->length,
                "breadth" => $boxSizes->breadth,
                "height" => $boxSizes->height,
                "weight" => max($totalWeight, 0.1),

                // Optional
                "ewaybill_no" => "",
                "customer_gstin" => "",
                "invoice_number" => "INV-" . now()->timestamp,
                "order_type" => ""
            ];

            // dd($data);

            $result = $service->createAdhocOrder($data);

            $order->shipping_method = 'shiprocket';
            $order->pickup_address_id = $pickupAddress->id;
            $order->shiprocket_order_id = $result['order_id'] ?? null;
            $order->shiprocket_shipment_id = $result['shipment_id'] ?? null;
            $order->shiprocket_status_code = $result['status_code'] ?? 0;
            $order->shiprocket_status = Str::lower($result['status']) ?? '';
            $order->delivery_status = 'confirmed';
            $order->save();

            return response()->json([
                'success' => true,
                'message' => translate('Order created in Shiprocket successfully'),
                'data' => [
                    'order_id' => $order->shiprocket_order_id,
                    'shipment_id' => $order->shiprocket_shipment_id
                ]
            ]);
        } catch (\Exception $e) {

            // Log::error('Shiprocket order creation failed', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);

            return response()->json([
                'success' => false,
                'message' => translate('Shiprocket order creation failed'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deliveryStatus()
    {
        $orders = Order::where('shipping_method', 'shiprocket')
            ->where('delivery_status', '!=', 'delivered')
            // ->where('updated_at', '<=', Carbon::now()->subHour())
            ->whereNotNull('shiprocket_order_id')
            ->get();

        try {
            $service = new ShiprocketService();

            foreach ($orders as $order) {

                $shipOrderId = $order->shiprocket_order_id;

                $statusInfo = $service->getOrderStatus($shipOrderId);

                if (!$statusInfo) continue;
                $status = $statusInfo['data']['status'] ?? null;
                $status_code = $statusInfo['data']['status_code'] ?? null;

                if ($status_code) {
                    $shiprocketStatusMap = [

                        'confirmed' => [
                            1,  
                            2,  
                            3,  
                            4,  
                            5,
                            11, 
                            27, 
                            52, 
                        ],

                        'picked_up' => [
                            19, 
                            42, 
                            15, 
                            20, 
                            13, 
                        ],

                        'on_the_way' => [
                            6,  
                            17, 
                            18, 
                            21, 
                            22, 
                            38, 
                            39, 
                            48, 
                            49, 
                            50, 
                            51, 
                            54, 
                            55, 
                            56, 
                            57, 
                            59, 
                            68, 
                            71,
                            72,
                        ],

                        'delivered' => [
                            7,  
                            23, 
                            26, 
                            43, 
                        ],

                        'cancelled' => [
                            8,  
                            16,
                            9,  
                            14, 
                            10, 
                            40, 
                            41, 
                            46, 
                            45, 
                            12, 
                            24,
                            25, 
                            44, 
                            47, 
                            75,
                            76, 
                            77, 
                            78, 
                        ],
                    ];

                    $changed  = false;

                    foreach ($shiprocketStatusMap as $parentStatus => $childCodes) {
                        if (in_array($status_code, $childCodes, true)) {

                            if ($order->delivery_status !== $parentStatus) {
                                $order->delivery_status = $parentStatus;
                                $changed = true;
                            }

                            break;
                        }
                    }

                    if (
                        in_array($status_code, [7, 23], true) &&
                        $order->payment_status !== 'paid'
                    ) {
                        $order->payment_status = 'paid';
                        $changed = true;
                    }

                    if ($order->shiprocket_status_code !== $status_code) {
                        $order->shiprocket_status_code = $status_code;
                        $changed = true;
                    }

                    $statusLower = $status ? strtolower($status) : null;
                    if ($order->shiprocket_status !== $statusLower) {
                        $order->shiprocket_status = $statusLower;
                        $changed = true;
                    }

                    if ($changed) {
                        $order->save();
                    }

                    // Log::info("Order Status Updated", [
                    //     "order_id" => $order->id,
                    //     "shiprocket_order_id" => $shipOrderId,
                    //     "shiprocket_status" => $status,
                    //     "mapped_status" => $order->delivery_status,
                    //     "status_code" => $status_code
                    // ]);
                }
            }

            return response()->json([
                "success" => true,
                "message" => "Delivery status updated successfully"
            ]);
        } catch (\Exception $e) {

            // Log::error("Shiprocket status update failed", [
            //     "error" => $e->getMessage()
            // ]);

            return response()->json([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }

    public function getCouriers(Request $request)
    {
        $request->validate(['order_id' => 'required|exists:orders,id']);
        $order = Order::findOrFail($request->order_id);

        if (!$order->shiprocket_order_id || !$order->shiprocket_shipment_id) {
            // Log::warning('Shiprocket IDs missing', ['order_id' => $order->id]);
            return response()->json(['success' => false, 'message' => 'Shiprocket order not found.']);
        }

        try {
            $service = new ShiprocketService();

            // Log::info('Fetching Shiprocket order details', [
            //     'shiprocket_order_id' => $order->shiprocket_order_id
            // ]);

            $shipOrder = $service->getOrderDetails($order->shiprocket_order_id);

            if (!isset($shipOrder['data'])) {
                // Log::error('Invalid Shiprocket order response', $shipOrder);
                return response()->json(['success' => false, 'message' => 'Invalid order data from Shiprocket.']);
            }

            $pickupPincode = $shipOrder['data']['pickup_code'] ?? null;
            $deliveryPincode = $shipOrder['data']['customer_pincode'] ?? null;

            $shipment = $shipOrder['data']['shipments'] ?? [];
            $weight = $shipment['weight'];
            $dims = explode('x', $shipment['dimensions']);
            $length = floatval($dims[0]);
            $breadth = floatval($dims[1]);
            $height = floatval($dims[2]);

            // Log::info('Serviceability params', compact('pickupPincode', 'deliveryPincode', 'weight', 'length', 'breadth', 'height'));

            if (!$pickupPincode || !$deliveryPincode) {
                // Log::error('Missing pincode', compact('pickupPincode', 'deliveryPincode'));
                return response()->json(['success' => false, 'message' => 'Missing pickup or delivery pincode.']);
            }

            $cod = $order->payment_status !== 'paid';

            $response = $service->getCourierServiceability(
                $pickupPincode,
                $deliveryPincode,
                $weight,
                $length,
                $breadth,
                $height,
                $cod
            );

            // dd($response);
            if (empty($response['data']['available_courier_companies'])) {
                // Log::warning('No couriers available', $response);
                return response()->json(['success' => false, 'message' => 'No couriers available for this route.']);
            }

            $couriers = collect($response['data']['available_courier_companies'])
                ->map(fn($c) => [
                    'id' => $c['courier_company_id'],
                    'name' => $c['courier_name']
                ])
                ->values();

            // Log::info('Couriers fetched successfully', ['count' => $couriers->count()]);

            return response()->json(['success' => true, 'couriers' => $couriers]);
        } catch (\Exception $e) {
            // Log::error('getCouriers failed', [
            //     'order_id' => $order->id,
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch couriers: ' . $e->getMessage()]);
        }
    }

    public function assignAWB(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'courier_id' => 'required|integer'
        ]);
// dd($request);
        $order = Order::findOrFail($request->order_id);

        if (!$order->shiprocket_shipment_id) {
            return response()->json(['success' => false, 'message' => 'Shipment ID missing.']);
        }

        try {
            $service = new ShiprocketService();

            // Generate AWB
            $awbResp = $service->generateAWB($order->shiprocket_shipment_id, (int) $request->courier_id);
            if (!($awbResp['awb_assign_status'] ?? false)) {
                return response()->json(['success' => false, 'message' => $awbResp['message'] ?? 'AWB assignment failed.']);
            }

            // Get updated shipment for AWB code
            $shipment = $service->getShipmentDetails($order->shiprocket_shipment_id);
            $awbCode = $shipment['data']['awb'] ?? null;
            $awbAssignDate = $shipment['data']['awb_assigned_date'] ?? null;
            $courier = $shipment['data']['courier'] ?? null;
            $courier_id = $shipment['data']['sr_courier_id'] ?? null;

            $order->shiprocket_awb = $awbCode;
            $order->shiprocket_courier_id = $courier_id;
            $order->shiprocket_courier_name = $courier;
            $order->awb_assigned_at = $awbAssignDate;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'AWB generated successfully!',
                'awb_code' => $awbCode
            ]);
        } catch (\Exception $e) {
            // \Log::error('AWB generation failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'AWB generation failed.']);
        }
    }

    public function downloadLabel(Order $order)
    {
        if (!$order->shiprocket_shipment_id) {
            abort(404, 'Shipment ID missing');
        }

        $service = new ShiprocketService();
        $response = $service->getLabel($order->shiprocket_shipment_id);

        if (!empty($response['label_url'])) {

            $order->shiprocket_label_url = $response['label_url'];
            $order->save();

            return redirect()->away($response['label_url']);
        }

        abort(404, 'Label not available yet.');
    }

    public function downloadManifest(Order $order)
    {
        if (!$order->shiprocket_shipment_id) {
            abort(404, 'Shipment ID missing');
        }

        $service = new ShiprocketService();
        $response = $service->getManifest($order->shiprocket_shipment_id);

        if (!empty($response['manifest_url'])) {

            $order->shiprocket_manifest_url = $response['manifest_url'];
            $order->save();

            return redirect()->away($response['manifest_url']);
        }

        abort(404, 'Manifest not available yet.');
    }

    public function requestPickup(Request $request)
    {
        $order = Order::findOrFail($request->order_id);

        if (!$order->shiprocket_shipment_id || !$order->shiprocket_awb) {
            return response()->json([
                'success' => false,
                'message' => 'AWB not generated yet'
            ]);
        }

        if ($order->pickup_scheduled_at) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup already requested'
            ]);
        }

        try {
            $service = new ShiprocketService();

            // Log::info('Pickup request payload', [
            //     'shipment_id' => $order->shiprocket_shipment_id
            // ]);

            $response = $service->requestPickup([
                $order->shiprocket_shipment_id
            ]);

            // Log::info('Pickup API response', $response);

            // --- Extract values safely ---
            $pickupStatus = $response['pickup_status'] ?? 0;
            $pickupData   = $response['response']['data'] ?? null;
            $pickupToken  = $response['response']['pickup_token_number'] ?? null;

            // Prefer scheduled date
            $pickupDateRaw =
                $response['response']['pickup_scheduled_date']
                ?? $response['response']['pickup_generated_date']['date']
                ?? null;

            if (
                $pickupStatus == 1 ||
                ($pickupData && str_contains(strtolower($pickupData), 'pickup'))
            ) {
                $order->pickup_scheduled_at = $pickupDateRaw
                    ? Carbon::parse($pickupDateRaw)
                    : null;

                $order->pickup_token = $pickupToken;
                $order->save();

                return response()->json([
                    'success' => true,
                    'message' => $pickupData ?? 'Pickup requested successfully',
                    'pickup_at' => $order->pickup_scheduled_at
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $pickupData ?? 'Pickup request failed'
            ]);
        } catch (\Exception $e) {

            // Log::error('Pickup request exception', [
            //     'order_id' => $order->id,
            //     'error' => $e->getMessage()
            // ]);

            return response()->json([
                'success' => false,
                'message' => 'Pickup request failed',
                'error' => $e->getMessage()
            ]);
        }
    }
}
