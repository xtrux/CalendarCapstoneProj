<x-layout title="Reset Password" hide-footer="true">
    <div class="d-flex flex-column justify-content-center align-items-center bg-primary bg-gradient p-4 flex-grow-1">
        <h3 class="fw-bold text-center text-dark mb-4">Choose a new password</h3>

        <div class="card shadow rounded-4 border-0" style="max-width: 400px; width: 100%;">
            <div class="card-body p-4">
                <form action="/reset-password" method="POST">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control bg-light border-0" id="email" name="email"
                            value="{{ old('email', $email) }}" required>
                        @error('email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New password</label>
                        <input type="password" class="form-control bg-light border-0" id="password" name="password"
                            placeholder="At least 8 characters, one uppercase" required>
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm new password</label>
                        <input type="password" class="form-control bg-light border-0" id="password_confirmation"
                            name="password_confirmation" required>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 rounded-pill py-2">Reset password</button>
                </form>
            </div>
        </div>
    </div>
</x-layout>
