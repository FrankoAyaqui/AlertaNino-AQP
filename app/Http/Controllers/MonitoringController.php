<?php
namespace App\Http\Controllers;
use App\Services\LiveMonitoring;
use Illuminate\Http\Request;
class MonitoringController extends Controller {
 public function index(){return view('monitoring',['places'=>LiveMonitoring::PLACES]);}
 public function data(Request $request,LiveMonitoring $monitor){$v=$request->validate(['source'=>'required|in:weather,warnings,impacts,reports','place'=>'sometimes|in:'.implode(',',array_keys(LiveMonitoring::PLACES))]);return response()->json($monitor->read($v['source'],$v['place']??'arequipa'))->header('Cache-Control','no-store');}
}
