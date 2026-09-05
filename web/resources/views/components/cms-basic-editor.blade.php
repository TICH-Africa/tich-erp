@props([
    'name',
    'id' => null,
    'value' => '',
    'label' => null,
    'required' => false,
    'minHeight' => '8rem',
])

@php
    $inputId = $id ?: $name;
    $content = old($name, $value ?? '');
@endphp

@if ($label)
    <label class="tich-label" for="{{ $inputId }}">{{ $label }}</label>
@endif

<div
    class="tich-cms-editor tich-cms-editor--basic"
    data-cms-editor
    data-cms-variant="basic"
    data-input-id="{{ $inputId }}"
>
    <div class="tich-cms-toolbar" role="toolbar" aria-label="Basic formatting">
        <div class="tich-cms-toolbar__group">
            <button type="button" data-cmd="bold" title="Bold"><strong>B</strong></button>
            <button type="button" data-cmd="italic" title="Italic"><em>I</em></button>
            <button type="button" data-cmd="insertUnorderedList" title="Bullet list">• List</button>
            <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
            <label class="tich-cms-toolbar__swatch" title="Font colour">
                A
                <input type="color" data-cmd="foreColor" value="#494c50">
            </label>
            <button type="button" data-cmd="removeFormat" title="Clear formatting">Clear</button>
        </div>
    </div>

    <div
        class="tich-cms-surface tich-prose"
        contenteditable="true"
        role="textbox"
        aria-multiline="true"
        aria-label="{{ $label ?: 'Formatted text' }}"
        data-cms-surface
        style="min-height: {{ $minHeight }}; max-height: 16rem;"
    >{!! $content !!}</div>

    <textarea
        id="{{ $inputId }}"
        name="{{ $name }}"
        class="tich-cms-hidden-input"
        @if ($required) required @endif
    >{{ $content }}</textarea>
</div>
