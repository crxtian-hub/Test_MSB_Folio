<x-layout>
    @php
        $imageMaxBytes = \App\Support\UploadLimit::effectiveImageMaxBytes();
        $postMaxBytes = \App\Support\UploadLimit::postMaxBytes();
        $imageMaxLabel = \App\Support\UploadLimit::formatBytes($imageMaxBytes);
        $postMaxLabel = \App\Support\UploadLimit::formatBytes($postMaxBytes);
    @endphp
    <head>
        <title>MSB - Edit info</title>
    </head>
    <body>
        @if ($errors->any())
            <div style="color:red;">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <nav>
            <a href="{{ route('home') }}" class="backendBrand">
                Hello Maria Sofia
            </a>

            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf

                <button class="logoutButton logoutButtonThirdCol" type="submit">
                    <ion-icon name="arrow-back-sharp"></ion-icon>
                    <span>Logout</span>
                </button>
            </form>

            <div class="backendPageLabel">
                Edit info
            </div>

            <a class="infoA" href="{{ route('info') }}">
                INFO
            </a>
        </nav>

        @if (session('status'))
            <p style="color:green;">{{ session('status') }}</p>
        @endif

        @php
            $sections = old('meta.sections', $infoPage->meta['sections'] ?? []);
        @endphp

        <form
            class="variousForm infoEditForm"
            method="POST"
            action="{{ route('admin.info.update') }}"
            enctype="multipart/form-data"
            data-image-max-bytes="{{ $imageMaxBytes }}"
            data-post-max-bytes="{{ $postMaxBytes }}"
            data-image-max-label="{{ $imageMaxLabel }}"
            data-post-max-label="{{ $postMaxLabel }}"
        >
            @csrf
            @method('PUT')

            <div class="allPhotosInputsContainers infoEditPhotoColumn">
                <div class="miniParagraphContainer">
                    <label class="miniSecTit">Profile image</label>
                    <small>Max {{ $imageMaxLabel }} per image.</small>
                    <label class="fileSquare {{ $infoPage->photo_path ? 'has-preview' : '' }}" id="infoPhotoPreviewBox">
                        <input class="photoInput" type="file" name="photo" id="info_photo_input" accept="image/*">
                        <img
                            class="coverPreview"
                            id="infoPhotoPreview"
                            alt="Info photo preview"
                            @if ($infoPage->photo_path) src="{{ asset('storage/' . $infoPage->photo_path) }}?v={{ $infoPage->updated_at?->timestamp }}" @endif
                        >
                        <span class="filePlus" aria-hidden="true">
                            <ion-icon name="add-sharp"></ion-icon>
                        </span>
                    </label>
                </div>
            </div>

            <div class="writtenContainer infoEditWrittenColumn">
                <div class="allPhotosInputsContainers">
                    <div class="miniParagraphContainer">
                        <label class="miniSecTit">Header</label>
                        <div>
                            <input class="writPlaHold" name="subtitle" placeholder="SUBTITLE" value="{{ old('subtitle', $infoPage->subtitle) }}">
                        </div>
                    </div>

                    <div class="miniParagraphContainer">
                        <label class="miniSecTit">Text sections</label>
                        <div id="info-sections" class="infoSectionsEditor">
                            @forelse ($sections as $i => $section)
                                <div class="infoSectionEditor">
                                    <input class="writPlaHold" name="meta[sections][{{ $i }}][title]" placeholder="TITLE" value="{{ $section['title'] ?? '' }}">
                                    <textarea class="infoTextarea" name="meta[sections][{{ $i }}][description]" placeholder="DESCRIPTION">{{ $section['description'] ?? '' }}</textarea>
                                    <button type="button" class="removeCredit infoSectionRemove">
                                        <ion-icon name="close-sharp"></ion-icon>
                                    </button>
                                </div>
                            @empty
                                <div class="infoSectionEditor">
                                    <input class="writPlaHold" name="meta[sections][0][title]" placeholder="TITLE">
                                    <textarea class="infoTextarea" name="meta[sections][0][description]" placeholder="DESCRIPTION"></textarea>
                                    <button type="button" class="removeCredit infoSectionRemove">
                                        <ion-icon name="close-sharp"></ion-icon>
                                    </button>
                                </div>
                            @endforelse
                        </div>

                        <button class="raCredit addCredit infoAddSectionButton" type="button" id="add-info-section">
                            <ion-icon class="addCredit" name="add-sharp"></ion-icon>
                        </button>
                    </div>

                    <div class="miniParagraphContainer contactPadding">
                        <label class="miniSecTit">Contacts</label>
                        <div>
                            <input class="writPlaHold" name="email" placeholder="EMAIL" value="{{ old('email', $infoPage->email) }}">
                        </div>
                        <div>
                            <input class="writPlaHold" name="instagram_url" placeholder="INSTAGRAM URL" value="{{ old('instagram_url', $infoPage->instagram_url) }}">
                        </div>
                    </div>
                </div>
            </div>

            <button class="saveButton" type="submit">Save</button>
        </form>

        <script>
            (function () {
                const uploadForm = document.querySelector('.infoEditForm');
                const photoInput = document.getElementById('info_photo_input');
                const photoPreview = document.getElementById('infoPhotoPreview');
                const photoBox = document.getElementById('infoPhotoPreviewBox');
                const imageMaxBytes = Number(uploadForm?.dataset.imageMaxBytes || 0);
                const postMaxBytes = Number(uploadForm?.dataset.postMaxBytes || 0);
                const imageMaxLabel = uploadForm?.dataset.imageMaxLabel || '';
                const postMaxLabel = uploadForm?.dataset.postMaxLabel || '';

                if (photoInput && photoPreview && photoBox) {
                    photoInput.addEventListener('change', () => {
                        const file = photoInput.files && photoInput.files[0];

                        if (!file) {
                            photoPreview.removeAttribute('src');
                            photoBox.classList.remove('has-preview');
                            return;
                        }

                        const objectUrl = URL.createObjectURL(file);
                        photoPreview.src = objectUrl;
                        photoBox.classList.add('has-preview');
                    });
                }

                if (uploadForm) {
                    uploadForm.addEventListener('submit', (event) => {
                        const file = photoInput?.files?.[0];
                        const errors = [];

                        if (file && imageMaxBytes && file.size > imageMaxBytes) {
                            errors.push(`${file.name} exceeds the ${imageMaxLabel} per-image limit.`);
                        }

                        if (file && postMaxBytes && file.size > postMaxBytes) {
                            errors.push(`${file.name} exceeds the ${postMaxLabel} request limit.`);
                        }

                        if (errors.length === 0) {
                            return;
                        }

                        event.preventDefault();
                        window.alert(errors.join('\n'));
                    });
                }

                const bindRemoveButtons = (root) => {
                    root.querySelectorAll('.removeCredit').forEach((button) => {
                        if (button.dataset.bound === 'true') {
                            return;
                        }

                        button.dataset.bound = 'true';
                        button.addEventListener('click', () => {
                            button.closest('.infoRow, .infoSectionEditor, .creditRow')?.remove();
                        });
                    });
                };

                const sectionsContainer = document.getElementById('info-sections');
                const addSectionButton = document.getElementById('add-info-section');
                let sectionIndex = sectionsContainer ? sectionsContainer.querySelectorAll('.infoSectionEditor').length : 0;

                if (sectionsContainer) {
                    bindRemoveButtons(sectionsContainer);
                }

                if (sectionsContainer && addSectionButton) {
                    addSectionButton.addEventListener('click', () => {
                        const item = document.createElement('div');
                        item.className = 'infoSectionEditor';
                        item.innerHTML = `
                            <input class="writPlaHold" name="meta[sections][${sectionIndex}][title]" placeholder="TITLE">
                            <textarea class="infoTextarea" name="meta[sections][${sectionIndex}][description]" placeholder="DESCRIPTION"></textarea>
                            <button type="button" class="removeCredit infoSectionRemove">
                                <ion-icon name="close-sharp"></ion-icon>
                            </button>
                        `;
                        sectionsContainer.appendChild(item);
                        sectionIndex += 1;
                        bindRemoveButtons(item);
                    });
                }
            })();
        </script>
    </body>
</x-layout>
