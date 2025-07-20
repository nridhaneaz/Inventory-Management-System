<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function productPage(Request $request){
        $userId = $request->header('id');
        $products = Product::where('user_id', '=', $userId)->get();
        return Inertia::render('Product/ProductPage', ['products' => $products]);
    }

    public function productSavePage(Request $request){
        $userId = $request->header('id');
        $productId = $request->query('id');
        $product = Product::where('user_id', '=', $userId)->where('id', '=', $productId)->first();
        
        // If product exists, set selling_price for the form
        if ($product) {
            $product->selling_price = $product->price;
        }
        
        $categories = Category::where('user_id', '=', $userId)->get();

        return Inertia::render('Product/ProductSavePage', ['product' => $product, 'categories' => $categories]);
    }

    public function createProduct(Request $request){
        try {
            $userId = $request->header('id');

            $request->validate([
                'name' => 'required|string|max:255',
                'selling_price' => 'required|numeric|min:0',
                'purchase_price' => 'nullable|numeric|min:0',
                'qty' => 'required|numeric|min:0',
                'img_url' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:2048',
                'category_id' => 'required|exists:categories,id',
            ]);

            // File upload
            if ($request->hasFile('img_url')) {
                $file = $request->file('img_url');
                $fileName = $file->getClientOriginalName();
                $t = time();
                $img_url = $userId.'-'.$t.'-'.$fileName;
                $path = 'images/'.$img_url;
                $file->move(public_path('images'), $img_url);
            } else {
                $path = null;
            }

            $data = [
                'user_id' => $userId,
                'category_id' => $request->input('category_id'),
                'name' => $request->input('name'),
                'price' => $request->input('selling_price'), // Use selling_price from form
                'purchase_price' => $request->input('purchase_price'), // Add purchase_price
                'unit' => $request->input('qty'),
                'img_url' => $path
            ];

            Product::create($data);
            return redirect()->route('productPage')->with(['status' => true, 'message' => 'Product created successfully'], 200);
        } catch(Exception $e) {
            return redirect()->route('productSavePage')->with(['status' => false, 'message' => $e->getMessage()], 200);
        }
    }

    public function updateProduct(Request $request){
        try {
            $userId = $request->header('id');

            $request->validate([
                'id' => 'required|exists:products,id',
                'name' => 'required|string|max:255',
                'selling_price' => 'required|numeric|min:0',
                'purchase_price' => 'nullable|numeric|min:0',
                'qty' => 'required|numeric|min:0',
                'category_id' => 'required|exists:categories,id',
            ]);
            
            $data = [
                'name' => $request->input('name'),
                'price' => $request->input('selling_price'), // Use selling_price from form
                'purchase_price' => $request->input('purchase_price'), // Add purchase_price
                'unit' => $request->input('qty'),
                'category_id' => $request->input('category_id')
            ];

            if ($request->hasFile('img_url')) {
                $file = $request->file('img_url');
                $fileName = $file->getClientOriginalName();
                $t = time();
                $img_url = $userId.'-'.$t.'-'.$fileName;
                $path = 'images/'.$img_url;
                $file->move(public_path('images'), $img_url);
                $data['img_url'] = $path;

                $oldImage = Product::where('user_id', '=', $userId)
                    ->where('id', '=', $request->input('id'))
                    ->select('img_url')
                    ->first();
                    
                if ($oldImage && $oldImage->img_url) {
                    File::delete(public_path($oldImage->img_url));
                }
            }
            
            Product::where('user_id', '=', $userId)
                ->where('id', '=', $request->input('id'))
                ->update($data);
                
            return redirect()->route('productPage')->with(['status' => true, 'message' => 'Product updated successfully']);
        } catch(Exception $e) {
            return redirect()->route('productSavePage')->with(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteProduct(Request $request){
        try {
            $userId = $request->header('id');
            $productId = $request->query('id');
            
            $oldImage = Product::where('user_id', '=', $userId)
                ->where('id', '=', $productId)
                ->select('img_url')
                ->first();
                
            if ($oldImage && $oldImage->img_url) {
                File::delete(public_path($oldImage->img_url));
            }

            Product::where('user_id', '=', $userId)
                ->where('id', '=', $productId)
                ->delete();

            return redirect()->route('productPage')->with(['status' => true, 'message' => 'Product deleted successfully']);
        } catch(Exception $e) {
            return redirect()->route('productPage')->with(['status' => false, 'message' => $e->getMessage()]);
        }
    }
}