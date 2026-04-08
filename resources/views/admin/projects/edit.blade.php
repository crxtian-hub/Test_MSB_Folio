<x-layout>
  @php
  $imageMaxBytes = \App\Support\UploadLimit::effectiveProjectImageMaxBytes();
  $postMaxBytes = \App\Support\UploadLimit::postMaxBytes();
  $otherPhotosMaxBytes = \App\Support\UploadLimit::effectiveProjectOtherPhotosMaxBytes();
  $imageMaxLabel = \App\Support\UploadLimit::formatBytes($imageMaxBytes);
  $postMaxLabel = \App\Support\UploadLimit::formatBytes($postMaxBytes);
  $otherPhotosMaxLabel = \App\Support\UploadLimit::formatBytes($otherPhotosMaxBytes);
  @endphp

  @if ($errors->any())
  <div style="color:red;">
    @foreach ($errors->all() as $error)
    <p>{{ $error }}</p>
    @endforeach
  </div>
  @endif
  <title>MSB - Edit project</title>
  
  
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
      Edit project
    </div>

    <a class="infoA" href="{{ route('info') }}">
      INFO
    </a>
      
    </nav>
    
    @if (session('status'))
    <p style="color:green;">{{ session('status') }}</p>
    @endif
    
    <form
      class="variousForm"
      method="POST"
      action="{{ route('admin.projects.update', $project) }}"
      enctype="multipart/form-data"
      data-image-max-bytes="{{ $imageMaxBytes }}"
      data-post-max-bytes="{{ $postMaxBytes }}"
      data-other-photos-max-bytes="{{ $otherPhotosMaxBytes }}"
      data-image-max-label="{{ $imageMaxLabel }}"
      data-post-max-label="{{ $postMaxLabel }}"
      data-other-photos-max-label="{{ $otherPhotosMaxLabel }}"
    >
      @csrf
      @method('PUT')
      <div id="deletePhotoInputs" hidden></div>
      
      <div class="allPhotosInputsContainers">
        <div class="miniParagraphContainer">
          <label class="miniSecTit">cover's image</label>
          <small>Max {{ $imageMaxLabel }} per image.</small>
          <label class="fileSquare {{ $project->cover_image ? 'has-preview' : '' }}" id="coverPreviewBox">
            <input class="photoInput" type="file" name="cover_image" id="cover_image_input" accept="image/*">
            <img class="coverPreview" id="coverPreview" alt="Cover preview" @if ($project->cover_image) src="{{ asset('storage/'.$project->cover_image) }}?v={{ $project->updated_at->timestamp }}" @endif>
            <span class="filePlus" aria-hidden="true">
              <ion-icon name="add-sharp"></ion-icon>
            </span>
          </label>
        </div>
        
        
        {{-- ? multiple photos --}}
        <div class="miniParagraphContainer">
          <label class="miniSecTit">other photos</label>
          <small>Max {{ $imageMaxLabel }} per image. Total upload max {{ $postMaxLabel }}.</small>
          <div class="otherPhotosGrid">
            <label class="fileSquare">
              <input class="photoInput" type="file" name="photos[]" id="other_photos_input" multiple accept="image/*">
              <span class="filePlus" aria-hidden="true">
                <ion-icon name="add-sharp"></ion-icon>
              </span>
            </label>
          <div class="otherPhotosExisting" aria-hidden="true">
            @foreach($project->photos as $photo)
            <button type="button" class="otherPreviewItem otherPreviewItem--existing" data-photo-id="{{ $photo->id }}" aria-label="Delete photo">
              <img
              class="otherPreviewImg"
              src="{{ asset('storage/'.$photo->path) }}"
              alt="{{ $photo->alt ?? 'Photo' }}"
              >
              <ion-icon class="otherPreviewRemove" name="close-sharp" aria-hidden="true"></ion-icon>
            </button>
            @endforeach
          </div>
            <div class="otherPhotosPreview" id="otherPhotosPreview" aria-hidden="true"></div>
          </div>
        </div>
      </div>
      
      <div class="writtenContainer">
        <div class="allPhotosInputsContainers">
          <div class="miniParagraphContainer">
            <label class="miniSecTit">Project info</label>
            <div>
              <input class="writPlaHold" name="title" placeholder="TITLE" value="{{ old('title', $project->title) }}">
            </div>
            
            
            <div>
              <input class="writPlaHold" name="place" placeholder="PLACE" value="{{ old('place', $project->place) }}">
            </div>
            
            <div>
              <input class="writPlaHold" name="date" placeholder="DATE" value="{{ old('date', $project->date) }}">
            </div>
          </div>
          
          
          <div class="miniParagraphContainer">
            <label class="miniSecTit">Credits</label>
            @php
            $credits = old('meta.credits', $project->meta['credits'] ?? []);
            @endphp
            <div id="credits">
              @forelse($credits as $i => $c)
              <div class="creditRow">
                <input class="writPlaHold" name="meta[credits][{{ $i }}][role]" value="{{ $c['role'] ?? '' }}" placeholder="JOB">
                <input class="writPlaHold" name="meta[credits][{{ $i }}][name]" value="{{ $c['name'] ?? '' }}" placeholder="NAME">
                <button type="button" class="removeCredit">
                  <ion-icon name="close-sharp"></ion-icon>
                </button>
              </div>
              @empty
              <div class="creditRow">
                <input class="writPlaHold" name="meta[credits][0][role]" placeholder="JOB">
                <input class="writPlaHold" name="meta[credits][0][name]" placeholder="NAME">
                <button type="button" class="removeCredit">
                  <ion-icon name="close-sharp"></ion-icon>
                </button>
              </div>
              @endforelse
            </div>
            
            <button class="raCredit addCredit" type="button" id="add-credit">
              <ion-icon class="addCredit" name="add-sharp"></ion-icon>
            </button>
          </div>
        </div>
      </div>
      
      <button class="saveButton" type="submit">SAVE</button>
    </form>
    
    <form method="POST" action="{{ route('admin.projects.destroy', $project) }}"
    onsubmit="return confirm('Do you really want to delete this project?');">
    @csrf
    @method('DELETE')
    <button class="deleteButton" type="submit">DELETE</button>
  </form>
  
  <script>
    (function () {
      const uploadForm = document.querySelector('.variousForm');
      const coverInput = document.getElementById('cover_image_input');
      const coverPreview = document.getElementById('coverPreview');
      const coverBox = document.getElementById('coverPreviewBox');
      const imageMaxBytes = Number(uploadForm?.dataset.imageMaxBytes || 0);
      const postMaxBytes = Number(uploadForm?.dataset.postMaxBytes || 0);
      const otherPhotosMaxBytes = Number(uploadForm?.dataset.otherPhotosMaxBytes || 0);
      const imageMaxLabel = uploadForm?.dataset.imageMaxLabel || '';
      const postMaxLabel = uploadForm?.dataset.postMaxLabel || '';
      const otherPhotosMaxLabel = uploadForm?.dataset.otherPhotosMaxLabel || '';
      
      if (coverInput && coverPreview && coverBox) {
        coverInput.addEventListener('change', () => {
          const file = coverInput.files && coverInput.files[0];
          if (!file) {
            coverPreview.removeAttribute('src');
            coverBox.classList.remove('has-preview');
            return;
          }
          
          const objectUrl = URL.createObjectURL(file);
          coverPreview.src = objectUrl;
          coverBox.classList.add('has-preview');
          coverPreview.onload = () => URL.revokeObjectURL(objectUrl);
        });
      }
      
      const otherPhotosInput = document.getElementById('other_photos_input');
      const otherPhotosPreview = document.getElementById('otherPhotosPreview');
      const otherPhotosExisting = document.querySelector('.otherPhotosExisting');
      const deletePhotoInputs = document.getElementById('deletePhotoInputs');
      let otherPhotosFiles = [];
      const deletedExistingPhotoIds = new Set();
      
      function renderOtherPhotos() {
        if (!otherPhotosPreview || !otherPhotosInput) return;
        otherPhotosPreview.innerHTML = '';
        
        otherPhotosFiles.forEach((file, index) => {
          const item = document.createElement('button');
          item.type = 'button';
          item.className = 'otherPreviewItem';
          item.dataset.index = String(index);
          
          const img = document.createElement('img');
          img.className = 'otherPreviewImg';
          const objectUrl = URL.createObjectURL(file);
          img.src = objectUrl;
          img.onload = () => URL.revokeObjectURL(objectUrl);
          
          const icon = document.createElement('ion-icon');
          icon.className = 'otherPreviewRemove';
          icon.setAttribute('name', 'close-sharp');
          icon.setAttribute('aria-hidden', 'true');
          
          item.appendChild(img);
          item.appendChild(icon);
          otherPhotosPreview.appendChild(item);
        });
      }
      
      function syncOtherPhotosInput() {
        if (!otherPhotosInput) return;
        const dataTransfer = new DataTransfer();
        otherPhotosFiles.forEach((file) => dataTransfer.items.add(file));
        otherPhotosInput.files = dataTransfer.files;
      }
      
      if (otherPhotosInput && otherPhotosPreview) {
        otherPhotosInput.addEventListener('change', () => {
          otherPhotosFiles = Array.from(otherPhotosInput.files || []);
          renderOtherPhotos();
        });
        
        otherPhotosPreview.addEventListener('click', (event) => {
          const item = event.target.closest('.otherPreviewItem');
          if (!item) return;
          const index = Number(item.dataset.index);
          if (Number.isNaN(index)) return;
          
          otherPhotosFiles.splice(index, 1);
          syncOtherPhotosInput();
          renderOtherPhotos();
        });
      }

      function getUploadErrors() {
        const errors = [];
        const files = [
          ...(coverInput?.files ? Array.from(coverInput.files) : []),
          ...otherPhotosFiles,
        ];

        for (const file of files) {
          if (imageMaxBytes && file.size > imageMaxBytes) {
            errors.push(`${file.name} exceeds the ${imageMaxLabel} per-image limit.`);
          }
        }

        const otherPhotosTotalSize = otherPhotosFiles.reduce((sum, file) => sum + file.size, 0);

        if (otherPhotosMaxBytes && otherPhotosTotalSize > otherPhotosMaxBytes) {
          errors.push(`Other photos total ${Math.ceil(otherPhotosTotalSize / (1024 * 1024))} MB, above the ${otherPhotosMaxLabel} limit.`);
        }

        const totalSize = files.reduce((sum, file) => sum + file.size, 0);

        if (postMaxBytes && totalSize > postMaxBytes) {
          errors.push(`Selected files total ${Math.ceil(totalSize / (1024 * 1024))} MB, above the ${postMaxLabel} request limit.`);
        }

        return errors;
      }

      if (uploadForm) {
        uploadForm.addEventListener('submit', (event) => {
          const errors = getUploadErrors();

          if (errors.length === 0) {
            return;
          }

          event.preventDefault();
          window.alert(errors.join('\n'));
        });
      }

      function syncDeletedPhotoInputs() {
        if (!deletePhotoInputs) return;
        deletePhotoInputs.innerHTML = '';

        deletedExistingPhotoIds.forEach((photoId) => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'delete_photo_ids[]';
          input.value = String(photoId);
          deletePhotoInputs.appendChild(input);
        });
      }

      if (otherPhotosExisting && deletePhotoInputs) {
        otherPhotosExisting.addEventListener('click', (event) => {
          const item = event.target.closest('.otherPreviewItem--existing');
          if (!item) return;

          const photoId = item.dataset.photoId;
          if (!photoId) return;

          deletedExistingPhotoIds.add(photoId);
          syncDeletedPhotoInputs();
          item.remove();
        });
      }
      
      const creditsEl = document.getElementById('credits');
      const addBtn = document.getElementById('add-credit');
      
      function wireRemove(btn) {
        btn.addEventListener('click', () => {
          const row = btn.closest('.creditRow');
          const rows = creditsEl.querySelectorAll('.creditRow');
          if (rows.length === 1) {
            row.querySelectorAll('input').forEach(i => i.value = '');
            return;
          }
          row.remove();
        });
      }
      
      if (creditsEl && addBtn) {
        creditsEl.querySelectorAll('.removeCredit').forEach(wireRemove);
        
        addBtn.addEventListener('click', () => {
          const i = creditsEl.querySelectorAll('.creditRow').length;
          
          const row = document.createElement('div');
          row.className = 'creditRow';
          row.style.cssText = 'display:flex; gap:1vw;';
          row.innerHTML = `
        <input class="writPlaHold" name="meta[credits][${i}][role]" placeholder="JOB">
        <input class="writPlaHold" name="meta[credits][${i}][name]" placeholder="NAME">
                <button type="button" class="removeCredit">
                  <ion-icon name="close-sharp"></ion-icon>
                </button>
      `;
          creditsEl.appendChild(row);
          wireRemove(row.querySelector('.removeCredit'));
        });
      }
    })();
  </script>
</x-layout>
