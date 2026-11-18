@if (session('status'))
    <dialog class="modal" data-show>
        <div class="modal-form">
            <div class="modal-head">
                <h2>Upload berhasil</h2>
                <button type="button" class="btn-modal-close" data-close aria-label="Tutup">&times;</button>
            </div>
            <p style="margin: 14px 0 0;">{{ session('status') }}</p>
        </div>
    </dialog>
@endif
