<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Espacio;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
    public function __construct()
    {
        $this->middleware('auth');
    }
    */

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    public function search()
    {
        $espacios = Espacio::paginate(10);
        return view('search', array('espacios' => $espacios));
    }

    public function espacio($id)
    {
        $espacio = Espacio::find($id);
        return view('espacio', array('espacio' => $espacio));
    }
}
