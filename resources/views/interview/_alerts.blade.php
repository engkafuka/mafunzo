@if (session('status'))
    <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
@endif
