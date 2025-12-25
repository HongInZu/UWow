<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    // 文章列表
    public function index(Request $request): JsonResponse
    {
        $query = Article::query();

        // 關鍵字搜尋
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // 狀態篩選
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->inactive();
            }
        }

        // 排序
        $sortBy = $request->get('sort_by', 'order');
        $sortOrder = $request->get('sort_order', 'asc');

        if ($sortBy === 'order') {
            $query->ordered();
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->get('per_page', 15);
        $articles = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $articles
        ]);
    }

    // 新增文章
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->only(['title', 'content', 'is_active', 'order']);

            // 處理圖片上傳
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('articles', 'public');
                $data['image'] = $imagePath;
            }

            // 如果沒有指定排序，自動設為最後一個
            if (!isset($data['order'])) {
                $maxOrder = Article::max('order') ?? 0;
                $data['order'] = $maxOrder + 1;
            }

            $article = Article::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '文章新增成功',
                'data' => $article
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => '新增失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 文章詳情
    public function show(Article $article): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $article
        ]);
    }

    // 更新文章
    public function update(Request $request, Article $article): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->only(['title', 'content', 'is_active', 'order']);

            // 處理圖片上傳
            if ($request->hasFile('image')) {
                // 刪除舊圖片
                if ($article->image) {
                    Storage::disk('public')->delete($article->image);
                }

                $imagePath = $request->file('image')->store('articles', 'public');
                $data['image'] = $imagePath;
            }

            $article->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '更新成功',
                'data' => $article->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => '更新失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 刪除文章
    public function destroy(Article $article): JsonResponse
    {
        try {
            $article->delete();

            return response()->json([
                'success' => true,
                'message' => '刪除成功'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '刪除失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 啟用文章
    public function setActive(Article $article): JsonResponse
    {
        try {
            $article->update(['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => '已啟用',
                'data' => $article
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '操作失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 停用文章
    public function setInactive(Article $article): JsonResponse
    {
        try {
            $article->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => '已停用',
                'data' => $article
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '操作失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 切換文章狀態
    public function toggleStatus(Article $article): JsonResponse
    {
        try {
            $article->update(['is_active' => !$article->is_active]);

            return response()->json([
                'success' => true,
                'message' => '狀態已切換',
                'data' => $article
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '操作失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 更新排序
    public function updateOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:articles,id',
            'orders.*.order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->orders as $orderData) {
                Article::where('id', $orderData['id'])
                    ->update(['order' => $orderData['order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '排序更新成功'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => '排序更新失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 批次刪除
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|exists:articles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Article::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => '批次刪除成功'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '批次刪除失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 批次更新狀態
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|exists:articles,id',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Article::whereIn('id', $request->ids)
                ->update(['is_active' => $request->is_active]);

            return response()->json([
                'success' => true,
                'message' => '狀態更新成功'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '狀態更新失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}