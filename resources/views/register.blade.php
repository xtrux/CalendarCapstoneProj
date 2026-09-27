<x-layout title="Sign Up" hide-nav="true" hide-footer="true">
    <div class="d-flex flex-column justify-content-center align-items-center bg-primary bg-gradient  p-4"
        style="min-height: 100vh;">
        <h2 class="fw-bold text-center text-dark mb-4">Ready to keep track of time?</h2>

        <h3 class="fw-bold text-center text-dark mb-4">Sign up for an account</h3>

        <div class="card shadow rounded-4 border-0" style="max-width: 400px; width: 100%;">
            <div class="card-body p-4">

                <form action="/register" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control bg-light border-0" id="username" name="username"
                            placeholder="Enter your Username" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control bg-light border-0" id="email" name="email"
                            placeholder="Enter your email" required>
                    </div>

                    <div class="mb-2">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control bg-light border-0" id="password" name="password"
                            placeholder="Enter your password" required>
                    </div>

                    <div class="mb-2">
                        <label for="password_confirmation" class="form-label">Password</label>
                        <input type="password" class="form-control bg-light border-0" id="password_confirmation" name="password_confirmation"
                            placeholder="Enter your password again" required>
                    </div>
                   
                    <button type="submit" class="btn btn-dark w-100 rounded-pill py-2">Register</button>

                    <p class="text-center small mt-3 mb-0">
                        Have an account already? <a href="/login" class="text-decoration-none">Sign in</a>
                    </p>
                </form>

            </div>
        </div>
    </div>
</x-layout>