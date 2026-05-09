<?php

function getPlanLimits($plan) {

    switch (strtolower($plan)) {

        case 'basic':
            return [
                'categories' => 1,
                'packages_per_category' => 1,

                // Admin/company boleh pilih max 2 vendor untuk ditawarkan
                'services' => 2,

                // Customer boleh pilih max 1 add-on/vendor masa booking
                'addons_per_booking' => 1,

                'bookings' => 1
            ];

        case 'standard':
            return [
                'categories' => 2,
                'packages_per_category' => 2,

                // Admin/company boleh pilih max 5 vendor untuk ditawarkan
                'services' => 5,

                // Customer boleh pilih max 3 add-on/vendor masa booking
                'addons_per_booking' => 3,

                'bookings' => 10
            ];

        case 'premium':
            return [
                'categories' => 999999,
                'packages_per_category' => 999999,

                // Admin/company boleh pilih max 15 vendor untuk ditawarkan
                'services' => 15,

                // Customer boleh pilih max 5 add-on/vendor masa booking
                'addons_per_booking' => 5,

                'bookings' => 999999
            ];

        default:
            return [
                'categories' => 0,
                'packages_per_category' => 0,
                'services' => 0,
                'addons_per_booking' => 0,
                'bookings' => 0
            ];
    }
}