<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
class AuthController extends Controller {
 public function form(){return view('auth',['register'=>false]);}
 public function registration(){return view('auth',['register'=>true]);}
 public function login(Request $r){$v=$r->validate(['email'=>'required|email','password'=>'required|string']);if(!Auth::attempt($v)){return back()->withErrors(['email'=>'Correo o contraseña incorrectos.'])->onlyInput('email');}$r->session()->regenerate();return redirect()->intended('/cuenta');}
 public function register(Request $r){$v=$r->validate(['name'=>'required|string|max:100','email'=>'required|email|max:255|unique:users','password'=>['required','confirmed',Password::min(12)->mixedCase()->numbers()],'consent'=>'accepted']);$u=User::create(collect($v)->only(['name','email','password'])->all());Auth::login($u);$r->session()->regenerate();return redirect('/cuenta');}
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/');}
 public function account(Request $r){return view('account',['zones'=>Zone::orderBy('name')->get(),'notices'=>$r->user()->notifications()->latest()->paginate(10)]);}
 public function preference(Request $r){$v=$r->validate(['zone_id'=>'nullable|exists:zones,id']);$r->user()->update($v);return back()->with('ok','Distrito guardado. Recibirás los próximos avisos publicados para esa zona en esta bandeja.');}
 public function read(Request $r,string $id){$r->user()->notifications()->findOrFail($id)->markAsRead();return back();}
}
