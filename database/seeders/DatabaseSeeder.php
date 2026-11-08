<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Zone,Alert,Guide};
class DatabaseSeeder extends Seeder {
 public function run():void {
 foreach(['Socabaya','Miraflores','Alto Selva Alegre'] as $name){$z=Zone::firstOrCreate(['name'=>$name],['description'=>'Distrito del piloto académico. La zona no equivale a un mapa de peligro.']);
 Alert::firstOrCreate(['title'=>'Ejercicio de preparación en '.$name],['zone_id'=>$z->id,'level'=>'informativo','message'=>'Aviso ficticio para aprender a consultar el distrito, la vigencia y la fuente. Consulta las recomendaciones de la autoridad local. No indica una emergencia real.','source_url'=>'https://www.gob.pe/indeci','issued_at'=>now()->subMinute(),'expires_at'=>now()->addDays(7),'status'=>'publicada']);}
 Guide::firstOrCreate(['title'=>'Cómo leer un aviso'],['body'=>'1. Confirma el distrito y la fecha. 2. Lee la fuente oficial enlazada. 3. Comprueba la vigencia. 4. Consulta las indicaciones de la autoridad. Esta guía explica la aplicación y no establece rutas de evacuación.','source_url'=>'https://www.gob.pe/indeci']);
 }
}
