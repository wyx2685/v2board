<?php

use App\Services\ThemeService;
use App\Models\Staff;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function (Request $request) {
    if (config('v2board.app_url') && config('v2board.safe_mode_enable', 0)) {
        if ($request->server('HTTP_HOST') !== parse_url(config('v2board.app_url'))['host']) {
            abort(403);
        }
    }

    $host = $request->server('HTTP_HOST');
    $staff = Staff::where('domain', $host)->where('status', 1)->first();

    $theme = config('v2board.frontend_theme', 'default');

    if (!config("theme.{$theme}")) {
        $themeService = new ThemeService($theme);
        $themeService->init();
    }

    $themeConfig = config("theme.{$theme}", []);

    if ($staff) {
        $themeConfig['background_url'] = $staff->background_url ?? '';
        $themeConfig['custom_html'] = $staff->custom_html ?? '';
    }

    $renderParams = [
        'title' => $staff->title ?? config('v2board.app_name', 'V2Board'),
        'logo' => $staff ? $staff->logo : config('v2board.logo'),
        'theme' => $theme,
        'version' => config('app.version'),
        'description' => $staff->description ?? config('v2board.app_description', 'V2Board is best'),
        'theme_config' => $themeConfig,
    ];

    return view("theme::{$theme}.dashboard", $renderParams);
});

//TODO:: 兼容
Route::get('/' . config('v2board.secure_path', config('v2board.frontend_admin_path', hash('crc32b', config('app.key')))), function () {
    return view('admin', [
        'title' => config('v2board.app_name', 'V2Board'),
        'theme_sidebar' => config('v2board.frontend_theme_sidebar', 'light'),
        'theme_header' => config('v2board.frontend_theme_header', 'dark'),
        'theme_color' => config('v2board.frontend_theme_color', 'default'),
        'background_url' => config('v2board.frontend_background_url'),
        'version' => config('app.version'),
        'logo' => config('v2board.logo'),
        'secure_path' => config('v2board.secure_path', config('v2board.frontend_admin_path', hash('crc32b', config('app.key'))))
    ]);
});

// Staff routes
$staffPath = config('v2board.staff_path', 'staff');

Route::get("/{$staffPath}/login", function () use ($staffPath) {
    return view('staff.login', ['staff_path' => $staffPath]);
});

Route::get("/{$staffPath}", function () use ($staffPath) {
    return view('staff.index', ['staff_path' => $staffPath]);
});

Route::get("/{$staffPath}/", function () use ($staffPath) {
    return view('staff.index', ['staff_path' => $staffPath]);
});

if (!empty(config('v2board.subscribe_path'))) {
    Route::get(config('v2board.subscribe_path'), 'V1\\Client\\ClientController@subscribe')->middleware('client');
}