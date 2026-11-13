@if ($aiEnabled)
    <input class="d-none" id="imageSearchInput" type="file" accept="image/*" capture="environment" data-url="{{ route('search.image') }}">

    <div class="modal fade" id="imageSearchModal" tabindex="-1" aria-labelledby="imageSearchTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0">
                <div class="modal-header">
                    <h2 class="modal-title h5 fw-bold" id="imageSearchTitle"><i class="bi bi-camera text-ky me-2"></i>{{ __('Cari dengan Gambar') }}</h2>
                    <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="{{ __('Tutup') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-3 align-items-start mb-3">
                        <img class="rounded border" data-preview alt="" style="width: 90px; height: 120px; object-fit: cover; background: #eef3f9">
                        <div class="flex-grow-1" data-status>
                            <div class="d-flex align-items-center gap-2 text-secondary">
                                <span class="spinner-border spinner-border-sm"></span> {{ __('Mengenali buku dari foto…') }}
                            </div>
                        </div>
                    </div>
                    <div class="row row-cols-2 row-cols-md-4 g-3" data-results></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-ky" type="button" data-retake><i class="bi bi-arrow-repeat me-1"></i>{{ __('Foto lain') }}</button>
                    <a class="btn btn-ky d-none" data-all href="#">{{ __('Lihat semua hasil') }}</a>
                </div>
            </div>
        </div>
    </div>
@endif
