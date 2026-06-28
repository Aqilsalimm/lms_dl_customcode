<?php

return [
    'groups' => [
        'public' => [
            'login', 'login.otp', 'register', 'forgot-password', 'reset-password', 'otp.send', 'otp.verify',
            'courses.index', 'courses.show', 'cart.index', 'cart.add', 'cart.remove', 'cart.clear',
            'blogs.index', 'blogs.show', 'language.switch',
        ],
        'student' => [
            'login', 'login.otp', 'register', 'forgot-password', 'reset-password', 'otp.send', 'otp.verify',
            'courses.index', 'courses.show', 'cart.index', 'cart.add', 'cart.remove', 'cart.clear',
            'blogs.index', 'blogs.show', 'language.switch',
            'dashboard', 'dashboard.enrolled-courses', 'dashboard.learning-progress', 'dashboard.reviews',
            'dashboard.quiz-attempts', 'dashboard.wishlist', 'dashboard.order-history', 'dashboard.qa',
            'wishlist.toggle', 'reviews.store', 'reviews.destroy', 'profile.edit', 'profile.update',
            'profile.destroy', 'profile.photo.update', 'profile.photo.destroy',
            'instructor.profile.setup', 'instructor.profile.store', 'payment.checkout', 'payment.mock-complete',
            'payment.cancel', 'cart.checkout', 'cart.checkout-page', 'checkout.resume', 'orders.invoice',
            'courses.learn', 'courses.lessons.content', 'courses.lessons.complete', 'courses.quizzes.complete',
            'courses.lessons.log-progress', 'notifications.read', 'notifications.read-all',
            'discussions.lesson', 'discussions.store', 'discussions.resolve', 'courses.certificate',
        ],
    ],
];
