<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH & CORE FRONTEND CONTROLLERS
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FlyingLocationController;
use App\Http\Controllers\Api\AirspaceSessionController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\MembershipCardController;

/*
|--------------------------------------------------------------------------
| FRONTEND PUBLIC CONTROLLERS
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\DestinationController;
use App\Http\Controllers\Api\SportController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Api\PilotLocationController;
use App\Http\Controllers\Api\PilotSafetyMessageController;
use App\Http\Controllers\Api\WeatherController as PublicWeatherController;
use App\Http\Controllers\Api\PublicPageController;
use App\Http\Controllers\PilotTeamController;

/*
|--------------------------------------------------------------------------
| ADMIN CONTROLLERS
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\Admin\FlyingLocationController as AdminFlyingLocationController;
use App\Http\Controllers\Api\Admin\SportController as AdminSportController;
use App\Http\Controllers\Api\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Api\Admin\NewsCategoryController as AdminNewsCategoryController;
use App\Http\Controllers\Api\Admin\PageController as AdminPageController;
use App\Http\Controllers\Api\Admin\ClearanceStatusController as AdminClearanceStatusController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\DestinationController as AdminDestinationController;
use App\Http\Controllers\Api\Admin\EventController as AdminEventController;
use App\Http\Controllers\Api\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Api\Admin\PageContentController;
use App\Http\Controllers\Api\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Api\Admin\PilotController as AdminPilotController;
use App\Http\Controllers\Api\Admin\WeatherController as AdminWeatherController;
use App\Http\Controllers\Api\Admin\PilotSafetyMessageController as AdminPilotSafetyMessageController;
use App\Http\Controllers\Api\CrossCountryRequestController;
use App\Http\Controllers\Api\CrossCountrySessionController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\Admin\FeedbackController as AdminFeedbackController;
/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/login', [
    AuthController::class,
    'login',
])->name('login');


Route::post('/register', [
    AuthController::class,
    'register',
]);


/*
|--------------------------------------------------------------------------
| FLYING LOCATIONS
|--------------------------------------------------------------------------
*/

Route::get('/flying-locations', [
    FlyingLocationController::class,
    'index',
]);


Route::get('/flying-locations/{slug}', [
    FlyingLocationController::class,
    'show',
]);


/*
|--------------------------------------------------------------------------
| NEWS
|--------------------------------------------------------------------------
*/

Route::get('/news', [
    NewsController::class,
    'index',
]);


Route::get('/news/{slug}', [
    NewsController::class,
    'show',
]);


/*
|--------------------------------------------------------------------------
| PAGES
|--------------------------------------------------------------------------
*/

Route::get('/pages', [
    PageController::class,
    'index',
]);


Route::get('/pages/{slug}', [
    PageController::class,
    'show',
]);


/*
|--------------------------------------------------------------------------
| QR CODE
|--------------------------------------------------------------------------
*/

Route::get('/qr/{token}', [
    AirspaceSessionController::class,
    'qr',
]);


/*
|--------------------------------------------------------------------------
| DESTINATIONS
|--------------------------------------------------------------------------
*/

Route::get('/destinations', [
    DestinationController::class,
    'index',
]);


Route::get('/destinations/{slug}', [
    DestinationController::class,
    'show',
]);


/*
|--------------------------------------------------------------------------
| SPORTS
|--------------------------------------------------------------------------
*/

Route::get('/sports', [
    SportController::class,
    'index',
]);


/*
|--------------------------------------------------------------------------
| PUBLIC PILOTS
|--------------------------------------------------------------------------
*/

Route::get('/pilots', [
    PilotTeamController::class,
    'index',
]);


Route::get('/pilot/{license}', [
    PilotTeamController::class,
    'show',
]);


/*
|--------------------------------------------------------------------------
| GALLERY
|--------------------------------------------------------------------------
*/

Route::get('/gallery', [
    GalleryController::class,
    'index',
]);


/*
|--------------------------------------------------------------------------
| EVENTS
|--------------------------------------------------------------------------
*/

Route::get('/events', [
    EventController::class,
    'index',
]);


Route::get('/events/{slug}', [
    EventController::class,
    'show',
]);


/*
|--------------------------------------------------------------------------
| TESTIMONIALS
|--------------------------------------------------------------------------
*/

Route::get('/testimonials', [
    TestimonialController::class,
    'index',
]);


