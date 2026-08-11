<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentTypeController extends Controller
{
    public function index(Request $request){

        $query = DocumentType::query();

        $query->when($request->has('s'), function($query) use($request){
            $search = trim($request->s);
            $query->where('name','like',"%{$search}%");
        })->orderBy('name','asc')->orderBy('id','asc');

        return $request->has('per_page') ? $query->paginate($request->per_page) : $query->get();
    }

    public function store(Request $request)
    {
        try {

            DB::beginTransaction();

            DocumentType::create([
                'code' =>strtoupper($request->code),
                'name' =>strtoupper($request->name)
            ]);

            DB::commit();

            return response()->json(['status' => 'ok', 'msg' => 'datos guardados']);

        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function show($id){
        $data=DocumentType::where('id',$id)->first();
        return response()->json($data);
    }

    public function update(Request $request){
        try {

            DB::beginTransaction();

            $race=DocumentType::find($request->id);

            $race->code=strtoupper($request->code);
            $race->name=strtoupper($request->name);

            $race->save();

            DB::commit();

            return response()->json(['status' =>'ok','msg' =>'Operación exitosa']);

        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage().PHP_EOL.$ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }
}
