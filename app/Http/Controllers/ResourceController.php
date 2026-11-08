<?php
namespace App\Http\Controllers;
use App\Models\{Alert,Zone,Guide,Contact,User};
use App\Notifications\PublishedAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class ResourceController extends Controller {
 // Whitelist: the request cannot select arbitrary models or tables.
 public static function definition(string $kind):array {return match($kind){
  'alertas'=>[Alert::class,['title'=>'Título','zone_id'=>'Distrito','level'=>'Nivel','message'=>'Mensaje y acciones','source_url'=>'URL HTTPS de referencia','issued_at'=>'Emitido el','expires_at'=>'Vence el','status'=>'Estado']],
  'zonas'=>[Zone::class,['name'=>'Distrito','description'=>'Descripción del ámbito']],
  'guias'=>[Guide::class,['title'=>'Título','body'=>'Pasos de preparación','source_url'=>'URL HTTPS de referencia']],
  'contactos'=>[Contact::class,['name'=>'Entidad o contacto','phone'=>'Teléfono','source_url'=>'URL HTTPS de referencia']],
  default=>abort(404)};}
 private function permissions(string $kind,Request $r):void {if($kind==='zonas')abort_unless($r->user()->role==='administrador',403);}
 public function index(Request $r,string $kind){$this->permissions($kind,$r);[$model,$fields]=self::definition($kind);return view('manage.index',compact('kind','fields')+['records'=>$model::latest()->paginate(15)]);}
 public function create(Request $r,string $kind){$this->permissions($kind,$r);[$model,$fields]=self::definition($kind);return view('manage.form',compact('kind','fields')+['record'=>new $model,'zones'=>Zone::orderBy('name')->get()]);}
 public function edit(Request $r,string $kind,int $id){$this->permissions($kind,$r);[$model,$fields]=self::definition($kind);return view('manage.form',compact('kind','fields')+['record'=>$model::findOrFail($id),'zones'=>Zone::orderBy('name')->get()]);}
 private function rules(string $kind,?int $id):array {
  $url=['required','url:https','max:1000'];
  return match($kind){
   'alertas'=>['title'=>'required|string|max:160','zone_id'=>'required|exists:zones,id','level'=>['required',Rule::in(['informativo','moderado','alto','muy alto'])],'message'=>'required|string|min:15|max:3000','source_url'=>$url,'issued_at'=>'required|date|before_or_equal:now','expires_at'=>'required|date|after:issued_at','status'=>['required',Rule::in(['borrador','publicada','cerrada'])]],
   'zonas'=>['name'=>['required','string','max:100',Rule::unique('zones')->ignore($id)],'description'=>'required|string|max:1000'],
   'guias'=>['title'=>'required|string|max:160','body'=>'required|string|min:15|max:5000','source_url'=>$url],
   'contactos'=>['name'=>'required|string|max:160','phone'=>['required','regex:/^[0-9+() -]{3,25}$/'],'source_url'=>$url],
  };
 }
 public function store(Request $r,string $kind){return $this->save($r,$kind,null);}
 public function update(Request $r,string $kind,int $id){return $this->save($r,$kind,$id);}
 private function save(Request $r,string $kind,?int $id){$this->permissions($kind,$r);[$model]=self::definition($kind);$record=$id?$model::findOrFail($id):new $model;$data=$r->validate($this->rules($kind,$id));
  if($kind==='alertas'&&$data['status']==='publicada'&&strtotime($data['expires_at'])<=time())return back()->withErrors(['expires_at'=>'Una publicación debe tener vigencia futura.'])->withInput();
  DB::transaction(function()use($record,$data,$kind){$publish=$kind==='alertas'&&$data['status']==='publicada'&&$record->status!=='publicada';$record->fill($data)->save();if($publish){User::where('zone_id',$record->zone_id)->each(fn($u)=>$u->notify(new PublishedAlert($record)));}});
  return redirect('/gestion/'.$kind)->with('ok','Registro guardado. Las publicaciones crean avisos de demostración en la bandeja del distrito.');
 }
 public function destroy(Request $r,string $kind,int $id){$this->permissions($kind,$r);[$model]=self::definition($kind);$record=$model::findOrFail($id);if($kind==='zonas'&&Alert::where('zone_id',$id)->exists())return back()->withErrors(['zone'=>'No puedes eliminar una zona que tiene alertas. Reasigna o elimina esas alertas primero.']);$record->delete();return back()->with('ok','Registro eliminado.');}
}
