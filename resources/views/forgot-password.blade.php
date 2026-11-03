<x-layout title="Forgot Password" hide-footer="true">
    <div class="d-flex flex-column justify-content-center align-items-center bg-primary bg-gradient p-4 flex-grow-1">
        <h3 class="fw-bold text-center text-dark mb-4">Reset your password</h3>

        <div class="card shadow rounded-4 border-0" style="max-width: 400px; width: 100%;">
            <div class="card-body p-4">
                @if (session('success'))
                    <div class="alert alert-success small">{{ session('success') }}</div>
                @endif

                <p class="small text-muted">Enter your email address and we will send you a link to reset your password.</p>

                <form action="/forgot-password" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control bg-light border-0" id="email" name="email"
                            value="{{ old('email') }}" placeholder="Enter your email" required>
                        @error('email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-dark w-100 rounded-pill py-2">Send reset link</button>

                    <p class="text-center small mt-3 mb-0">
                        <a href="/login" class="text-decoration-none">Back to login</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</x-layout>
