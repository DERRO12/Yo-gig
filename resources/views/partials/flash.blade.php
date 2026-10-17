@if (session('success') || session('error') || $errors->any())
    <div class="container flash-wrap">
        @if (session('success'))
            <div class="alert alert--success" role="status">✅ <span>{{ session('success') }}</span></div>
        @endif

        @if (session('error'))
            <div class="alert alert--error" role="alert">⚠️ <span>{{ session('error') }}</span></div>
        @endif

        @if ($errors->any())
            <div class="alert alert--error" role="alert">
                <div>
                    <strong>Please fix the following:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
@endif
