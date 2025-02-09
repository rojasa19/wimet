<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use App\Espacio;
use App\Image;
use App\Price;
use App\User;
use DB;
use Mail;
use Validator;
use Symfony\Component\HttpFoundation\Response;
use Auth;
use App\Mail\NuevoEspacio;

class EspacioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = Espacio::with(
                'prices',
                'user',
                'categorias',
                'servicios',
                'estilosEspacio',
                'rules',
                'images',
                'characteristics',
                'access'
            );
            $query->orderBy('id', 'DESC');
            // Filtro por status
            if ($request->has('status')) {
                $query->where('status', '=', $request->input('status'));
            }
            // Filtro por step
            if ($request->has('step')) {
                $query->where('step', '=', $request->input('step'));
            }
            if ($request->has('user')) {
                $user = User::find($request->input('user'));
                if (!$user->isAdmin) {
                    $query->where('user_id', '=', $request->input('user'));
                }
            }
            $querystringArray = $request->only(['status', 'step', 'page']);
            $espacios = $query->paginate(10)->appends($querystringArray);
            return $espacios;
        }catch (\Exception $e) {
            return response('Su usuario no tiene permisos para ver esta pantalla', 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $lastEspacio = Espacio::latest()->first();
            DB::beginTransaction();
            $espacio = new Espacio();
            $espacio->user_id = $request->user_id;
            $espacio->name = $request->name;
            $espacio->phone = $request->phone;
            $espacio->staticname = str_slug($request->name);
            $espacio->description = $request->description;
            $espacio->quantyrooms = ($request->quantyrooms) ? $request->quantyrooms : null;
            $espacio->quantybathrooms = ($request->quantyrooms) ? $request->quantyrooms : null;
            $espacio->floor = ($request->floor) ? $request->floor : null;
            $espacio->surface = ($request->surface) ? $request->surface : null;
            $espacio->seated = ($request->seated) ? $request->seated : null;
            $espacio->quanty = ($request->quanty) ? $request->quanty : null;
            $espacio->adress = ($request->adress) ? $request->adress : null;
            $espacio->type = ($request->type) ? $request->type : null;
            $espacio->city = ($request->city) ? $request->city : null;
            $espacio->state = ($request->state) ? $request->state : null;
            $espacio->country = ($request->country) ? $request->country : null;
            $espacio->long = ($request->long) ? $request->long : null;
            $espacio->lat = ($request->lat) ? $request->lat : null;
            $espacio->step = ($request->step) ? $request->step : null;
            $espacio->status = false;
            $espacio->order = $lastEspacio->order + 1;
            $espacio->save();

            // Actualizo las categorias
            if($request->categorias){
                $arrCategories = [];
                foreach ($request->categorias as $key => $categoria)
                {
                    array_push($arrCategories, $categoria['id']);
                    $price = new Price;
                    $price->espacio_id      = $espacio->id;
                    $price->categoria_id    = $categoria['id'];
                    $price->price           = 0;
                    $price->daily           = 0;
                    $price->minhours        = 0;
                    $price->status          = true;
                    $price->save();
                }
                $espacio->categorias()->sync($arrCategories);
            }

            //Actualizo el tipo de usuario
            $user = User::find($espacio->user_id);
            $user->tipo_clientes_id = 2;
            $user->save();
            DB::commit();
            return $espacio;
        }catch(\Exception $e){
            DB::rollBack();
            return response('Los campos no son correctos, ' . $e->getMessage(), 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $query = Espacio::with(
                'prices',
                'user',
                'categorias',
                'servicios',
                'estilosEspacio',
                'rules',
                'images',
                'characteristics',
                'access'
            );
            $query->where('id', '=', $id);
            $espacio = $query->first();
            return $espacio;
        }catch (\Exception $e) {
            return \response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $input = $request->all();
            $input['staticname'] = str_slug($input['name']);
            $espacio = Espacio::find($id);
            $espacio->update($input);
            // Se sincronizan los servicios
            if($request->servicios){
                $arrServicios = [];
                foreach ($request->servicios as $servicio) {
                    array_push($arrServicios, $servicio['id']);
                }
                $espacio->servicios()->sync($arrServicios);
            }
            // Se sincronizan los accesos
            if($request->access){
                $arrAccess = [];
                foreach ($request->access as $acc) {
                    array_push($arrAccess, $acc['id']);
                }
                $espacio->access()->sync($arrAccess);
            }
            return $espacio;
        }catch (\Exception $e) {
            return response('Los campos no son correctos ' . $e->getMessage(), 400);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getEspacio($categoriaId, $id)
    {
        $query = DB::table('espacios');
        $query->join('categoria_espacio', 'espacios.id', '=', 'categoria_espacio.espacio_id');
        // Join con precios por Id categoria y Id de espacio
        $query->join('prices', function($join) {
            $join->on('espacios.id', '=', 'prices.espacio_id');
            $join->on('categoria_espacio.categoria_id', '=', 'prices.categoria_id');
        });
        $query->join('users', 'espacios.user_id', '=', 'users.id');
        $query->select('espacios.id', 'espacios.name', 'espacios.quanty', 'espacios.portada', 'espacios.staticname', 'espacios.image360', 'espacios.country', 'prices.price', 'users.imagesource');
        $query->where([
            ['espacios.id', '=', $id],
            ['categoria_espacio.categoria_id', '=', $categoriaId],
        ]);
        $query->groupBy('espacios.id');
        $espacio = $query->get();
        return $espacio;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id){
        $espacio = Espacio::find($id);
        $espacio->delete();
        return $id;
    }

    /**
    * @fn saveEspacioWithoutData()
    * @brief Funcion que guarda un espacio desde la vista registracion espacio
    * @param Object $request
    * @return redirect to public-categoria
    */
    public function saveEspacioWithoutData(Request $request) {
        try {
            $emails = ['adrian@wimet.co', 'alejandro@wimet.co', 'federico@wimet.co'];

            $user = User::find($request->user_id);
            // Se crea el espacio
            $espacio = new Espacio($request->all());
            $espacio->status = false;
            $espacio->save();
            // se cambia el tipo del usuario
            $user->tipo_clientes_id = 2;
            $user->save();
            // Se guardan las categorias asociadas al espacio
            $categorias = $espacio->estilosEspacio()->sync($request->estilos);
            // Se modifica el tipo de usuario en Hubspot
            $this->registerHubspot($user);
            // Envia email a wimet informando el nuevo espacio
            Mail::to($emails)
                ->queue(new NuevoEspacio($espacio, $user, $categorias));
            return $espacio;
        }catch(\Exception $e){
            return response('Los campos no son correctos', 400);
        }
    }

    /**
    * @fn saveEspacio()
    * @brief Funcion que guarda un espacio desde la vista registracion espacio
    * @param Object $request
    * @return redirect to public-categoria
    */
    public function saveEspacio(Request $request) {
        try {
            $espacio = Espacio::find($request->id);
            $espacio->name = $request->name;
            $espacio->description = nl2br($request->description);
            $espacio->step = ($espacio->step == 4) ? $espacio->step : $request->step;
            $espacio->save();
            if($espacio->step != 4) {
                $user = User::find($espacio->user_id);
                $this->regitroStepHubspot($user, $request->step);
            }
            return $espacio;
        }catch(\Exception $e){
            return response('Los campos no son correctos')->status(400);
        }
    }

    /**
    * @fn saveCategory()
    * @brief Funcion que asocia categorias a un espacio
    * @param Object $request
    * @return redirect to public-detalles
    */
    public function saveCategory(Request $request) {
        $espacio = Espacio::find($request->id);
        if(!$request->categorias){
            $request->session()->flash('alert-danger', 'Debe seleccionar alguna actividad');
            return \Redirect::route('publica-categoria', array('id' => $espacio->id));
        }
        $espacio->categorias()->sync($request->categorias);
        $espacio->save();
        $oldPrices = $espacio->prices();
        $espacio->prices()->delete();
        $espacio->save();
        foreach ($request->categorias as $key => $categoria)
        {
            $encontro = false;
            $objEncontrado = null;

            foreach ($oldPrices->get() as $key => $oldPrice) {
                if((int)$categoria == $oldPrice->categoria_id) {
                    $objEncontrado = $oldPrice;
                }
            }
            $price = new Price;
            if($encontro){
                $price->espacio_id      = $objEncontrado->espacio_id;
                $price->categoria_id    = $objEncontrado->categoria_id;
                $price->price           = $objEncontrado->price;
                $price->daily           = $objEncontrado->daily;
                $price->minhours        = $objEncontrado->minhours;
                $price->status          = $objEncontrado->status;
            }else {
                $price->espacio_id      = $espacio->id;
                $price->categoria_id    = $categoria;
                $price->price           = 0;
                $price->daily           = 0;
                $price->minhours        = 0;
                $price->status          = true;
            }
            $price->save();
        }
        return \Redirect::route('publica-detalles', array('id' => $request->id));
    }

    /**
    * @fn saveAccess()
    * @brief Funcion que asocia accesos a un espacio
    * @param Object $request
    * @return redirect to public-invidatos
    */
    public function saveAccess(Request $request) {
        $espacio = Espacio::find($request->id);
        if($request->quantyrooms) {
            $espacio->quantyrooms = $request->quantyrooms;
        }
        if($request->quantybathrooms) {
            $espacio->quantybathrooms = $request->quantybathrooms;
        }
        if($request->floor) {
            $espacio->floor = $request->floor;
        }
        if($request->surface) {
            $espacio->surface = $request->surface;
        }
        if($request->access) {
            $espacio->access()->sync($request->access);
        }
        $espacio->save();
        return \Redirect::route('publica-invidatos', array('id' => $request->id));
    }

    /**
    * @fn saveInvitados()
    * @brief Funcion que asocia cantidad invitados a un espacio
    * @param Object $request
    * @return redirect to public-maps
    */
    public function saveInvitados(Request $request) {
        $espacio = Espacio::find($request->id);
        if($request->quanty) {
            $espacio->quanty = $request->quanty;
        }
        if($request->foot) {
            $espacio->foot = $request->foot;
        }
        if($request->seated) {
            $espacio->seated = $request->seated;
        }
        $espacio->save();
        return \Redirect::route('publica-maps', array('id' => $espacio->id));
    }

    /**
    * @fn saveAdress()
    * @brief Funcion que asocia direccion a un espacio
    * @param Object $request
    * @return redirect to public-images
    */
    public function saveAdress(Request $request) {
        $espacio = Espacio::find($request->id);
        if($request->route && $request->street_number) {
            $espacio->adress = $request->route . " " . $request->street_number;
        }
        if($request->locality) {
            $espacio->state = $request->locality;
        }
        if($request->administrative_area_level_1) {
            $espacio->city = $request->administrative_area_level_1;
        }
        if($request->country) {
            $espacio->country = $request->country;
        }
        if($request->lat) {
            $espacio->lat = $request->lat;
        }
        if($request->long) {
            $espacio->long = $request->long;
        }
        $espacio->step = ($espacio->step == 4) ? $espacio->step : 2;
        $espacio->save();
        if($espacio->step == 2) {
            $user = User::find($espacio->user_id);
            $this->regitroStepHubspot($user, 2);
        }
        return \Redirect::route('publica-steps', array(
                "espacioId" => $espacio->id
            )
        );
    }

    /**
    * @fn saveImages()
    * @brief Funcion que asocia imagenes a un espacio
    * @param Object $request
    * @return redirect to public-amenities
    */
    public function saveImages(Request $request, $id) {
        \Cloudinary::config(array( 
          "cloud_name" => "wimet", 
          "api_key" => "278198295249288", 
          "api_secret" => "UCZYJFDClfelbwqG_CJajCWI-cw" 
        ));
        try {
            if ($request->hasFile('file')) {
                $espacio = Espacio::find($id);
                // upload the image //
                $imagesEspacio = $request->file('file');
                $destination_fotoprincipales = 'fotosespacios/' . $id . '/';
                $extension = "." . pathinfo($imagesEspacio->getClientOriginalName(), PATHINFO_EXTENSION);
                $filename = "wimet_espacios_creativos_reuniones_producciones_eventos_retail_" . $espacio->staticname . "_" . $request->imgorder . $extension;

                \Cloudinary\Uploader::upload($imagesEspacio,
                    array(
                        "public_id" => $destination_fotoprincipales . str_replace($extension, "", $filename)
                    )
                );
                $cloudinaryUrl = "http://res.cloudinary.com/wimet/image/upload/";
                $image = new Image;
                $image->name = $cloudinaryUrl . $destination_fotoprincipales . $filename;
                $image->espacio_id = $id;
                $image->imgorder = $request->imgorder;
                $image->save();
                return $image;
            }
        }catch (\Exception $e) {
            return response('Error al guardar la imagen, intente nuevamente'. $e->getMessage(), 500);
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|Response
     */
    public function savePortada(Request $request, $id) {
        \Cloudinary::config(array(
            "cloud_name" => "wimet",
            "api_key" => "278198295249288",
            "api_secret" => "UCZYJFDClfelbwqG_CJajCWI-cw"
        ));
        try {
            if ($request->hasFile('portada')) {
                $espacio = Espacio::find($id);
                $imagenPortada = $request->file('portada');
                $destination_fotoprincipales = 'fotosespacios/' . $id . '/';
                $extension = "." . pathinfo($imagenPortada->getClientOriginalName(), PATHINFO_EXTENSION);
                $filename = "wimet_espacios_creativos_reuniones_producciones_eventos_retail_" . $espacio->staticname . "_portada" . $extension;

                \Cloudinary\Uploader::upload($imagenPortada,
                    array(
                        "public_id" => $destination_fotoprincipales . str_replace($extension, "", $filename)
                    )
                );
                $cloudinaryUrl = "http://res.cloudinary.com/wimet/image/upload/";
                $espacio->portada = $cloudinaryUrl . $destination_fotoprincipales . $filename;
                $espacio->save();
                return $espacio;
            }
        }catch (\Exception $e) {
            return response('Error al guardar la imagen de portada, intente nuevamente'. $e->getMessage(), 500);
        }
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|Response
     */
    public function destroyPortada($id)
    {
        try {
            $espacio = Espacio::find($id);
            \Cloudinary::config(array(
                "cloud_name" => "wimet",
                "api_key" => "278198295249288",
                "api_secret" => "UCZYJFDClfelbwqG_CJajCWI-cw"
            ));

            //Busca la imagen actual y la borra
            $cloudinaryUrl = "http://res.cloudinary.com/wimet/image/upload/";
            $namesearch = str_replace($cloudinaryUrl, "", $espacio->portada);
            \Cloudinary\Uploader::destroy($namesearch);
            $espacio->portada = "";
            $espacio->save();
            return $espacio;
        }catch (\Exception $e) {
            return response('No se pudo borrar la imagen actual', 500);
        }
    }

    /**
    * @fn saveAmenities()
    * @brief Funcion que asocia amenities a un espacio
    * @param Object $request
    * @return redirect to public-caracteristicas
    */
    public function saveAmenities(Request $request) {
        if($request->servicios) {
            $espacio = Espacio::find($request->id);
            $espacio->servicios()->sync($request->servicios);
            $espacio->save();
        }
        return \Redirect::route('publica-titulo', array('id' => $request->id));
    }

    /**
    * @fn saveImages()
    * @brief Funcion que asocia imagenes a un espacio
    * @param Object $request
    * @return redirect to public-caracteristicas
    */
    public function saveCaracteristicas(Request $request) {
        if($request->characteristics) {
            $espacio = Espacio::find($request->id);
            $espacio->characteristics()->sync($request->characteristics);
            $espacio->save();
        }
        return \Redirect::route('publica-amenities', array('id' => $request->id));
    }

    /**
    * @fn saveRules()
    * @brief Funcion que asocia las reglas a un espacio
    * @param Object $request
    * @return redirect to public-caracteristicas
    */
    public function saveRules(Request $request) {
        if($request->rules) {
            $espacio = Espacio::find($request->id);
            $espacio->rules()->sync($request->rules);
            $espacio->save();
        }
        return \Redirect::route('publica-prices', array('id' => $request->id));
    }

    /**
    * @fn savePrice()
    * @brief Funcion que asocia precios a un espacio
    * @param Object $request
    * @return redirect to public-caracteristicas
    */
    public function savePrice(Request $request) {
        try {
            foreach ($request->categories as $key => $cat) {
                $price = Price::find($cat['id']);
                if ($cat['espacio_id']) {
                    $price->espacio_id = $cat['espacio_id'];
                }
                if ($cat['categoria_id']) {
                    $price->categoria_id = $cat['categoria_id'];
                }
                if ($cat['price']) {
                    $price->price = $cat['price'];
                }
                if ($cat['daily']) {
                    $price->daily = $cat['daily'];
                }
                if ($cat['minhours']) {
                    $price->minhours = $cat['minhours'];
                }
                $price->status = $cat['status'];
                $price->save();
            }
            return response('Los precios fueron modificados', 200);
        }catch(\Exception $e) {
            return response('Los precios no fueron modificados', 500);
        }
    }

    /**
    * @fn saveCancelacion()
    * @brief Funcion que asocia cantidad invitados a un espacio
    * @param Object $request
    * @return redirect to public-maps
    */
    public function saveCancelacion(Request $request) {
        $espacio = Espacio::find($request->id);
        if($request->securitydeposit) {
            $espacio->securitydeposit = $request->securitydeposit;
        }
        if($request->cancellationflexibility) {
            $espacio->cancellationflexibility = $request->cancellationflexibility;
        }
        $espacio->step = 4;
        $espacio->save();
        if($espacio->step == 4) {
            $user = User::find($espacio->user_id);
            $this->regitroStepHubspot($user, 4);
        }
        return \Redirect::route('publica-steps', array(
                "espacioId" => $espacio->id
            )
        );
    }

    /**
    * @fn saveWishlist()
    * @brief Funcion que asocia un espacio a un usuaio
    * @param Object $request
    * @return true o false
    */
    public function saveWishlist(Request $request) {
        $user = User::find($request->clientId);
        if($user->id == null){
            return false;
        }
        $user->wishlist()->sync($request->espacio_id);
        return true;
    }

    /**
     * Create a new user instance after a valid registration. into huspot
     *
     * @param  array  $data
     * @return User
     */
    protected function registerHubspot($data) {
        $arr = array(
            'properties' => array(
                array(
                    'property' => 'esanfitrion',
                    'value' => true
                ),
                array(
                    'property' => 'step_wimet',
                    'value' => 1
                )
            )
        );
        $post_json = json_encode($arr);
        $keyHuspot = "153f6544-3085-41e5-98d0-80a3d435d496";
        
        $endpoint = 'https://api.hubapi.com/contacts/v1/contact/createOrUpdate/email/'.$data->email.'/?hapikey=' . $keyHuspot;
        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
        Log::debug("curl Errors: " . $curl_errors);
        Log::debug("Status code: " . $status_code);
        Log::debug("Response: " . $response);
    }

    /**
     * Create/update a user instance after a valid registration. into huspot
     *
     * @param  array  $data
     * @return User
     */
    public function regitroStepHubspot($data, $step) {
        $arr = array(
            'properties' => array(
                array(
                    'property' => 'step_wimet',
                    'value' => $step
                )
            )
        );
        $post_json = json_encode($arr);
        $keyHuspot = "153f6544-3085-41e5-98d0-80a3d435d496";
        $endpoint = 'https://api.hubapi.com/contacts/v1/contact/createOrUpdate/email/'.$data->email.'/?hapikey=' . $keyHuspot;
        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
    }

    /**
     * @fn searchEspacios
     * @brief Funcion que retorna los los espacios en base a los parametros
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|string|Response
     */
    public function searchEspacios(Request $request) {
        $querystringArray = $request->only(['ubicacion','categoria','price','quanty']);
        $query = Espacio::query();
        $query->select('id', 'long', 'lat');
        try {
            //Chequeo si existe el filtro por ubicacion
            if ($request->has('ubicacion')) {
                $query->where('city', $request->input('ubicacion'));
            }
            //Chequeo si existe el filtro por categoria
            if($request->has('categoria')){
                $query->whereHas('categorias',
                    function($subQuery) {
                        $subQuery->where('id', \Request::input('categoria'));
                    }
                );
            }
            //Chequeo si existe el filtro por price y quanty
            $query->whereHas('priceByCategory',
                function($subQuery) {
                    //Chequeo si existe el filtro por price
                    if(\Request::has('price')){
                        $precios = explode("-", \Request::input('price'));
                        if($precios[1] == "5000") {
                            $subQuery->where('price', '>=', $precios[0]);
                        }else {
                            $subQuery->whereBetween('price', [$precios[0], $precios[1]]);
                        }
                    }
                    //Chequeo si existe el filtro por quanty
                    if(\Request::has('quanty')) {
                        $quanties = explode("-", \Request::input('quanty'));
                        if($quanties[1] == '5000') {
                            $subQuery->where('quanty', '>=', $quanties[0]);
                        }else {
                            $subQuery->whereBetween('quanty', [$quanties[0], $quanties[1]]);
                        }
                    }
                }
            );
            // Filtro por espacios activos
            $query->where('espacios.status', '=', true);
            $espacios = $query->paginate(20);
            $espacios->appends($querystringArray);
            return $espacios;
        }catch (\Exception $e) {
            return response('Los campos no son correctos, error: ' . $e->getMessage(), 400);
        }
    }
}
