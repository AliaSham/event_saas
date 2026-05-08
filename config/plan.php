<?php

function getPlanLimits($plan) {

    switch (strtolower($plan)) {

        case 'basic':
            return [
                'categories' => 1,
                'packages_per_category' => 1,
                'services' => 2,
                'bookings' => 1
            ];

        case 'standard':
            return [
                'categories' => 2,
                'packages_per_category' => 2,
                'services' => 5,
                'bookings' => 10
            ];

        case 'premium':
            return [
                'categories' => 999999,
                'packages_per_category' => 999999,
                'services' => 15,
                'bookings' => 999999
            ];

        default:
            return [
                'categories' => 0,
                'packages_per_category' => 0,
                'services' => 0,
                'bookings' => 0
            ];
    }
}