<?php

namespace Emojisushi\Api;

use Emojisushi\Api\Objects\Cart;
use Emojisushi\Api\Objects\CartProduct;
use Emojisushi\Api\Objects\CategoriesList;
use Emojisushi\Api\Objects\Category;
use Emojisushi\Api\Objects\CitiesList;
use Emojisushi\Api\Objects\City;
use Emojisushi\Api\Objects\PaymentMethod;
use Emojisushi\Api\Objects\PaymentMethodsList;
use Emojisushi\Api\Objects\PlaceOrderResponse;
use Emojisushi\Api\Objects\Product;
use Emojisushi\Api\Objects\ProductsList;
use Emojisushi\Api\Objects\ShipmentMethod;
use Emojisushi\Api\Objects\ShipmentMethodsList;
use Emojisushi\Api\Objects\Spot;
use Emojisushi\Api\Objects\SpotsList;
use Emojisushi\Api\Objects\Variant;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;

class EmojisushiApi {

    protected Client $guzzleClient;

    protected string $lang;

    protected HydratorInterface $hydrator;

    protected string $baseUrl;

    protected array $headers = [];

    protected HandlerStack $handler;

    public function init($conf) {
        $this->lang = $conf['lang'] ?? 'uk';
        $this->baseUrl = $conf['baseUrl'];
        $this->hydrator = new Hydrator();

        $this->handler = HandlerStack::create();

        $this->handler->push(Middleware::mapRequest(function (RequestInterface $request) {
            if(count($this->headers) > 0) {
                $request = Utils::modifyRequest($request, [
                    'set_headers' => $this->headers
                ]);
            }

            return Utils::modifyRequest($request, [
                'query' => ($query = $request->getUri()->getQuery()) .
                    (strlen($query) ? '&': '?') . 'lang=' . $this->lang
            ]);
        }));

        $baseConfig = [
            'base_uri' => $this->baseUrl,
            'handler' => $this->handler,
            'verify' => false
        ];

        $this->guzzleClient = new Client($baseConfig);
    }

    public function setHeader(string $name, string $value) {
        $this->headers[$name] = $value;
    }

    /**
     * @param array{slug_or_id: string|int} $params
     * @param array $guzzleOptions
     * @return City
     * @throws GuzzleException
     */
    public function getCity( array $params = [], array $guzzleOptions = []): City {
        $res = $this->guzzleClient->get(
            'city',
            array_merge_recursive($guzzleOptions, [
                'query' => $params,
            ]),
        );
        return $this->hydrator->hydrate(City::class, json_decode($res->getBody(), true));
    }

    /**
     * @param array{includeSpots?: bool,includeDistricts?:bool} $params
     * @param array $guzzleOptions
     * @return CitiesList
     * @throws GuzzleException
     */
    public function getCities( array $params = [], array $guzzleOptions = []): CitiesList {
        $res = $this->guzzleClient->get(
            'cities',
            array_merge_recursive($guzzleOptions, [
                'query' => $params,
            ]),
        );
        return $this->hydrator->hydrate(CitiesList::class, json_decode($res->getBody(), true));
    }

    /**
     * @param array{slug_or_id: string|int} $params
     * @param array $guzzleOptions
     * @return Spot
     * @throws GuzzleException
     */
    public function getSpot( array $params = [], array $guzzleOptions = []): Spot {
        $res = $this->guzzleClient->get(
            'spot',
            array_merge_recursive($guzzleOptions, [
                'query' => $params,
            ]),
        );
        return $this->hydrator->hydrate(Spot::class, json_decode($res->getBody(), true));
    }

    /**
     * @param array $params
     * @param array $guzzleOptions
     * @return SpotsList
     * @throws GuzzleException
     */
    public function getSpots( array $params = [], array $guzzleOptions = []): SpotsList {
        $res = $this->guzzleClient->get(
            'spots',
            array_merge_recursive($guzzleOptions, [
                'query' => [],
            ]),
        );
        return $this->hydrator->hydrate(SpotsList::class, json_decode($res->getBody(), true));
    }

