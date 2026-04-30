<?php

function getPlanLimits($plan) {

    switch ($plan) {

        case 'basic':
            return [
                'packages' => 1,
                'services' => 2,
                'bookings' => 1
            ];

        case 'standard':
            return [
                'packages' => 2,
                'services' => 5,
                'bookings' => 10
            ];

        case 'premium':
            return [
                'packages' => 999999,
                'services' => 15,
                'bookings' => 999999
            ];

        default:
            return [
                'packages' => 0,
                'services' => 0,
                'bookings' => 0
            ];
    }
}