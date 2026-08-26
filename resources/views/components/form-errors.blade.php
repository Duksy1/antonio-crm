@if($errors->any())<div class="form-errors"><strong>Provjerite unesene podatke:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