    /**
     * @param array $params
     * @param array $guzzleOptions
     * @return CategoriesList
     * @throws GuzzleException
     */
    public function getCategories( array $params = [], array $guzzleOptions = []): CategoriesList {
        $res = $this->guzzleClient->get(
            'categories',
            array_merge_recursive($guzzleOptions, [
                'query' => [],
            ]),
        );
        return $this->hydrator->hydrate(
            CategoriesList::class,
            json_decode($res->getBody(), true)
        );
    }

    /**
     * @param array{id: string|int} $params
     * @param array $guzzleOptions
     * @return Category
     * @throws GuzzleException
     */
    public function getCategory(array $params = [], array $guzzleOptions = []): Category {
        $categories = $this->getCategories(
            ['limit' => 44543534],
            $guzzleOptions
        )->data;

        return collect($categories)->first(function($category) use($params) {
            return $params['id'] == $category->id;
        });
    }

    /**
     * @param array{offset?: string|int, limit?: string|int} $params
     * $param array $guzzleOptions
     * @return ProductsList
     * @throws GuzzleException
     */
    public function getProducts(array $params = [], array $guzzleOptions = []): ProductsList {
        $res = $this->guzzleClient->get(
            'products',
            array_merge_recursive($guzzleOptions, [
                'query' => array_merge($params, []),
            ]),
        );
        return $this->hydrator->hydrate(
            ProductsList::class,
            json_decode($res->getBody(), true)
        );
    }

    /**
     * @param array{product_id: string|int} $params
     * @param array $guzzleOptions
     * @return ?Product
     * @throws GuzzleException
     */
    public function getProduct(array $params = [], array $guzzleOptions = []): ?Product {
        $products = $this->getProducts(
            ['limit' => 44543534, 'category_slug' => 'menu'],
            $guzzleOptions
        )->data;
        return collect($products)->first(
            function(Product $product) use($params) { return $params['product_id'] == $product->id; }
        );
    }

    /**
     * @param array{variant_id: string|int} $params
     * @param array $guzzleOptions
     * @return ?Variant
     * @throws GuzzleException
     */
    public function getVariant(array $params = [], array $guzzleOptions = []): ?Variant {
        $products = $this->getProducts(['limit' => 44543534, 'category_slug' => 'menu'], $guzzleOptions)->data;

        /** @var Product $product */
        $product = collect($products)->first(function (Product $product) use($params) {
            return in_array($params['variant_id'], array_map(fn(Variant $variant) => $variant->id, $product->variants));
        });

        if(!$product) {
            return null;
        }

        return collect($product->variants)->first(
            fn(Variant $v) => $v->id === $params['variant_id']
        );
    }

    public function getCart(array $params = [], array $guzzleOptions = []): Cart {
        $res = $this->guzzleClient->get(
            'cart/products',
            array_merge_recursive($guzzleOptions, [
                'query' => $params
            ]),
        );
        return $this->hydrator->hydrate(Cart::class, json_decode($res->getBody(), true));
    }

    /**
     * @param array{product_id:string|int, quantity:string|int, variant_id:string|int} $params
     * @param array $guzzleOptions
     * @return Cart
     * @throws GuzzleException
     */
    public function addCartProduct(array $params = [], array $guzzleOptions = []): Cart {
        $res = $this->guzzleClient->post(
            'cart/add',
            array_merge_recursive($guzzleOptions, [
                'json' => $params
            ]),
        );
        return $this->hydrator->hydrate(Cart::class, json_decode($res->getBody(), true));
    }

    /**
     * @param array{cart_product_id: string|int } $params
     * @param array $guzzleOptions
     * @return mixed
     * @throws GuzzleException
     */
    public function removeFromCart(array $params = [], array $guzzleOptions = []): Cart {
        $res = $this->guzzleClient->post(
            'cart/remove',
            array_merge_recursive($guzzleOptions, [
                'json' => $params
            ]),
        );
        return $this->hydrator->hydrate(Cart::class, json_decode($res->getBody(), true));
    }

