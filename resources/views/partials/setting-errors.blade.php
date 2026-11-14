@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-bold mb-1"><i class="fas fa-exclamation-circle mr-1"></i> Setting tidak disimpan:</div>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
