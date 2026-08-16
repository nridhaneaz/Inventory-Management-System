<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Services\UnitConversionService;

class ProductController extends Controller
{
    private function formatProductForFrontend($product, UnitConversionService $converter)
    {
        $product->loadMissing('category');
        $product->category_name = $product->category?->name;

        $display = $converter->displayQuantity($product->stock_quantity ?? $product->unit ?? 0, $product->unit_type);
        $product->display_stock = $display['quantity'];
        $product->display_unit = $display['unit'];
        $product->stock_display = $converter->formatStock($product->stock_quantity ?? $product->unit ?? 0, $product->unit_type);
        $product->price_label = $converter->formatPriceLabel($product->price, $product->unit_type);

        return $product;
    }

    public function productPage(Request $request){
        $userId = $request->header('id');
        $converter = app(UnitConversionService::class);
        $products = Product::with('category')->where('user_id', '=', $userId)->orderByDesc('id')->get()->map(function ($product) use ($converter) {
            return $this->formatProductForFrontend($product, $converter);
        });
        $categories = Category::where('user_id', '=', $userId)->orderBy('name')->get();
        return Inertia::render('Product/ProductPage', ['products' => $products, 'categories' => $categories]);
    }

    public function productSavePage(Request $request){
        $userId = $request->header('id');
        $productId = $request->query('id');
        $product = Product::where('user_id', '=', $userId)->where('id', '=', $productId)->first();
        
        // If product exists, set selling_price for the form
        if ($product) {
            $product->selling_price = $product->price;
            $converter = app(UnitConversionService::class);
            $display = $converter->displayQuantity($product->stock_quantity ?? $product->unit ?? 0, $product->unit_type);
            $product->opening_stock = $display['quantity'];
            $product->opening_stock_unit = $display['unit'];
        }
        
        $categories = Category::where('user_id', '=', $userId)->get();

        return Inertia::render('Product/ProductSavePage', ['product' => $product, 'categories' => $categories]);
    }

    public function createProduct(Request $request){
        $converter = app(UnitConversionService::class);
        $uploadedFiles = [];
        try {
            $userId = $request->header('id');

            if (is_array($request->input('products'))) {
                $request->validate([
                    'products' => 'required|array|min:1',
                    'products.*.unit_type' => 'required|string',
                    'products.*.img_url' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:2048',
                ]);

                $rows = $request->input('products', []);
                $errors = [];
                $seen = [];
                $validRows = [];
                $existingCategoryIds = Category::where('user_id', $userId)
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->all();
                $existingKeys = Product::where('user_id', $userId)
                    ->get()
                    ->map(function ($product) {
                        return mb_strtolower($this->normalizeProductName($product->name)) . '|' . (string) $product->category_id;
                    })
                    ->all();

                foreach ($rows as $index => $row) {
                    $name = $this->normalizeProductName($row['name'] ?? '');
                    $unitType = $converter->normalizeUnitType($row['unit_type'] ?? 'pcs');
                    $categoryId = $row['category_id'] ?? null;
                    $purchasePrice = $row['purchase_price'] ?? null;
                    $sellingPrice = $row['selling_price'] ?? null;
                    $qty = $row['qty'] ?? null;
                    $key = mb_strtolower($name) . '|' . (string) $categoryId;
                    $isBlankRow = $name === ''
                        && empty($categoryId)
                        && ($purchasePrice === null || $purchasePrice === '')
                        && ($sellingPrice === null || $sellingPrice === '')
                        && ($qty === null || $qty === '')
                        && empty($row['img_url']);

                    if ($isBlankRow) {
                        continue;
                    }

                    $validRows[] = [
                        'index' => $index,
                        'row' => $row,
                        'unit_type' => $unitType,
                    ];

                    if ($name === '') {
                        $errors["products.$index.name"] = 'Product name is required.';
                    }

                    if (empty($categoryId)) {
                        $errors["products.$index.category_id"] = 'Category is required.';
                    } elseif (!in_array((string) $categoryId, $existingCategoryIds, true)) {
                        $errors["products.$index.category_id"] = 'Invalid category selected.';
                    }

                    if ($purchasePrice === null || $purchasePrice === '') {
                        $errors["products.$index.purchase_price"] = 'Purchase price is required.';
                    } elseif (!is_numeric($purchasePrice)) {
                        $errors["products.$index.purchase_price"] = 'Purchase price must be numeric.';
                    }

                    if ($sellingPrice === null || $sellingPrice === '') {
                        $errors["products.$index.selling_price"] = 'Selling price is required.';
                    } elseif (!is_numeric($sellingPrice)) {
                        $errors["products.$index.selling_price"] = 'Selling price must be numeric.';
                    }

                    if ($qty === null || $qty === '') {
                        $errors["products.$index.qty"] = 'Quantity is required.';
                    } elseif ($unitType === 'pcs' && filter_var($qty, FILTER_VALIDATE_INT) === false) {
                        $errors["products.$index.qty"] = 'Quantity must be a valid integer.';
                    }

                    if ($name !== '' && isset($seen[$key])) {
                        $errors["products.$index.name"] = 'Duplicate product in this bulk entry.';
                    }

                    if ($name !== '' && in_array($key, $existingKeys, true)) {
                        $errors["products.$index.name"] = 'Product already exists for this category.';
                    }

                    if ($name !== '') {
                        $seen[$key] = true;
                    }

                    if ($qty !== null && $qty !== '' && !$converter->isValidQuantity($qty, $unitType)) {
                        $errors["products.$index.qty"] = $unitType === 'pcs'
                            ? 'Quantity must be a valid integer.'
                            : 'Quantity must be a valid number.';
                    }
                }

                if (empty($validRows)) {
                    $errors['products'] = 'Add at least one product before saving.';
                }

                if (!empty($errors)) {
                    throw ValidationException::withMessages($errors);
                }

                $createdProducts = DB::transaction(function () use ($validRows, $userId, &$uploadedFiles, $request, $converter) {
                    $createdProducts = [];

                    foreach ($validRows as $item) {
                        $index = $item['index'];
                        $row = $item['row'];
                        $path = null;
                        $unitType = $item['unit_type'];
                        $baseStock = $converter->convertToBaseQuantity($row['qty'] ?? 0, $unitType);
                        $file = $request->file("products.$index.img_url");

                        if ($file) {
                            $path = $this->storeProductImage($file, $userId);
                            $uploadedFiles[] = $path;
                        }

                        $product = Product::create([
                            'user_id' => $userId,
                            'category_id' => $row['category_id'],
                            'name' => $this->normalizeProductName($row['name']),
                            'unit_type' => $unitType,
                            'price' => $row['selling_price'],
                            'purchase_price' => $row['purchase_price'],
                            'unit' => $baseStock,
                            'stock_quantity' => $baseStock,
                            'img_url' => $path ?? '',
                        ]);

                        $createdProducts[] = $this->formatProductForFrontend($product, $converter);
                    }

                    return $createdProducts;
                });

                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Products created successfully',
                        'products' => $createdProducts,
                    ], 201);
                }

