<?php

namespace Ugly\Base\Services;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;
use Ugly\Base\Exceptions\ApiCustomError;

class FormService
{
    /**
     * 创建模式.
     */
    public const MODE_CREATE = '__create__';

    /**
     * 编辑模式.
     */
    public const MODE_EDIT = '__edit__';

    /**
     * 删除模式.
     */
    public const MODE_DELETE = '__delete__';

    /**
     * 当前模式.
     */
    private string $mode = '';

    /**
     * 表单钩子.
     */
    private array $formCallback = [];

    /**
     * 表单验证规则.
     */
    private array $validateRules = [];

    /**
     * 表单验证消息.
     */
    private array $validateMessages = [];

    /**
     * 表单验证属性.
     */
    private array $validateAttribute = [];

    /**
     * 需要忽略的字段.
     */
    private array $ignoreFields = [];

    /**
     * 需要处理的模型.
     */
    private mixed $model;

    /**
     * 操作model的主键值.
     */
    private mixed $key = null;

    /**
     * 允许行内编辑的字段.
     */
    private array $allowInlineEditFields = [];

    /**
     * 行内编辑表单钩子白名单
     */
    private array $lineEditCallbackWhitelist = ['validate', 'policy'];


    /**
     * 构造函数.
     */
    private function __construct($model)
    {
        $this->model = is_string($model) ? app($model) : $model;
    }

    /**
     * 创建实例.
     */
    public static function make($model): static
    {
        return new static($model);
    }

    /**
     * 获取模型.
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * 设置主键（编辑/删除）.
     *
     * @return $this
     */
    public function setKey(int|string $key): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * 获取模型ID.
     */
    public function getKey(): mixed
    {
        return $this->key;
    }

    /**
     * 设置form类型.
     */
    public function setMode(string $formMode): static
    {
        $this->mode = $formMode;

        return $this;
    }

    /**
     * 是否是创建.
     */
    public function isCreate(): bool
    {
        return static::MODE_CREATE === $this->mode;
    }

    /**
     * 是否是编辑.
     */
    public function isEdit(): bool
    {
        return static::MODE_EDIT === $this->mode;
    }

    /**
     * 是否是删除.
     */
    public function isDelete(): bool
    {
        return static::MODE_DELETE === $this->mode;
    }

    /**
     * 设置模型策略.
     */
    public function policy(\Closure $closure): static
    {
        $this->formCallback['policy'] = $closure;

        return $this;
    }

    /**
     * 设置验证规则.
     */
    public function validate(\Closure $closure, array $messages = [], array $attributes = []): static
    {
        $this->formCallback['validate'] = $closure;
        $this->validateMessages = array_merge($this->validateMessages, $messages);
        $this->validateAttribute = array_merge($this->validateAttribute, $attributes);

        return $this;
    }

    /**
     * 设置处理输入的回调.
     */
    public function handleInput(\Closure $closure): static
    {
        $this->formCallback['handleInput'] = $closure;

        return $this;
    }

    /**
     * 表单唯一验证.
     */
    public function unique($column, string $table = null): Unique
    {
        $table = $table ?: get_class($this->getModel());
        $rule = Rule::unique($table, $column);
        if ($this->isEdit()) {
            $rule->ignore($this->getKey());
        }

        return $rule;
    }

    /**
     * 设置允许行内编辑的字段.
     */
    public function inlineEdit(array $fields): static
    {
        foreach ($fields as $key => $val) {
            if (is_numeric($key)) {
                $this->allowInlineEditFields[$val] = [];
            } elseif (is_array($val)) {
                $this->allowInlineEditFields[$key] = $val;
            }
        }

        return $this;
    }

