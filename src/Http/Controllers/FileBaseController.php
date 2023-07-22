<?php

namespace Ugly\Base\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Ugly\Base\Models\File;
use Ugly\Base\Services\AuthInfoServices;
use Ugly\Base\Traits\ApiResource;

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
        return md5($file->getClientOriginalName()).'.'.$file->getClientOriginalExtension();
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
        ])->where('belongs_type', $belongs_type)
            ->where('belongs_id', $belongs_id)
            ->orderByDesc('id');

        return $this->paginate($query);
    }

    /**
     * 上传文件.
     */
    public function store(Request $request): JsonResponse
    {
        $loginUser = AuthInfoServices::tryLoginUser($this->guard);
        if ($request->hasFile($this->fileKey)) {
            $file = $request->file($this->fileKey);
            $sha1 = sha1_file($file->getRealPath());
            $path = $this->customPath();
            $name = $this->customName($file);
            $path = $file->storeAs($path, $name, $this->disk);

            File::query()->create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'sha1' => $sha1,
                'belongs_type' => $loginUser?->getFileBelongType(),
                'belongs_id' => $loginUser?->getFileBelongId(),
            ]);

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
