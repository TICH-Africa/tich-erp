document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('photo_input');
    const profilePhotoField = document.getElementById('profile_photo_file');
    const cropModal = document.getElementById('photo-crop-modal');
    const cropSource = document.getElementById('photo-crop-source');
    const previewWrap = document.getElementById('photo-preview-wrap');
    const photoReadyField = document.getElementById('profile_photo_ready');

    if (!fileInput || !cropModal || !cropSource || !previewWrap || !profilePhotoField) {
        return;
    }

    let cropper = null;

    const closeCropModal = () => {
        cropModal.classList.remove('is-open');
        cropModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-photo-crop-open');

        if (cropper) {
            cropper.destroy();
            cropper = null;
        }

        fileInput.value = '';
    };

    const openCropModal = () => {
        cropModal.classList.add('is-open');
        cropModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-photo-crop-open');
    };

    document.querySelectorAll('[data-close-crop]').forEach((el) => {
        el.addEventListener('click', closeCropModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && cropModal.classList.contains('is-open')) {
            closeCropModal();
        }
    });

    fileInput.addEventListener('change', () => {
        const file = fileInput.files && fileInput.files[0];
        if (!file) {
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert('Please choose an image under 5 MB.');
            fileInput.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            cropSource.src = event.target.result;
            openCropModal();

            if (typeof Cropper === 'undefined') {
                alert('Photo cropping could not load. Check your connection or contact ICT.');
                closeCropModal();
                return;
            }

            if (cropper) {
                cropper.destroy();
            }

            cropper = new Cropper(cropSource, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                responsive: true,
                background: false,
            });
        };
        reader.readAsDataURL(file);
    });

    const applyButton = document.getElementById('photo-crop-apply');
    if (!applyButton) {
        return;
    }

    applyButton.addEventListener('click', () => {
        if (!cropper) {
            return;
        }

        const canvas = cropper.getCroppedCanvas({
            width: 480,
            height: 480,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!canvas) {
            alert('Could not crop this image. Try another photo.');
            return;
        }

        canvas.toBlob((blob) => {
            if (!blob) {
                alert('Could not prepare the cropped photo. Try another image.');
                return;
            }

            const croppedFile = new File([blob], 'profile-photo.jpg', {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });

            const transfer = new DataTransfer();
            transfer.items.add(croppedFile);
            profilePhotoField.files = transfer.files;

            if (photoReadyField) {
                photoReadyField.value = '1';
            }

            const previewUrl = URL.createObjectURL(blob);
            previewWrap.innerHTML = '<img src="' + previewUrl + '" alt="Profile preview" class="tich-employee-profile-photo__img" id="photo-preview">';
            previewWrap.classList.add('is-ready');

            closeCropModal();
        }, 'image/jpeg', 0.88);
    });
});