/*
|--------------------------------------------------------------------------
| PUBLIC CONTENT
|--------------------------------------------------------------------------
*/

Route::get('/about-us', [
    PublicPageController::class,
    'getAbout',
]);


Route::get('/regulations', [
    PublicPageController::class,
    'getRegulations',
]);


Route::get('/weather-report', [
    PublicWeatherController::class,
    'index',
]);


Route::get('/pilot-safety-message', [
    PilotSafetyMessageController::class,
    'index',
]);


/*
|--------------------------------------------------------------------------
| DASHBOARD + LIVE TRACKING
|--------------------------------------------------------------------------
|
| ADMIN:
|   Dashboard        YES
|   Live Tracking    YES
|
| ARMY:
|   Dashboard        YES
|   Live Tracking    YES
|
| WATCHER:
|   Dashboard        YES
|   Live Tracking    YES
|
| PILOT:
|   NO ACCESS
|
*/

Route::middleware([
    'auth:sanctum',
    'dashboard_access',
])
    ->prefix('admin')
    ->group(function () {

        Route::get('/dashboard', [
            DashboardController::class,
            'stats',
        ]);


        Route::get('/gps/live', [
            PilotLocationController::class,
            'liveAll',
        ]);


        Route::get('/gps/live/{locationId}', [
            PilotLocationController::class,
            'live',
        ]);
    });


/*
|--------------------------------------------------------------------------
| ADMIN + ARMY LOCATION MANAGEMENT
|--------------------------------------------------------------------------
|
| ADMIN:
|   Manage locations      YES
|   View QR codes         YES
|   Generate QR codes     YES
|
| ARMY:
|   Manage locations      YES
|   View QR codes         YES
|   Generate QR codes     YES
|
| WATCHER:
|   NO ACCESS
|
*/

Route::middleware([
    'auth:sanctum',
    'army_access',
])
    ->prefix('admin')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT:
        | Static routes must be before apiResource.
        |--------------------------------------------------------------------------
        */

        Route::get(
            'flying-locations/regions',
            [
                AdminFlyingLocationController::class,
                'regions',
            ]
        );


        Route::post(
            'flying-locations/{flyingLocation}/generate-qr',
            [
                AdminFlyingLocationController::class,
                'generateQR',
            ]
        );


        Route::get(
            'flying-locations/{flyingLocation}/qr-codes',
            [
                AdminFlyingLocationController::class,
                'getQRCodes',
            ]
        );


        Route::apiResource(
            'flying-locations',
            AdminFlyingLocationController::class
        );
    });


/*
|--------------------------------------------------------------------------
| ADMIN + ARMY PILOT VIEWING
|--------------------------------------------------------------------------
|
| ADMIN:
|   View pilots        YES
|   View pilot         YES
|   View licenses      YES
|   Download license   YES
|
| ARMY:
|   View pilots        YES
|   View pilot         YES
|   View licenses      YES
|   Download license   YES
|
| WATCHER:
|   NO ACCESS
|
*/

Route::middleware([
    'auth:sanctum',
    'pilot_view_access',
])
    ->prefix('admin')
    ->group(function () {

        Route::get('pilots', [
            AdminPilotController::class,
            'index',
        ]);


        Route::get(
            'pilots/{pilot}/licenses',
            [
                AdminPilotController::class,
                'licenses',
            ]
        );


        Route::get(
            'pilots/{pilot}/licenses/{index}',
            [
                AdminPilotController::class,
                'downloadLicense',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Keep dynamic pilot route LAST.
        |--------------------------------------------------------------------------
        */

        Route::get('pilots/{pilot}', [
            AdminPilotController::class,
            'show',
        ]);
    });


/*
|--------------------------------------------------------------------------
| ADMIN + ARMY DAILY CLEARANCE PERMISSIONS
|--------------------------------------------------------------------------
|
| Both Admin and Army can:
|
|   - View daily permissions
|   - View one permission
|   - View history
|
| We will add the exact "request/respond" workflow afterward.
|
*/

Route::middleware([
    'auth:sanctum',
    'army_access',
])
    ->prefix('admin')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT:
        | History must be before {clearanceStatus}.
        |--------------------------------------------------------------------------
        */

        Route::get(
            'clearance-statuses/history',
            [
                AdminClearanceStatusController::class,
                'history',
            ]
        );


        Route::get(
            'clearance-statuses',
            [
                AdminClearanceStatusController::class,
                'index',
            ]
        );


        Route::get(
            'clearance-statuses/{clearanceStatus}',
            [
                AdminClearanceStatusController::class,
                'show',
            ]
        );
    });