    /**
     * @param array{product_id: string|int, variant_id: string|int} $params
     * @param array $guzzleOptions
     * @return ?CartProduct
     * @throws GuzzleException
     */
    public function getCartProduct(array $params = [], array $guzzleOptions = []): ?CartProduct {
        $cart = $this->getCart([], $guzzleOptions);
        return collect($cart->data)->first(function(CartProduct $cartProduct) use($params) {
            if(isset($params['variant_id'])) {
                if($cartProduct->variant_id !== $params['variant_id']) {
                    return false;
                }
            }
            return $params['product_id'] === $cartProduct->product_id;
        });
    }

    /**
     * @param array{offset?: string|int, limit?: string|int} $params
     * @param array $guzzleOptions
     * @return PaymentMethodsList
     * @throws GuzzleException
     */
    public function getPaymentMethods(array $params = [], array $guzzleOptions = []): PaymentMethodsList {
        $res = $this->guzzleClient->get(
            'payments',
            array_merge_recursive($guzzleOptions, [
                'query' => $params
            ]),
        );
        return $this->hydrator->hydrate(PaymentMethodsList::class, json_decode($res->getBody(), true));
    }

    /**
     * @param array{offset?: string|int, limit?: string|int} $params
     * @param array $guzzleOptions
     * @return ShipmentMethodsList
     * @throws GuzzleException
     */
    public function getShippingMethods(array $params = [], array $guzzleOptions = []): ShipmentMethodsList {
        $res = $this->guzzleClient->get(
            'shipping',
            array_merge_recursive($guzzleOptions, [
                'query' => $params
            ]),
        );
        return $this->hydrator->hydrate(ShipmentMethodsList::class, json_decode($res->getBody(), true));
    }

    /**
     * @param array{id:string|int} $params
     * @param array $guzzleOptions
     * @return ?ShipmentMethod
     * @throws GuzzleException
     */
    public function getShippingMethod(array $params = [], array $guzzleOptions = []): ?ShipmentMethod {
        $methods = $this->getShippingMethods([
            'limit' => 4342342342
        ], $guzzleOptions)->data;

        return collect($methods)->first(function(ShipmentMethod $method) use($params) {
            return $method->id === $params['id'];
        });
    }

    /**
     * @param array{id:string|int} $params
     * @param array $guzzleOptions
     * @return ?PaymentMethod
     * @throws GuzzleException
     */
    public function getPaymentMethod(array $params = [], array $guzzleOptions = []): ?PaymentMethod {
        $methods = $this->getPaymentMethods([
            'limit' => 4342342342
        ], $guzzleOptions)->data;

        return collect($methods)->first(function(PaymentMethod $method) use($params) {
            return $method->id === $params['id'];
        });
    }

    /**
     * @param array $params
     * @param array $guzzleOptions
     * @return Cart
     * @throws GuzzleException
     */
    public function clearCart(array $params = [], array $guzzleOptions = []): Cart {
        $res = $this->guzzleClient->post(
            'cart/clear',
            array_merge_recursive($guzzleOptions, [
                'json' => $params
            ]),
        );
        return $this->hydrator->hydrate(Cart::class, json_decode($res->getBody(), true));
    }

    /**
     * @param array{phone: string, firstname?: string, lastname?: string, email?: string, shipping_method_id: int, payment_method_id: int, spot_id: int, address?: string, comment?: string, sticks?: int, change?: string} $params
     * @param array $guzzleOptions
     * @return Cart
     * @throws GuzzleException
     */
    public function placeOrder(array $params = [], array $guzzleOptions = []): PlaceOrderResponse {
        $res = $this->guzzleClient->post(
            'order/place',
            array_merge_recursive($guzzleOptions, [
                'json' => $params
            ]),
        );
        return $this->hydrator->hydrate(PlaceOrderResponse::class, json_decode($res->getBody(), true));
    }
}