                return redirect()->route('productPage')->with(['status' => true, 'message' => 'Products created successfully'], 200);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'unit_type' => 'required|string',
                'selling_price' => 'required|numeric|min:0',
                'purchase_price' => 'required|numeric|min:0',
                'qty' => 'required|numeric|min:0',
                'img_url' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:2048',
                'category_id' => 'required|exists:categories,id',
            ]);

            $unitType = $converter->normalizeUnitType($request->input('unit_type'));
            if (!$converter->isValidQuantity($request->input('qty'), $unitType)) {
                throw ValidationException::withMessages([
                    'qty' => $unitType === 'pcs' ? 'Quantity must be a valid integer.' : 'Quantity must be a valid number.',
                ]);
            }

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
                'unit_type' => $unitType,
                'price' => $request->input('selling_price'), // Use selling_price from form
                'purchase_price' => $request->input('purchase_price') ?? 0,
                'unit' => $converter->convertToBaseQuantity($request->input('qty'), $unitType),
                'stock_quantity' => $converter->convertToBaseQuantity($request->input('qty'), $unitType),
                'img_url' => $path ?? ''
            ];

            Product::create($data);
            return redirect()->route('productPage')->with(['status' => true, 'message' => 'Product created successfully'], 200);
        } catch (ValidationException $e) {
            throw $e;
        } catch(Exception $e) {
            foreach ($uploadedFiles as $path) {
                if ($path && file_exists(public_path($path))) {
                    File::delete(public_path($path));
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return redirect()->route('productSavePage')->with(['status' => false, 'message' => $e->getMessage()], 200);
        }
    }

    public function updateProduct(Request $request){
        try {
            $userId = $request->header('id');
            $converter = app(UnitConversionService::class);

            $request->validate([
                'id' => 'required|exists:products,id',
                'name' => 'required|string|max:255',
                'unit_type' => 'required|string',
                'selling_price' => 'required|numeric|min:0',
                'purchase_price' => 'required|numeric|min:0',
                'qty' => 'required|numeric|min:0',
                'category_id' => 'required|exists:categories,id',
            ]);

            $unitType = $converter->normalizeUnitType($request->input('unit_type'));
            if (!$converter->isValidQuantity($request->input('qty'), $unitType)) {
                throw ValidationException::withMessages([
                    'qty' => $unitType === 'pcs' ? 'Quantity must be a valid integer.' : 'Quantity must be a valid number.',
                ]);
            }
            
            $data = [
                'name' => $request->input('name'),
                'unit_type' => $unitType,
                'price' => $request->input('selling_price'), // Use selling_price from form
                'purchase_price' => $request->input('purchase_price') ?? 0,
                'unit' => $converter->convertToBaseQuantity($request->input('qty'), $unitType),
                'stock_quantity' => $converter->convertToBaseQuantity($request->input('qty'), $unitType),
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

    private function normalizeProductName(?string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $name));
    }

    private function storeProductImage($file, string $userId): string
    {
        $fileName = $file->getClientOriginalName();
        $t = time();
        $imageName = $userId . '-' . $t . '-' . $fileName;
        $path = 'images/' . $imageName;
        $file->move(public_path('images'), $imageName);

        return $path;
    }
}