/*
|--------------------------------------------------------------------------
| SUPER ADMIN ONLY ROUTES
|--------------------------------------------------------------------------
|
| Only Super Admin can:
|
|   - Create/update/delete pilots
|   - Approve/reject pilots
|   - Import/export pilots
|   - Create/update/delete clearance permissions
|   - Manage CMS
|   - Manage weather
|   - Manage safety message
|
*/

Route::middleware([
    'auth:sanctum',
    'admin',
])
    ->prefix('admin')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | PILOT MANAGEMENT
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Static routes before dynamic {pilot}.
        |
        */

        Route::get('pilots/export', [
            AdminPilotController::class,
            'export',
        ]);


        Route::post('pilots/import', [
            AdminPilotController::class,
            'import',
        ]);


        Route::patch(
            'pilots/{pilot}/approve',
            [
                AdminPilotController::class,
                'approve',
            ]
        );


        Route::patch(
            'pilots/{pilot}/reject',
            [
                AdminPilotController::class,
                'reject',
            ]
        );


        Route::post('pilots', [
            AdminPilotController::class,
            'store',
        ]);


        Route::put('pilots/{pilot}', [
            AdminPilotController::class,
            'update',
        ]);


        Route::patch('pilots/{pilot}', [
            AdminPilotController::class,
            'update',
        ]);


        Route::delete('pilots/{pilot}', [
            AdminPilotController::class,
            'destroy',
        ]);

Route::get('/feedback', [
    AdminFeedbackController::class,
    'index',
]);

Route::get('/feedback/{feedback}', [
    AdminFeedbackController::class,
    'show',
]);

Route::patch('/feedback/{feedback}', [
    AdminFeedbackController::class,
    'update',
]);

        /*
        |--------------------------------------------------------------------------
        | DAILY CLEARANCE MANAGEMENT
        |--------------------------------------------------------------------------
        */

        Route::post(
            'clearance-statuses',
            [
                AdminClearanceStatusController::class,
                'store',
            ]
        );


        Route::put(
            'clearance-statuses/{clearanceStatus}',
            [
                AdminClearanceStatusController::class,
                'update',
            ]
        );


        Route::patch(
            'clearance-statuses/{clearanceStatus}',
            [
                AdminClearanceStatusController::class,
                'update',
            ]
        );


        Route::delete(
            'clearance-statuses/{clearanceStatus}',
            [
                AdminClearanceStatusController::class,
                'destroy',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | WEATHER
        |--------------------------------------------------------------------------
        */

        Route::get('weather', [
            AdminWeatherController::class,
            'index',
        ]);


        Route::put('weather/{id}', [
            AdminWeatherController::class,
            'update',
        ]);


        /*
        |--------------------------------------------------------------------------
        | PILOT SAFETY MESSAGE
        |--------------------------------------------------------------------------
        */

        Route::get('pilot-safety-message', [
            AdminPilotSafetyMessageController::class,
            'index',
        ]);


        Route::put('pilot-safety-message', [
            AdminPilotSafetyMessageController::class,
            'update',
        ]);


        /*
        |--------------------------------------------------------------------------
        | SPORTS
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'sports',
            AdminSportController::class
        );


        /*
        |--------------------------------------------------------------------------
        | NEWS
        |--------------------------------------------------------------------------
        */

        Route::put(
            'news/{news}/toggle-publish',
            [
                AdminNewsController::class,
                'togglePublish',
            ]
        );


        Route::apiResource(
            'news',
            AdminNewsController::class
        );


        /*
        |--------------------------------------------------------------------------
        | NEWS CATEGORIES
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'news-categories',
            AdminNewsCategoryController::class
        );


        /*
        |--------------------------------------------------------------------------
        | PAGES
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'pages',
            AdminPageController::class
        );


        /*
        |--------------------------------------------------------------------------
        | DESTINATIONS
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'destinations',
            AdminDestinationController::class
        );


        /*
        |--------------------------------------------------------------------------
        | EVENTS
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'events',
            AdminEventController::class
        );


        /*
        |--------------------------------------------------------------------------
        | TESTIMONIALS
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'testimonials',
            AdminTestimonialController::class
        );


        /*
        |--------------------------------------------------------------------------
        | GALLERY
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'gallery',
            AdminGalleryController::class
        )->only([
            'index',
            'store',
            'destroy',
        ]);


        Route::put(
            'gallery/{gallery}',
            [
                AdminGalleryController::class,
                'update',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | PAGE CONTENT
        |--------------------------------------------------------------------------
        */

        Route::post('/about-us', [
            PageContentController::class,
            'updateAbout',
        ]);


        Route::post('/regulations', [
            PageContentController::class,
            'storeRegulation',
        ]);


        Route::put(
            '/regulations/{regulation}',
            [
                PageContentController::class,
                'updateRegulation',
            ]
        );


        Route::delete(
            '/regulations/{regulation}',
            [
                PageContentController::class,
                'destroyRegulation',
            ]
        );
    });


