<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Models\ConfigSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laracasts\Flash\Flash;

class ConfigSettingController extends AppBaseController
{
    public function index(Request $request)
    {
        if (!OperationsController::isAdmin()) {
            Flash::error('Access denied. Admin only.');
            return redirect('/');
        }

        $query = ConfigSetting::query();

        // Filter by category
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('value', 'like', "%{$search}%");
            });
        }

        $settings = $query->orderBy('category')->orderBy('key')->get();
        $categories = ConfigSetting::$categories;
        $types = ConfigSetting::$types;

        return view('config_settings.index', compact('settings', 'categories', 'types'));
    }

    public function update(Request $request, $id)
    {
        if (!OperationsController::isAdmin()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $setting = ConfigSetting::findOrFail($id);

        $request->validate([
            'value' => 'nullable|string|max:10000',
        ]);

        // Validate based on type
        $validation = $setting->validateValue($request->value);
        if ($validation !== true) {
            return response()->json(['error' => $validation], 422);
        }

        $setting->update([
            'value' => $request->value,
            'updated_by' => auth()->user()->email,
        ]);

        // Clear cache
        Cache::forget("config_setting.{$setting->key}");

        return response()->json([
            'success' => true,
            'message' => 'Setting updated',
            'display_value' => $setting->display_value,
        ]);
    }

    public function store(Request $request)
    {
        if (!OperationsController::isAdmin()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'key' => 'required|string|max:100|unique:config_settings,key',
            'value' => 'nullable|string|max:10000',
            'type' => 'required|in:' . implode(',', array_keys(ConfigSetting::$types)),
            'category' => 'required|in:' . implode(',', array_keys(ConfigSetting::$categories)),
            'description' => 'nullable|string|max:255',
            'is_sensitive' => 'boolean',
        ]);

        $setting = ConfigSetting::create([
            'key' => $request->key,
            'value' => $request->value,
            'type' => $request->type,
            'category' => $request->category,
            'description' => $request->description,
            'is_sensitive' => $request->is_sensitive ?? false,
            'updated_by' => auth()->user()->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Setting created',
            'setting' => $setting,
        ]);
    }

    public function destroy($id)
    {
        if (!OperationsController::isAdmin()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $setting = ConfigSetting::findOrFail($id);
        $key = $setting->key;
        $setting->delete();

        Cache::forget("config_setting.{$key}");

        return response()->json([
            'success' => true,
            'message' => 'Setting deleted',
        ]);
    }
}
