@if (!empty($program->cover_image_url))
    <img src="{{ $program->cover_image_url }}" alt="{{ $program->program_name }}" class="tich-course-card__image tich-program-card__image" loading="lazy">
@else
    <div class="tich-course-card__placeholder tich-program-card__placeholder" aria-hidden="true"></div>
@endif
