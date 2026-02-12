<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function show(Request $r)
    {
        // simple single settings file for demo
        $json = Storage::disk('local')->exists('settings.json') ? json_decode(Storage::disk('local')->get('settings.json'), true) : [];
        return response()->json($json);
    }

    public function save(Request $r)
    {
        $r->validate(['yandex_url'=>'nullable|url']);
        $data = ['yandex_url'=>$r->yandex_url];
        Storage::disk('local')->put('settings.json', json_encode($data));
        return response()->json(['ok'=>true]);
    }

    public function import(Request $r)
    {
        // enqueue or run import - here we mock starting import
        // In real implementation, dispatch a job to scrape Yandex reviews
        return response()->json(['job_id' => uniqid('imp_')]);
    }
}
