<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\Admin\RegistrationManagementController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Tenant\RoomRequestController;
use App\Http\Controllers\Admin\RoomRequestManagementController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Tenant\BillingController as TenantBillingController;
use App\Http\Controllers\Admin\ManualPaymentController;
use App\Http\Controllers\Admin\BillingItemController;
use App\Http\Controllers\Admin\AdvancePaymentController;
use App\Http\Controllers\Admin\AdvanceCreditController;

/*
|--------------------------------------------------------------------------
| DEFAULT ROUTE
|--------------------------------------------------------------------------
*/

Route::view('/', 'welcome')->name('home');

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

Route::get('/login', [
    AuthController::class,
    'showLogin'
])->name('login');

Route::post('/login', [
    AuthController::class,
    'login'
])
    ->middleware('throttle:10,1')
    ->name('login.submit');

/*
|--------------------------------------------------------------------------
| EMAIL OTP VERIFICATION
|--------------------------------------------------------------------------
*/

Route::get('/verify-otp', [
    AuthController::class,
    'showOtpForm'
])->name('otp.form');

Route::post('/verify-otp', [
    AuthController::class,
    'verifyOtp'
])
    ->middleware('throttle:5,1')
    ->name('otp.verify');

Route::post('/resend-otp', [
    AuthController::class,
    'resendOtp'
])
    ->middleware('throttle:3,1')
    ->name('otp.resend');

/*
|--------------------------------------------------------------------------
| PASSWORD RESET
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [
        PasswordResetController::class,
        'showRequestForm'
    ])->name('password.otp.request');

    Route::post('/forgot-password', [
        PasswordResetController::class,
        'sendOtp'
    ])->middleware('throttle:3,1')->name('password.otp.send');

    Route::get('/reset-password', [
        PasswordResetController::class,
        'showResetForm'
    ])->name('password.otp.form');

    Route::post('/reset-password', [
        PasswordResetController::class,
        'reset'
    ])->middleware('throttle:5,1')->name('password.otp.reset');
});

/*
|--------------------------------------------------------------------------
| PUBLIC REGISTRATION REQUEST
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get('/register', [
        RegistrationController::class,
        'index'
    ])->name('register');

    Route::post('/register', [
        RegistrationController::class,
        'store'
    ])
        ->middleware('throttle:3,1')
        ->name('register.store');

});

/*
|--------------------------------------------------------------------------
| TENANT REGISTRATION COMPLETION
|--------------------------------------------------------------------------
| Outside auth/guest groups.
| The controller validates the invitation token.
|--------------------------------------------------------------------------
*/

Route::get('/complete-registration/{token}', [
    RegistrationController::class,
    'showCompletion'
])
    ->where('token', '[a-f0-9]{64}')
    ->name('registration.complete');

