<?php

namespace Ugly\Base\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Ugly\Base\Enums\FormScene;
use Ugly\Base\Exceptions\ApiCustomError;
use Ugly\Base\Traits\ApiResource;

/**
 * 快速表单.
 */
class FormController extends Controller
{
    use ApiResource;

    /**
     * 保存.
     *
     * @throws \Throwable
     */
    public function store(...$params): JsonResponse
    {
        try {
            DB::beginTransaction();
            $model = $this->form(...$params)->setScene(FormScene::Create)->save();
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
    public function update($id, ...$params): JsonResponse
    {
        try {
            DB::beginTransaction();
            $this->form(...$params)->setKey($id)->setScene(FormScene::Edit)->save();
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
    public function destroy($id, ...$params): JsonResponse
    {
        DB::beginTransaction();
        try {
            $this->form(...$params)->setScene(FormScene::Delete)->setKey($id)->delete();
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
