<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Color;
use App\Type;
use App\Product;
use Auth;
use Image;
use Input;
use Validator;

class ClothesController extends Controller
{

    public function addClothes(){

        $colors = Color::all();
        $types = Type::all();

        return view('clothes.add')->withColors($colors)->withTypes($types);
    }

    public function saveClothes(Request $request){

        $this->validate($request, [
            'brand' => 'required',
            'price' => 'required|integer',
            'colors' => 'required',
            'file' => 'required|image|max:10000'
        ]);

        $product = new Product;
        $this->fillData($request, $product, false);

        return redirect()->action('DashboardController@index');
    }

    public function updateClothes(Request $request, $id){

        $this->validate($request, [
            'brand' => 'required',
            'price' => 'required|integer',
            'colors' => 'required',
            'file' => 'image|max:10000'
        ]);

        $product = Product::find($id);
        $product->colors()->detach();
        $this->fillData($request, $product, true);

        return redirect()->action('DashboardController@index');
    }

    public function fillData($request, $product, $update){

        // IMAGE
        if( ! $update || ($update && $request->file('file') != null )) {
            $image = $request->file('file');
            $filename  = time() . '.' . $image->getClientOriginalExtension();
            $path = public_path('clothes_pictures/' . $filename);
            Image::make($image->getRealPath())->resize(320, 320)->save($path);

            $product->image = $filename;
        }

        $product->FK_type = $request->input('type');
        $product->FK_user = Auth::user()->id;
        $product->brand = $request->input('brand');
        $product->size = $request->input('size');
        $product->price = $request->input('price');
        $product->seller = Auth::user()->name;
        $product->paid = 0;

        $product->save();

        $product = Product::find($product->id);
        $product->colors()->attach($request->input('colors'));

        return true;
    }

    public function edit($id){

        $product = Product::find($id);
        $colors = Color::all();
        $types = Type::all();
        $selectedColors = $product->colors;
        $selectedType = $product->FK_type;


        foreach ($selectedColors as $color) {
            $arSelectedColors[] = $color->id;
        }
        
        return view('clothes.edit')
                ->withProduct($product)
                ->withTypes($types)
                ->withColors($colors)
                ->with('selectedColors', $arSelectedColors)
                ->with('selectedType', $selectedType);
    }

    public function delete($id){

        $product = Product::find($id);
        $product->delete();
        return redirect()->action('DashboardController@index');
    }

    public function deleteHistory(){

        $sold = Product::where('FK_user', Auth::user()->id)->where('paid', 1);
        $sold->delete();

        return redirect()->action('DashboardController@index');
    }
}
