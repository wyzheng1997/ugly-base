<?php

namespace Ugly\Base\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Ugly\Base\Enums\FormScene;
use Ugly\Base\Exceptions\ApiCustomError;
use Ugly\Base\Services\FormService;
use Ugly\Base\Traits\ApiResource;

/**
 * 快速表单.
 */
abstract class FormController extends Controller
{
    use ApiResource;

    /**
     * 抽象表单配置类
     */
    abstract protected function form(): FormService;

    /**
     * 保存.
     *
     * @throws \Throwable
     */
    public function store(): JsonResponse
    {
        $model = DB::transaction(fn () => $this->form()->setScene(FormScene::Create)->save());

        return $this->success([
            $model->getKeyName() => $model->getKey(),
        ], Response::HTTP_CREATED);
    }

    /**
     * 更新.
     *
     * @throws \Throwable
     */
    public function update($id): JsonResponse
    {
        DB::transaction(function () use ($id) {
            $form = $this->form();
            $ids = explode(',', $id);
            throw_if(count($ids) > 100, new ApiCustomError('批量编辑不能超过100条！'));
            foreach ($ids as $key) {
                $form->setKey($key)->setScene(FormScene::Edit)->save();
            }
        });

        return $this->success();
    }

    /**
     * 删除.
     *
     * @throws \Throwable
     */
    public function destroy($id): JsonResponse
    {
        DB::transaction(function () use ($id) {
            $form = $this->form();
            $ids = explode(',', $id);
            throw_if(count($ids) > 100, new ApiCustomError('批量删除不能超过100条！'));
            foreach ($ids as $key) {
                $form->setKey($key)->setScene(FormScene::Delete)->delete();
            }
        });

        return $this->success();
    }
}
