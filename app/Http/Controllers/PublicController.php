<?php
namespace App\Http\Controllers;
use App\Models\{Alert,Zone,Guide,Contact};
use Illuminate\Http\Request;
class PublicController extends Controller {
 public function index(Request $r){$v=$r->validate(['zone'=>'nullable|integer|exists:zones,id','q'=>'nullable|string|max:100']);$query=Alert::with('zone')->where('status','publicada')->where('issued_at','<=',now())->where('expires_at','>',now())->when($v['zone']??null,fn($q,$id)=>$q->where('zone_id',$id))->when($v['q']??null,fn($q,$term)=>$q->where('title','like','%'.$term.'%'))->latest();$zoneSummaries=(clone $query)->get()->groupBy('zone_id');$alerts=$query->paginate(6)->withQueryString();return view('home',compact('alerts','zoneSummaries')+['zones'=>Zone::orderBy('name')->get(),'guides'=>Guide::latest()->get(),'contacts'=>Contact::orderBy('name')->get()]);}
 public function alert(Alert $alert){abort_unless($alert->status==='publicada',404);return view('alert',compact('alert'));}
}
