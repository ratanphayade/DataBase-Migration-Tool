<?php

function getIsApprovedStatus($old)
{
    $isApproved = [
        '0' => 'pending',
        '1' => 'approved',
        '2' => 'rejected',
    ];
    return (isset($isApproved[$old])) ? $isApproved[$old] : NULL;
}

function getRedemptionTypeFromCoupon($key)
{
    $redemptionType = [
        'normal' => 'normal',
        'merchantApp' => 'app-merchant',
        'appExclusive' => 'app-exclusive',
        'social' => 'social'
    ];

    return ($key !== NULL && isset($key)) ? $redemptionType[$key] : NULL;
}

function getOfferTypeFromCoupon($key)
{
    $redemptionType = [
        'Exclusive' => 'exclusive',
        'Card Offer' => 'bank-card',
        'General' => NULL
    ];

    return (isset($redemptionType[$key])) ? $redemptionType[$key] : NULL;
}

function getGenderFromCoupon($key)
{
    $gender = [
        '0' => NULL,
        '1' => 'male',
        '2' => 'female',
        '3' => 'unisex'
    ];

    return ($key !== NULL && isset($key)) ? $gender[$key] : NULL;
}

function getDiscountTypeFromCoupon($key)
{
    $discountType = [
        'Percentage' => 'percentage',
        'Amount' => 'value',
        'FreeItem' => 'free-item',
        'Cashback' => 'cashback'
    ];

    return ($key !== NULL && isset($key)) ? $discountType[$key] : NULL;
}

?>