<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
class ExampleTest extends TestCase {use RefreshDatabase;public function test_homepage():void{$this->get('/')->assertOk()->assertSee('SEGUIMIENTO INFORMATIVO');}}
