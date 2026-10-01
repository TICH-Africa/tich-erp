@props([
    'name',
    'id' => null,
    'value' => '',
    'label' => null,
    'required' => false,
    'minHeight' => '8rem',
    'maxHeight' => '16rem',
    'withHeadings' => false,
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
            <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
        </div>
        @if ($withHeadings)
            <div class="tich-cms-toolbar__group">
                <select data-action="style" title="Styles">
                    <option value="">Styles</option>
                    <option value="p">Normal</option>
                    <option value="h1">Heading 1</option>
                    <option value="h2">Heading 2</option>
                    <option value="h3">Heading 3</option>
                </select>
            </div>
        @endif
        <div class="tich-cms-toolbar__group">
            <button type="button" data-cmd="insertUnorderedList" title="Bullet list">• List</button>
            <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
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
        style="min-height: {{ $minHeight }}; max-height: {{ $maxHeight }};"
    >{!! \App\Support\SafeHtml::clean($content) !!}</div>

    <textarea
        id="{{ $inputId }}"
        name="{{ $name }}"
        class="tich-cms-hidden-input"
        @if ($required) required @endif
    >{{ $content }}</textarea>
</div>
