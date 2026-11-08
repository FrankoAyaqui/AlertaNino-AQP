<?php
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
Artisan::command('alerta:usuario {email} {rol}',function(){
 $role=$this->argument('rol');if(!in_array($role,['ciudadano','operador','administrador'])){$this->error('Rol inválido');return 1;}
 if(!filter_var($this->argument('email'),FILTER_VALIDATE_EMAIL)){$this->error('Email inválido');return 1;}
 $name=$this->ask('Nombre');$pass=$this->secret('Contraseña (mínimo 12 caracteres, mayúscula, minúscula y número)');
 if(strlen($pass)<12||!preg_match('/[A-Z]/',$pass)||!preg_match('/[a-z]/',$pass)||!preg_match('/[0-9]/',$pass)){$this->error('Contraseña insuficiente');return 1;}
 if(User::where('email',$this->argument('email'))->exists()){$this->error('Ya existe ese usuario');return 1;}
 $u=new User(['name'=>$name,'email'=>$this->argument('email'),'password'=>$pass]);$u->role=$role;$u->save();$this->info('Usuario creado');
})->purpose('Crear un usuario local sin contraseñas predeterminadas');
