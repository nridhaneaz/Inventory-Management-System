<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CategoryController extends Controller
{
    private function normalizeCategoryName(?string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $name));
    }

    private function findCategoryDuplicateErrors(array $rows, string $userId): array
    {
        $errors = [];
        $seen = [];
        $existingNames = Category::where('user_id', $userId)
            ->pluck('name')
            ->map(fn ($name) => mb_strtolower($this->normalizeCategoryName($name)))
            ->all();

        foreach ($rows as $index => $row) {
            $name = $this->normalizeCategoryName($row['name'] ?? '');

            if ($name === '') {
                $errors["categories.$index.name"] = 'Category name is required.';
                continue;
            }

            $key = mb_strtolower($name);

            if (in_array($key, $existingNames, true)) {
                $errors["categories.$index.name"] = 'Category name already exists.';
                continue;
            }

            if (isset($seen[$key])) {
                $errors["categories.$index.name"] = 'Duplicate category name in bulk entry.';
                continue;
            }

            $seen[$key] = true;
        }

        return $errors;
    }

    private function normalizeCategoryRow(?array $row): array
    {
        return [
            'name' => $this->normalizeCategoryName($row['name'] ?? ''),
        ];
    }

    private function isBlankCategoryRow(?array $row): bool
    {
        return $this->normalizeCategoryName($row['name'] ?? '') === '';
    }

   public function categoryPage(Request $request){
    $userId = $request->header('id');
    $categories = Category::where('user_id', '=', $userId)->get();
    return Inertia::render('Category/CategoryPage', ['categories' => $categories]);
    }

    public function categorySavePage(Request $request){
        $id=$request->query('id');
        $userId=$request->header('id');
        $category=Category::where('user_id','=',$userId)->where('id','=',$id)->first();
        return Inertia::render('Category/CategorySavePage',['category'=>$category]);
    }
    public function createCategory(Request $request)
    {
        try {
            $userId = $request->header('id');

            if (is_array($request->input('categories'))) {
                $request->validate([
                    'categories' => 'required|array|min:1',
                ]);

                $rows = array_values(array_filter(
                    array_map(fn ($row) => $this->normalizeCategoryRow($row), $request->input('categories', [])),
                    fn ($row) => !$this->isBlankCategoryRow($row)
                ));

                if (empty($rows)) {
                    throw ValidationException::withMessages([
                        'categories' => 'Add at least one category before saving.',
                    ]);
                }

                $errors = $this->findCategoryDuplicateErrors($rows, $userId);

                if (!empty($errors)) {
                    throw ValidationException::withMessages($errors);
                }

                DB::transaction(function () use ($rows, $userId) {
                    foreach ($rows as $row) {
                        $name = $this->normalizeCategoryName($row['name'] ?? '');
                        Category::create(['user_id' => $userId, 'name' => $name]);
                    }
                });

                return redirect()->route('categoryPage')->with(['status' => true, 'message' => 'Categories created successfully']);
            }

            $request->validate([
                'name' => 'string|required',
            ]);

            $name = $this->normalizeCategoryName($request->input('name'));
            if ($name === '') {
                throw ValidationException::withMessages(['name' => 'Category name is required.']);
            }

            $exists = Category::where('user_id', $userId)
               ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
               ->exists();

            if ($exists) {
               throw ValidationException::withMessages(['name' => 'Category name already exists.']);
            }

            Category::create(['user_id' => $userId, 'name' => $name]);
            return redirect()->route('categorySavePage')->with(['status' => true, 'message' => 'Category created successfully']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->route('categorySavePage')->with(['status' => false, 'message' => 'Category creation failed']);
        }
    }

    public function updateCategory(Request $request)
    {
        try {
            $userId = $request->header('id');
            $categoryId = $request->input('id');
            $name = $this->normalizeCategoryName($request->input('name'));

            if ($name === '') {
               throw ValidationException::withMessages(['name' => 'Category name is required.']);
            }

            $exists = Category::where('user_id', $userId)
               ->where('id', '!=', $categoryId)
               ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
               ->exists();

            if ($exists) {
               throw ValidationException::withMessages(['name' => 'Category name already exists.']);
            }

            Category::where('id', '=', $categoryId)->where('user_id', '=', $userId)->update([
                'name' => $name,
            ]);

            return redirect()->route('categorySavePage')->with(['status' => true, 'message' => 'Category updated successfully']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->route('categorySavePage')->with(['status' => false, 'message' => 'Category update failed']);
        }
    }

    public function deleteCategory(Request $request)
    {
        try {
            $userId = $request->header('id');
            $categoryId = $request->input('id');
            Category::where('user_id', '=', $userId)->where('id', '=', $categoryId)->delete();

            return redirect('/category-page')->with(['status' => true, 'message' => 'Category deleted successfully']);
        } catch (Exception $e) {
            return redirect()->route('categoryPage')->with(['status' => false, 'message' => 'Category deleteion failed']);
        }
    }
}
