<?php

namespace Ugly\Base\Services;

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
     * 表单事件.
     */
    private array $formEvent = [];

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
     * 表单额外字段.
     */
    private array $extraFields = [];

    /**
     * 忽略的字段.
     */
    private array $ignoreFields = [];

    /**
     * 需要处理的模型.
     */
    private $model;

    /**
     * 操作model的ID.
     */
    private mixed $_id = null;

    /**
     * 允许行内编辑的字段.
     */
    private array $allowInlineEditFields = [];

    /**
     * 构建器回调.
     */
    private ?\Closure $builderCallback;

    /**
     * 构造函数.
     */
    private function __construct($model, \Closure $builderCallback = null)
    {
        $this->model = is_string($model) ? app($model) : $model;
        $this->builderCallback = $builderCallback;
    }

    /**
     * 创建实例.
     *
     * @param  mixed  ...$params
     */
    public static function make(...$params): static
    {
        return new static(...$params);
    }

    /**
     * 获取模型.
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * 设置ID（编辑/删除）.
     *
     * @return $this
     */
    public function setKey(int|string $id): static
    {
        $this->_id = $id;

        return $this;
    }

    /**
     * 获取模型ID.
     */
    public function getKey(): mixed
    {
        return $this->_id;
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
     * 设置验证规则.
     */
    public function validate(array $rules, array $messages = [], array $attributes = []): static
    {
        $this->validateRules = array_merge($this->validateRules, $rules);
        $this->validateMessages = array_merge($this->validateMessages, $messages);
        $this->validateAttribute = array_merge($this->validateAttribute, $attributes);

        return $this;
    }

    /**
     * 设置额外请求字段以及默认值.
     *
     * @example $form->extraFields(['note', 'attach' => [], 'avatar' => 'default.png']);
     */
    public function extraFields(array $fields): static
    {
        $this->extraFields = array_merge($this->extraFields, $fields);

        return $this;
    }

    /**
     * 设置忽略字段.
     */
    public function ignoreFields(array $fields): static
    {
        $this->ignoreFields = array_merge($this->ignoreFields, $fields);

        return $this;
    }

    /**
     * 设置允许行内编辑的字段.
     */
    public function inlineEdit(array $fields): static
    {
        $this->allowInlineEditFields = array_merge($this->allowInlineEditFields, $fields);

        return $this;
    }

    /**
     * 保存.
     */
    public function save()
    {
        if ($this->isEdit()) {
            $this->model = $this->model->findOrFail($this->_id);
        }
        $this->builderCallback && call_user_func($this->builderCallback, $this);

        // 当前请求.
        $request = request();

        // 行内编辑模式 start >>>>>>>>>>>>>>>>>>.
        if ($this->isEdit() && $request->boolean('_inline_edit_')) {
            $field = $request->input('field');
            if (! in_array($field, $this->allowInlineEditFields)) {
                throw new \Exception($field.'不允许编辑.');
            }
            if (isset($this->validateRules[$field])) { // 单独验证字段.
                $request->validate(['value' => $this->validateRules[$field]]);
            }
            $this->model->update([$field => $request->input('value')]);

            return $this->model;
        }
        // <<<<<<<<<<<<<<<<<< end 行内编辑模式.

        // 表单数据.
        $formData = [];

        // 字段验证
        if (! empty($this->validateRules)) {
            $formData = $request->validate($this->validateRules, $this->validateMessages, $this->validateAttribute);
        }

        // 获取额外请求字段.
        foreach ($this->extraFields as $field => $default) {
            if (is_numeric($field)) {
                $field = $default;
                $default = null;
            }
            $formData[$field] = $request->input($field, $default);
        }

        // 保存前钩子，调整formData.
        if (isset($this->formEvent['saving']) && $this->formEvent['saving'] instanceof \Closure) {
            $formData = call_user_func($this->formEvent['saving'], $this, $formData, $request);
            if (! is_array($formData)) {
                throw new \Exception('saving钩子必须返回数组');
            }
        }

        // 忽略字段.
        if (! empty($this->ignoreFields)) {
            foreach ($this->ignoreFields as $field) {
                unset($formData[$field]);
            }
        }

        if ($this->isEdit()) { // 更新
            foreach ($formData as $key => $val) {
                $this->model->$key = $val;
            }
            $this->model->save();
        } else {
            // 创建
            $this->model = $this->model->create($formData);
        }

        // 保存后钩子.
        if (isset($this->formEvent['saved']) && $this->formEvent['saved'] instanceof \Closure) {
            call_user_func($this->formEvent['saved'], $this, $request);
        }

        return $this->model;
    }

    /**
     * 保存前回调函数.
     */
    public function saving(\Closure $callback = null): static
    {
        $this->formEvent['saving'] = $callback;

        return $this;
    }

    /**
     * 保存后回调函数.
     */
    public function saved(\Closure $callback = null): static
    {
        $this->formEvent['saved'] = $callback;

        return $this;
    }

    /**
     * 删除前回调.
     */
    public function deleting(\Closure $callback = null): static
    {
        $this->formEvent['deleting'] = $callback;

        return $this;
    }

    /**
     * 删除后回调.
     */
    public function deleted(\Closure $callback = null): static
    {
        $this->formEvent['deleted'] = $callback;

        return $this;
    }

    /**
     * 删除.
     */
    public function delete()
    {
        $this->model = $this->model->findOrFail($this->_id);
        $this->builderCallback && call_user_func($this->builderCallback, $this);
        // 删除前
        if (isset($this->formEvent['deleting']) && $this->formEvent['deleting'] instanceof \Closure) {
            call_user_func($this->formEvent['deleting'], $this);
        }
        // 删除
        $res = $this->model->delete();

        // 删除后
        if (isset($this->formEvent['deleted']) && $this->formEvent['deleted'] instanceof \Closure) {
            call_user_func($this->formEvent['deleted'], $this);
        }

        return $res;
    }
}
