@if(session('status'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert"><i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-4" role="alert"><div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Periksa kembali data yang diinput.</div><ul class="mb-0 ps-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
