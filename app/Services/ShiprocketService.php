<?php

namespace App\Services;

use App\Models\ShiprocketCredential;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShiprocketService
{
    protected $baseUrl;
    protected $token;
    protected $userId;

    public function __construct()
    {
        $this->baseUrl = "https://apiv2.shiprocket.in/v1/external/";
        $this->userId = Auth::id();
        $this->token = $this->getAuthToken();
    }

    protected function getAuthToken($forceRefresh = false)
    {
        $cacheKey = 'shiprocket_token_' . $this->userId;

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $credential = ShiprocketCredential::where('user_id', $this->userId)->first();

        return Cache::remember($cacheKey, 3600, function () {
            $credential = ShiprocketCredential::where('user_id', $this->userId)->first();

            if (!$credential) {
                throw new \Exception('Shiprocket credentials not found for this user.');
            }

            $response = Http::post($this->baseUrl . 'auth/login', [
                'email'    => $credential->email,
                'password' => $credential->password,
            ]);

            if ($response->successful()) {
                $token = $response->json('token');

                // \Log::info('Shiprocket token fetched: ' . $token);

                return $token;
            }

            // \Log::error('Shiprocket authentication failed', [
            //     'response' => $response->body()
            // ]);

            throw new \Exception('Shiprocket authentication failed: ' . $response->body());
        });
    }

    protected function request($method, $endpoint, $data = [])
    {
        try {
            $url = $this->baseUrl . $endpoint;

            if (strtolower($method) === 'get') {
                // Send as query parameters
                $response = Http::withToken($this->token)->get($url, $data);
            } else {
                // POST, PUT, etc.
                $response = Http::withToken($this->token)->{$method}($url, $data);
            }

            if ($response->status() === 401) {
                // Log::warning('Shiprocket token expired, refreshing...');
                $this->token = $this->getAuthToken(true);
                if (strtolower($method) === 'get') {
                    $response = Http::withToken($this->token)->get($url, $data);
                } else {
                    $response = Http::withToken($this->token)->{$method}($url, $data);
                }
            }

            if ($response->successful()) {
                return $response->json();
            }

            // Log::error("Shiprocket {$method} request failed", [
            //     'endpoint' => $endpoint,
            //     'data' => $data,
            //     'response' => $response->body()
            // ]);

            throw new \Exception('Shiprocket request failed: ' . $response->body());
        } catch (\Exception $e) {
            // Log::error('Shiprocket request exception', [
            //     'endpoint' => $endpoint,
            //     'data' => $data,
            //     'error' => $e->getMessage()
            // ]);
            throw $e;
        }
    }


    public function createAdhocOrder($data)
    {
        return $this->request('post', 'orders/create/adhoc', $data);
    }

    public function getOrderStatus($shiprocket_order_id)
    {
        try {
            return $this->request('get', 'orders/show/' . $shiprocket_order_id);
        } catch (\Exception $e) {
            // Log::error("Shiprocket getOrderStatus failed", [
            //     'order_id' => $shiprocket_order_id,
            //     'error' => $e->getMessage()
            // ]);
            return null;
        }
    }

    public function getOrderDetails($shiprocketOrderId)
    {
        return $this->request('get', "orders/show/{$shiprocketOrderId}");
    }

    public function getCourierServiceability($pickupPincode, $deliveryPincode, $weight, $length, $breadth, $height, $cod)
    {
        return $this->request('get', 'courier/serviceability/', [
            'pickup_postcode' => $pickupPincode,
            'delivery_postcode' => $deliveryPincode,
            'weight' => $weight,
            'length' => $length,
            'breadth' => $breadth,
            'height' => $height,
            'cod'    => $cod
        ]);
    }

    // Generate AWB
    public function generateAWB($shipmentId, $courierId)
    {
        // dd($courierId);
        // dd($shipmentId);
        return $this->request('post', 'courier/assign/awb', [
            'shipment_id' => $shipmentId,
            'courier_id' => $courierId
        ]);
    }

    // Get label URL
    public function getLabel($shipmentId)
    {
        return $this->request('post', 'courier/generate/label', [
            'shipment_id' => [$shipmentId]
        ]);
    }

    public function getManifest($shipmentId)
    {
        return $this->request('post', 'manifests/generate', [
            'shipment_id' => [$shipmentId]
        ]);
    }

    public function getShipmentDetails($shipmentId)
    {
        return $this->request('get', "shipments/{$shipmentId}");
    }

    public function requestPickup(array $shipmentIds)
    {
        return $this->request(
            'post',
            'courier/generate/pickup',
            [
                'shipment_id' => $shipmentIds
            ]
        );
    }
}