    /**
     * 保存.
     */
    public function save()
    {
        // 用户存储合法的请求数据
        $formData = [];

        // 请求实例
        $request = request();

        // 约定PATCH请求为行内编辑
        $isLineEdit = false;
//        $this->isLineEdit = $request->isMethod('PATCH')

        // 编辑模式下先获取需要编辑的资源.
        if ($this->isEdit()) {
            $this->model = $this->model->find($this->key);
        }

        // 获取表单验证规则配置.
        if ($validateFn = $this->checkFormCallback('validate')) {
            $this->validateRules = call_user_func($validateFn);
            if ($isLineEdit) {
                $this->validateRules = $this->generateLineEditValidateRules($request);
            }
        }

        // 执行表单验证.
        if (! empty($this->validateRules)) {
            $formData = $request->validate($this->decodeIgnoreFields($this->validateRules), $this->validateMessages, $this->validateAttribute);
        }

        // 执行策略检查
        if ($policyFn = $this->checkFormCallback('policy')) {
            $result = call_user_func($policyFn, $this, $this->model);
            if ($result === false || is_string($result)) {
                throw new ApiCustomError(is_string($result) ? $result : '非法操作！');
            }
        }

        // 处理输入的值
        if ($handleInputFn = $this->checkFormCallback('handleInput')) {
            $formData = $this->decodeIgnoreFields(call_user_func($handleInputFn, $this, $formData));
        }

        // 保存前钩子.
        if ($savingFn = $this->checkFormCallback('saving')) {
            call_user_func($savingFn, $this, $this->model);
        }

        // 填充数据.
        $allowData = $this->delIgnoreFields($formData);
        if (! empty($allowData)) {
            $this->model = $this->model->fill($allowData);
        }

        // 保存后钩子.
        if ($savedFn = $this->checkFormCallback('saved')) {
            call_user_func($savedFn, $this, $request);
        }

        return $this->model;
    }

    /**
     * 保存前回调函数.
     */
    public function saving(\Closure $callback = null): static
    {
        $this->formCallback['saving'] = $callback;

        return $this;
    }

    /**
     * 保存后回调函数.
     */
    public function saved(\Closure $callback = null): static
    {
        $this->formCallback['saved'] = $callback;

        return $this;
    }

    /**
     * 删除前回调.
     */
    public function deleting(\Closure $callback = null): static
    {
        $this->formCallback['deleting'] = $callback;

        return $this;
    }

    /**
     * 删除后回调.
     */
    public function deleted(\Closure $callback = null): static
    {
        $this->formCallback['deleted'] = $callback;

        return $this;
    }

    /**
     * 删除.
     */
    public function delete()
    {
        $this->model = $this->model->find($this->key);
        // 执行策略检查
        if ($policyFn = $this->checkFormCallback('policy')) {
            $result = call_user_func($policyFn, $this, $this->model);
            if ($result === false || is_string($result)) {
                throw new ApiCustomError(is_string($result) ? $result : '非法操作！');
            }
        }
        // 删除前
        if ($deletingFn = $this->checkFormCallback('deleting')) {
            call_user_func($deletingFn, $this);
        }
        // 删除
        $res = $this->model->delete();

        // 删除后
        if ($deletedFn = $this->checkFormCallback('deleted')) {
            call_user_func($deletedFn, $this->key);
        }

        return $res;
    }

    /**
     * 检查表单回调函数是否可以调用，如果可以调用就返回可调用函数.
     */
    private function checkFormCallback(string $key, $isLineEdit = false): bool|\Closure
    {
        if($isLineEdit && !in_array($key, $this->lineEditCallbackWhitelist)) {
            return in_array($key, data_get($this->allowInlineEditFields, request('field')));
        }

        if (isset($this->formCallback[$key]) && $this->formCallback[$key] instanceof \Closure) {
            return $this->formCallback[$key];
        }

        return false;
    }

    /**
     * 解析需要忽略的字段.
     */
    private function decodeIgnoreFields(array $fields): array
    {
        $results = [];
        foreach ($fields as $key => $val) {
            if (str_starts_with($key, '!')) {
                $results[substr($key, 1)] = $val;
                if (! str_contains($key, '.')) {
                    $this->ignoreFields[] = $key;
                }
            } else {
                $results[$key] = $val;
            }
        }

        return $results;
    }

    private function delIgnoreFields(array $formData): array
    {
        if (empty($this->ignoreFields)) {
            return $formData;
        }

        return array_diff_key($formData, array_flip($this->ignoreFields));
    }

    /**
     * 生成行内编辑表单验证规则.
     */
    private function generateLineEditValidateRules(Request $request): array
    {
        $validMessage = [];
        if (! $request->has('field')) {
            $validMessage['field'] = '`field` is required';
        }
        if (! $request->has('value')) {
            $validMessage['value'] = '`value` is required';
        }
        if (! empty($validMessage)) {
            throw ValidationException::withMessages($validMessage);
        }

        $field = $request->input('field');
        $value = $request->input('value');

        // 检查是否允许行内编辑
        if (! in_array($field, array_keys($this->allowInlineEditFields))) {
            throw new ApiCustomError($field.'不允许修改！');
        }

        // 合并到请求中
        $request->merge([$field => $value]);

        // 返回字段对应的验证规则
        return array_filter($this->validateRules, function ($key) use ($field) {
            return str_starts_with($key, $field) || str_starts_with($key, "!$field");
        }, ARRAY_FILTER_USE_KEY);
    }
}
