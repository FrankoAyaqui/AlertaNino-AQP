<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class Staff {public function handle(Request $r,Closure $next){abort_unless(in_array($r->user()?->role,['administrador','operador']),403);return $next($r);}}
