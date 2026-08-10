<?php

declare(strict_types=1);

const TM_FREE_GIFT_MIN_INR = 199.0;
const TM_FREE_SHIPPING_MIN_INR = 499.0;
const TM_DISCOUNT_MIN_INR = 699.0;
const TM_DISCOUNT_PERCENT = 12.0;
const TM_SHIPPING_FEE_INR = 70.0;

function tm_order_qualifies_free_gift(float $itemsSubtotal): bool
{
    return $itemsSubtotal >= TM_FREE_GIFT_MIN_INR;
}

function tm_order_shipping_inr(float $itemsSubtotal): float
{
    if ($itemsSubtotal <= 0) {
        return 0.0;
    }

    return $itemsSubtotal >= TM_FREE_SHIPPING_MIN_INR ? 0.0 : TM_SHIPPING_FEE_INR;
}

function tm_order_discount_inr(float $itemsSubtotal): float
{
    if ($itemsSubtotal < TM_DISCOUNT_MIN_INR) {
        return 0.0;
    }

    return round($itemsSubtotal * TM_DISCOUNT_PERCENT / 100, 2);
}

function tm_order_charge_total_inr(float $itemsSubtotal, float $couponDiscountInr = 0.0): float
{
    $milestoneDiscount = tm_order_discount_inr($itemsSubtotal);
    $discount = $couponDiscountInr > 0 ? min($itemsSubtotal, $couponDiscountInr) : $milestoneDiscount;
    $afterDiscount = $itemsSubtotal - $discount;
    $shipping = tm_order_shipping_inr($itemsSubtotal);

    return round($afterDiscount + $shipping, 2);
}

/**
 * @param array{productId?: string|int, name?: string}|null $freeGift
 * @return list<string>
 */
function tm_order_reward_notes(float $itemsSubtotal, ?array $freeGift = null, ?string $couponCode = null, float $couponDiscount = 0.0): array
{
    $notes = [];
    if (tm_order_qualifies_free_gift($itemsSubtotal)) {
        $giftName = isset($freeGift['name']) ? trim((string) $freeGift['name']) : '';
        $giftId = isset($freeGift['productId']) ? trim((string) $freeGift['productId']) : '';
        if ($giftName !== '' && $giftId !== '') {
            $notes[] = 'Free gift chosen: ' . substr($giftName, 0, 200) . ' (product ' . substr($giftId, 0, 32) . ')';
        } else {
            $notes[] = 'Free gift: qualified (order ₹' . (int) TM_FREE_GIFT_MIN_INR . '+) — no gift selected';
        }
    }
    $shipping = tm_order_shipping_inr($itemsSubtotal);
    $notes[] = $shipping > 0
        ? 'Shipping: ₹' . (int) $shipping
        : 'Shipping: free (order ₹' . (int) TM_FREE_SHIPPING_MIN_INR . '+)';
    if ($couponCode !== null && $couponCode !== '' && $couponDiscount > 0) {
        $notes[] = 'Coupon: ' . strtoupper($couponCode) . ' (−₹' . (int) round($couponDiscount) . ')';
    } else {
        $discount = tm_order_discount_inr($itemsSubtotal);
        if ($discount > 0) {
            $notes[] = 'Discount: 12% (−₹' . (int) round($discount) . ')';
        }
    }

    return $notes;
}
