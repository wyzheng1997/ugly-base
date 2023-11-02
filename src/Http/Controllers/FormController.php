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
        try {
            DB::beginTransaction();
            $model = $this->form()->setScene(FormScene::Create)->save();
            DB::commit();
        } catch (ApiCustomError $e) {
            DB::rollBack();

            return $this->failed($e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

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
        try {
            DB::beginTransaction();
            $this->form()->setKey($id)->setScene(FormScene::Edit)->save();
            DB::commit();

            return $this->success(Response::HTTP_OK);
        } catch (ApiCustomError $e) {
            DB::rollBack();

            return $this->failed($e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 删除.
     *
     * @throws \Throwable
     */
    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $this->form()->setScene(FormScene::Delete)->setKey($id)->delete();
            DB::commit();

            return $this->success();
        } catch (ApiCustomError $exception) {
            DB::rollBack();

            return $this->failed($exception->getMessage());
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }
}
