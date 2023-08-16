<?php

namespace Ugly\Base\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Ugly\Base\Models\File;
use Ugly\Base\Services\AuthInfoServices;
use Ugly\Base\Traits\ApiResource;

/**
 * 文件上传基类.
 */
class FileBaseController extends Controller
{
    use ApiResource;

    /**
     * 存储驱动.
     */
    protected string $disk = 'public';

    /**
     * 文件key.
     */
    protected string $fileKey = 'file';

    /**
     * 认证守卫.
     */
    protected string $guard = '';

    /**
     * 自定义上传路径.
     */
    protected function customPath(): string
    {
        return '';
    }

    /**
     * 自定义文件名.
     */
    protected function customName($file): string
    {
        return md5(time().$file->getClientOriginalName()).'.'.$file->getClientOriginalExtension();
    }

    /**
     * 自定义转换文件类型. 返回空字符串则表示不存储在素材库中.
     */
    protected function getCustomType(string $mimeType): string
    {
        // 以image开头的都是图片.
        if (str_starts_with($mimeType, 'image')) {
            return 'image';
        }
        // 以video开头的都是视频.
        if (str_starts_with($mimeType, 'video')) {
            return 'video';
        }

        return '';
    }

    /**
     * 文件列表.
     */
    public function index(): JsonResponse
    {
        $loginUser = AuthInfoServices::loginUser($this->guard);
        $belongs_type = $loginUser->getFileBelongType();
        $belongs_id = $loginUser->getFileBelongId();
        $query = File::search([
            'name' => 'like',
            'type' => '=',
        ])->where('belongs_type', $belongs_type)
            ->where('belongs_id', $belongs_id)
            ->select(['id', 'name', 'path', 'type', 'size', 'created_at', 'updated_at'])
            ->orderByDesc('updated_at');

        return $this->paginate($query);
    }

    /**
     * 上传文件.
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->hasFile($this->fileKey)) {
            $file = $request->file($this->fileKey);
            $sha1 = sha1_file($file->getRealPath());
            // 通过sha1判断文件是否已存在.
            $oldFile = File::query()->where('sha1', $sha1)->first();
            $name = $this->customName($file);
            if ($oldFile) {
                $path = $oldFile->path;
            } else {
                $path = $file->storeAs($this->customPath(), $name, $this->disk);
            }
            if (! $path) {
                return $this->failed('上传失败');
            }

            $mimeType = $file->getMimeType();
            $type = $this->getCustomType($mimeType);
            if ($type) { // 保存到素材库
                $loginUser = AuthInfoServices::tryLoginUser($this->guard);
                $belongs_type = $loginUser?->getFileBelongType();
                $belongs_id = $loginUser?->getFileBelongId();
                // 完全相同的文件不重复上传.
                if ($oldFile && $oldFile->belongs_type === $belongs_type && $oldFile->belongs_id == $belongs_id) {
                    $oldFile->update([ // 更新文件名称.
                        'name' => $name,
                        'updated_at' => now(), // 更新时间 排序在前面.
                    ]);
                } else {
                    File::query()->create([
                        'name' => $name,
                        'path' => $path,
                        'type' => $type,
                        'mime_type' => $mimeType,
                        'size' => $file->getSize(),
                        'sha1' => $sha1,
                        'belongs_type' => $belongs_type,
                        'belongs_id' => $belongs_id,
                    ]);
                }
            }

            return $this->success([
                'path' => $path,
                'url' => Storage::disk($this->disk)->url($path),
            ]);
        }

        return $this->failed('上传失败');
    }

    /**
     * 重命名.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name' => 'required',
        ]);
        $loginUser = AuthInfoServices::loginUser($this->guard);
        $belongs_type = $loginUser->getFileBelongType();
        $belongs_id = $loginUser->getFileBelongId();
        File::query()
            ->where('belongs_type', $belongs_type)
            ->where('belongs_id', $belongs_id)
            ->findOrFail($id)
            ->update([
                'name' => $request->input('name'),
            ]);

        return $this->success();
    }

    /**
     * 删除.
     */
    public function destroy($id): JsonResponse
    {
        $loginUser = AuthInfoServices::loginUser($this->guard);
        $belongs_type = $loginUser->getFileBelongType();
        $belongs_id = $loginUser->getFileBelongId();
        File::query()
            ->where('belongs_type', $belongs_type)
            ->where('belongs_id', $belongs_id)
            ->findOrFail($id)
            ->delete();

        return $this->success();
    }
}