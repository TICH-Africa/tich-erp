@if (!empty($program->cover_image_url))
    <img src="{{ $program->cover_image_url }}" alt="{{ $program->program_name }}" class="tich-program-card__image">
@else
    <div class="tich-program-card__placeholder" aria-hidden="true"></div>
@endif
