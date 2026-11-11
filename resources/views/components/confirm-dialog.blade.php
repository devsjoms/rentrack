<link rel="stylesheet" href="{{ asset('css/rentrack-rooms.css') }}">
<dialog class="rt-confirm-dialog" id="rt-confirm-dialog" aria-labelledby="rt-confirm-title" aria-describedby="rt-confirm-message">
    <div class="rt-confirm-inner">
        <span class="rt-confirm-icon" aria-hidden="true">?</span>
        <h2 id="rt-confirm-title" data-confirm-dialog-title>Please confirm</h2>
        <p id="rt-confirm-message" data-confirm-dialog-message>Continue with this action?</p>
        <div class="rt-confirm-actions">
            <button type="button" class="rt-btn rt-btn-secondary" data-confirm-dialog-cancel>Cancel</button>
            <button type="button" class="rt-btn" data-confirm-dialog-accept>Confirm</button>
        </div>
    </div>
</dialog>
<script src="{{ asset('js/rentrack-confirm-dialog.js') }}" defer></script>