/*
|--------------------------------------------------------------------------
| AUTHENTICATED USER ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | CURRENT USER
        |--------------------------------------------------------------------------
        */

        Route::get('/user', function (Request $request) {
            return $request->user();
        });

Route::post(
    '/cross-country-requests',
    [CrossCountryRequestController::class, 'store']
);

Route::get(
    '/cross-country-requests',
    [CrossCountryRequestController::class, 'index']
);
Route::get(
    '/cross-country-requests/{crossCountryRequest}',
    [CrossCountryRequestController::class, 'show']
);
Route::patch(
    '/cross-country-requests/{crossCountryRequest}/cancel',
    [CrossCountryRequestController::class, 'cancel']
);
Route::get(
    '/admin/cross-country-requests',
    [CrossCountryRequestController::class, 'adminIndex']
);
Route::patch(
    '/admin/cross-country-requests/{crossCountryRequest}/status',
    [CrossCountryRequestController::class, 'updateStatus']
);
Route::get(
    '/cross-country/history',
    [CrossCountryRequestController::class, 'history']
);
Route::get(
    '/admin/cross-country-requests/{crossCountryRequest}',
    [CrossCountryRequestController::class, 'adminShow']
);
Route::post(
    '/cross-country-requests/{crossCountryRequest}/start',
    [CrossCountrySessionController::class, 'start']
);
Route::post(
    '/cross-country-sessions/{crossCountrySession}/finish',
    [CrossCountrySessionController::class, 'finish']
);
Route::get(
    '/cross-country/statistics',
    [CrossCountrySessionController::class, 'statistics']
);
Route::get(
    '/cross-country-sessions/{crossCountrySession}/track',
    [CrossCountrySessionController::class, 'track']
);
Route::get(
    '/cross-country-sessions/active',
    [CrossCountrySessionController::class, 'active']
);

        /*
        |--------------------------------------------------------------------------
        | MEMBERSHIP CARD
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/membership-card/download',
            [
                MembershipCardController::class,
                'download',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | GPS
        |--------------------------------------------------------------------------
        */

        Route::post('/gps/update', [
            PilotLocationController::class,
            'update',
        ]);


        /*
        |--------------------------------------------------------------------------
        | MEMBERSHIP PROFILE
        |--------------------------------------------------------------------------
        */

        Route::get('/my-membership', [
            AuthController::class,
            'myMembership',
        ]);


        Route::put('/my-membership', [
            AuthController::class,
            'updateMembership',
        ]);


        /*
        |--------------------------------------------------------------------------
        | USER PROFILE
        |--------------------------------------------------------------------------
        */

        Route::put('/user/profile', [
            AuthController::class,
            'updateProfile',
        ]);


        /*
        |--------------------------------------------------------------------------
        | AIRSPACE SESSIONS
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/airspace-sessions/active-pilot',
            [
                AirspaceSessionController::class,
                'userActiveSession',
            ]
        );


        Route::post(
            '/airspace-sessions',
            [
                AirspaceSessionController::class,
                'store',
            ]
        );


        Route::post(
            '/airspace-sessions/{id}/checkout',
            [
                AirspaceSessionController::class,
                'checkout',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | LOGOUT
        |--------------------------------------------------------------------------
        */

        Route::post('/logout', [
            AuthController::class,
            'logout',
        ]);
    });
    Route::post('/feedback', [FeedbackController::class, 'store']);