<?php

namespace Ugly\Base\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Ugly\Base\Contracts\SimpleForm;
use Ugly\Base\Exceptions\ApiCustomError;
use Ugly\Base\Traits\ApiResource;

/**
 * 简单表单控制器.
 */
class SimpleFormController extends Controller
{
    use ApiResource;

    /**
     * 表单配置.
     */
    protected array $form = [];

    /**
     * 获取表单默认值.
     *
     * @return JsonResponse|void
     */
    public function index(string $type)
    {
        if (isset($this->form[$type]) && isset(class_implements($this->form[$type])[SimpleForm::class])) {
            $form = new $this->form[$type];

            return $this->success($form->default());
        }
        abort(Response::HTTP_NOT_FOUND);
    }

    /**
     * 更新表单.
     *
     * @return JsonResponse|void
     *
     * @throws \Throwable
     */
    public function update(Request $request, $type)
    {
        if (isset($this->form[$type]) && isset(class_implements($this->form[$type])[SimpleForm::class])) {
            $form = new $this->form[$type];
            // 表单验证
            try {
                DB::beginTransaction();
                $form->handle($form->policy($request));
                DB::commit();

                return $this->success($form->default());
            } catch (ApiCustomError $e) {
                DB::rollBack();

                return $this->failed($e->getMessage());
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }
        abort(Response::HTTP_NOT_FOUND);
    }
}
