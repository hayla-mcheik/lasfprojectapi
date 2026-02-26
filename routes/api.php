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

/*
|--------------------------------------------------------------------------
| FRONTEND (PUBLIC) CONTROLLERS
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Api\DestinationController;
use App\Http\Controllers\Api\SportController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\TestimonialController;

/*
|--------------------------------------------------------------------------
| ADMIN CONTROLLERS (ALIASED)
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
use App\Http\Controllers\Api\WeatherController as PublicWeatherController;
use App\Http\Controllers\Api\PublicPageController;
use App\Http\Controllers\PilotTeamController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (NUXT FRONTEND)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/flying-locations', [FlyingLocationController::class, 'index']);
Route::get('/flying-locations/{slug}', [FlyingLocationController::class, 'show']);
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{slug}', [NewsController::class, 'show']);
Route::get('/pages', [PageController::class, 'index']);
Route::get('/pages/{slug}', [PageController::class, 'show']);
Route::get('/qr/{token}', [AirspaceSessionController::class, 'qr']);
Route::get('/destinations', [DestinationController::class, 'index']);
Route::get('/destinations/{slug}', [DestinationController::class, 'show']);
Route::get('/sports', [SportController::class, 'index']);
Route::get('/pilots', [PilotTeamController::class, 'index']);
Route::get('/gallery', [GalleryController::class, 'index']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{slug}', [EventController::class, 'show']);
Route::get('/testimonials', [TestimonialController::class, 'index']);
Route::get('/airspace-sessions/active', [AirspaceSessionController::class, 'active']);
Route::get('/about-us', [PublicPageController::class, 'getAbout']);
Route::get('/regulations', [PublicPageController::class, 'getRegulations']);
Route::get('/weather-report', [PublicWeatherController::class, 'index']);
/*
|--------------------------------------------------------------------------
| SHARED ADMIN & ARMY ROUTES
|--------------------------------------------------------------------------
| These routes are accessible by both Super Admins and the Lebanese Army.
*/
Route::middleware(['auth:sanctum', 'army_access'])
    ->prefix('admin')
    ->group(function () {
        
        // Shared Dashboard Stats
        Route::get('/dashboard', [DashboardController::class, 'stats']);
        
        // Shared Flying Locations Management
        Route::apiResource('flying-locations', AdminFlyingLocationController::class);
        Route::get('flying-locations/regions', [AdminFlyingLocationController::class, 'regions']);
        Route::post('flying-locations/{flyingLocation}/generate-qr', [AdminFlyingLocationController::class, 'generateQR']);
        Route::get('flying-locations/{flyingLocation}/qr-codes', [AdminFlyingLocationController::class, 'getQRCodes']);
    });

/*
|--------------------------------------------------------------------------
| SUPER ADMIN ONLY ROUTES (CMS)
|--------------------------------------------------------------------------
| These routes are strictly for Super Admins. Army users are blocked here.
*/
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {
        
        // Pilots Management (Admin Only)
        Route::get('pilots/export', [AdminPilotController::class, 'export']);
        Route::post('pilots/import', [AdminPilotController::class, 'import']);
        Route::apiResource('pilots', AdminPilotController::class);
// Inside the Admin middleware group
Route::get('weather', [AdminWeatherController::class, 'index']);
Route::put('weather/{id}', [AdminWeatherController::class, 'update']);
        // Content Management
        Route::apiResource('sports', AdminSportController::class);
        Route::apiResource('news', AdminNewsController::class);
        Route::put('news/{news}/toggle-publish', [AdminNewsController::class, 'togglePublish']);
        Route::apiResource('news-categories', AdminNewsCategoryController::class);
        Route::apiResource('pages', AdminPageController::class);
        Route::apiResource('clearance-statuses', AdminClearanceStatusController::class);
        Route::apiResource('destinations', AdminDestinationController::class);
        Route::apiResource('events', AdminEventController::class);
        Route::apiResource('testimonials', AdminTestimonialController::class);
        
        // Gallery
        Route::apiResource('gallery', AdminGalleryController::class)->only(['index', 'store', 'destroy']);
        Route::put('gallery/{gallery}', [AdminGalleryController::class, 'update']);

        // Page Content
        Route::post('/about-us', [PageContentController::class, 'updateAbout']);
        Route::post('/regulations', [PageContentController::class, 'storeRegulation']);
        Route::put('/regulations/{regulation}', [PageContentController::class, 'updateRegulation']);
        Route::delete('/regulations/{regulation}', [PageContentController::class, 'destroyRegulation']);
    });

/*
|--------------------------------------------------------------------------
| AUTHENTICATED USER ROUTES (PILOTS)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::get('/airspace-sessions/active-pilot', [AirspaceSessionController::class, 'userActiveSession']);
    Route::post('/airspace-sessions', [AirspaceSessionController::class, 'store']);
    Route::post('/airspace-sessions/{id}/checkout', [AirspaceSessionController::class, 'checkout']);
});