Route::post('/complete-registration/{token}', [
    RegistrationController::class,
    'completeRegistration'
])
    ->where('token', '[a-f0-9]{64}')
    ->middleware('throttle:5,1')
    ->name('registration.complete.store');

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | GENERAL DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [
        DashboardController::class,
        'index'
    ])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | ADMIN ROUTES
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            // ADMIN DASHBOARD
            Route::get('/dashboard', [
                DashboardController::class,
                'admin'
            ])->name('dashboard');

            /*
            |--------------------------------------------------------------------------
            | REGISTRATION MANAGEMENT
            |--------------------------------------------------------------------------
            */

            Route::get('/registration-requests', [
                RegistrationManagementController::class,
                'index'
            ])->name('registrations.index');

            Route::post('/registration-requests/{registrationRequest}/approve', [
                RegistrationManagementController::class,
                'approve'
            ])->name('registrations.approve');

            Route::post('/registration-requests/{registrationRequest}/reject', [
                RegistrationManagementController::class,
                'reject'
            ])->name('registrations.reject');

            Route::post('/registration-requests/{registrationRequest}/resend', [
                RegistrationManagementController::class,
                'resend'
            ])
                ->middleware('throttle:3,1')
                ->name('registrations.resend');

            /*
            |--------------------------------------------------------------------------
            | ROOM MANAGEMENT
            |--------------------------------------------------------------------------
            */

            // VIEW ROOMS
            Route::get('/rooms', [
                RoomController::class,
                'index'
            ])->name('rooms.index');

            // ADD ROOM
            Route::post('/rooms', [
                RoomController::class,
                'store'
            ])
                ->middleware('throttle:10,1')
                ->name('rooms.store');

            // EDIT ROOM FORM
            Route::get('/rooms/{room}/edit', [
                RoomController::class,
                'edit'
            ])->name('rooms.edit');

            // UPDATE ROOM
            Route::put('/rooms/{room}', [
                RoomController::class,
                'update'
            ])->name('rooms.update');

            // ARCHIVE ROOM
            Route::patch('/rooms/{room}/archive', [
                RoomController::class,
                'archive'
            ])
                ->middleware('throttle:10,1')
                ->name('rooms.archive');

            // RESTORE ROOM
            Route::patch('/rooms/{room}/restore', [
                RoomController::class,
                'restore'
            ])
                ->middleware('throttle:10,1')
                ->name('rooms.restore');
                
            // VIEW ROOM REQUESTS
            Route::get('/room-requests', [
                RoomRequestManagementController::class,
                'index'
            ])->name('room-requests.index');

            // APPROVE ROOM REQUEST
            Route::patch('/room-requests/{roomRequest}/approve', [
                RoomRequestManagementController::class,
                'approve'
            ])
                ->middleware('throttle:10,1')
                ->name('room-requests.approve');

            // REJECT ROOM REQUEST
            Route::patch('/room-requests/{roomRequest}/reject', [
                RoomRequestManagementController::class,
                'reject'
            ])
                ->middleware('throttle:10,1')
                ->name('room-requests.reject');
            
            // BILLING MANAGEMENT

            // View monthly bills
            Route::get('/billings', [
                BillingController::class,
                'index'
            ])->name('billings.index');

            // Generate a tenant's monthly bill
            Route::post('/billings', [
                BillingController::class,
                'store'
            ])
                ->middleware('throttle:10,1')
                ->name('billings.store');
            
            
            Route::get('/billings/{billing}/manual-payment', [
                ManualPaymentController::class,
                'show'
            ])->name('billings.manual.show');

            Route::post('/billings/{billing}/manual-payment', [
                ManualPaymentController::class,
                'store'
            ])
                ->middleware('throttle:5,1')
                ->name('billings.manual.store');
            
                /*
                |--------------------------------------------------------------------------
                | BILLING ITEM MANAGEMENT
                |--------------------------------------------------------------------------
                */

                // Display Edit Charges page
                Route::get('/billings/{billing}/edit', [
                    BillingItemController::class,
                    'edit'
                ])->name('billings.edit');

                // Add or update billing charges
                Route::put('/billings/{billing}/items', [
                    BillingItemController::class,
                    'save'
                ])
                    ->middleware('throttle:10,1')
                    ->name('billings.items.save');

                // Remove Electricity or Water charge
                Route::delete('/billings/{billing}/items/{itemType}', [
                    BillingItemController::class,
                    'destroy'
                ])
                    ->where('itemType', 'Electricity|Water')
                    ->name('billings.items.remove');


                // Record an advance payment for a tenant
                Route::post('/advance-payments', [
                    AdvancePaymentController::class,
                    'store'
                ])
                    ->middleware('throttle:5,1')
                    ->name('advance-payments.store');
                Route::get('/advance-payments', [
                    AdvancePaymentController::class,
                    'index'
                ])->name('advance-payments.index');
        Route::get(
            '/billings/{billing}/advance-credit',
            [AdvanceCreditController::class, 'show']
        )->name('billings.advance-credit.show');

        Route::post(
            '/billings/{billing}/advance-credit',
            [AdvanceCreditController::class, 'store']
        )
            ->middleware('throttle:5,1')
            ->name('billings.advance-credit.store');
        });
       
/*
|--------------------------------------------------------------------------
| Multi-Select Edit Billing Charges
|--------------------------------------------------------------------------
| Standalone route with explicit Admin middleware.
*/

Route::put(
    '/admin/billings/{billing}/items/bulk',
    [
        \App\Http\Controllers\Admin\BillingItemController::class,
        'saveSelected'
    ]
)
->middleware(['auth', 'role:admin'])
->name('admin.billings.items.bulk');

    /*
    |--------------------------------------------------------------------------
    | TENANT ROUTES
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:tenant')
        ->prefix('tenant')
        ->name('tenant.')
        ->group(function () {

            Route::get('/dashboard', [
                DashboardController::class,
                'tenant'
            ])->name('dashboard');
            
            // TENANT ROOM BROWSING
            Route::get('/rooms', [
                RoomRequestController::class,
                'index'
            ])->name('rooms.index');

            // TENANT ROOM REQUEST SUBMISSION
            Route::post('/room-requests', [
                RoomRequestController::class,
                'store'
            ])
                ->middleware('throttle:5,1')
                ->name('room-requests.store');
            // TENANT BILLING DASHBOARD
            Route::get('/billings', [
                TenantBillingController::class,
                'index'
            ])->name('billings.index');


        });

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', [
        AuthController::class,
        'logout'
    ])->name('logout');

});


/*
|--------------------------------------------------------------------------
| PAYMONGO INTEGRATION
|--------------------------------------------------------------------------
*/

// Tenant checkout — authentication required
Route::post(
    '/tenant/billings/{billing}/checkout',
    [\App\Http\Controllers\Tenant\CheckoutController::class, 'store']
)->middleware(['auth', 'role:tenant'])
 ->name('tenant.billings.checkout');


// PayMongo webhook — publicly accessible
// Security handled by webhook signature verification
Route::post(
    '/webhooks/paymongo',
    [\App\Http\Controllers\PayMongoWebhookController::class, 'handle']
)->name('paymongo.webhook');

