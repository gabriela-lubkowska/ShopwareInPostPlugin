<?php

declare(strict_types=1);

namespace BitBag\ShopwareInPostPlugin\Factory\Package;

use BitBag\ShopwareInPostPlugin\Exception\OrderException;
use BitBag\ShopwareInPostPlugin\Provider\Defaults;
use BitBag\ShopwareInPostPlugin\Resolver\OrderShippingAddressResolverInterface;
use Shopware\Core\Checkout\Order\OrderEntity;

final class ReceiverPayloadFactory implements ReceiverPayloadFactoryInterface
{
    private OrderShippingAddressResolverInterface $orderShippingAddressResolver;

    public function __construct(OrderShippingAddressResolverInterface $orderShippingAddressResolver)
    {
        $this->orderShippingAddressResolver = $orderShippingAddressResolver;
    }

    public function create(OrderEntity $order): array
    {
        $orderCustomer = $order->getOrderCustomer();

        if (null === $orderCustomer) {
            throw new OrderException('order.customerEmailNotFound');
        }

        $orderShippingAddress = $this->orderShippingAddressResolver->resolve($order);

        $phoneNumber = $orderShippingAddress->getPhoneNumber();

        if (null === $phoneNumber) {
            throw new OrderException('order.nullPhoneNumber');
        }

        [, $street, $houseNumber] = $this->splitStreet($orderShippingAddress->getStreet());

        return [
            'company_name' => $orderShippingAddress->getCompany(),
            'first_name' => $orderShippingAddress->getFirstName(),
            'last_name' => $orderShippingAddress->getLastName(),
            'email' => $orderCustomer->getEmail(),
            'phone' => $this->replaceString($phoneNumber),
            'address' => [
                'street' => $street,
                'building_number' => $houseNumber,
                'city' => $orderShippingAddress->getCity(),
                'post_code' => $orderShippingAddress->getZipcode(),
                'country_code' => Defaults::CURRENCY_CODE,
            ],
        ];
    }

    private function splitStreet(string $street): array
    {
        if (!preg_match('/^([^\d]*[^\d\s]) *(\d.*)$/', $street, $streetAddress)) {
            throw new OrderException('Street cannot be split');
        }

        return $streetAddress;
    }

    private function replaceString(string $value): string
    {
        return str_replace(['-', ' '], '', $value);
    }
}