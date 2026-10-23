<x-layout title="Settings" hide-footer="true">
    <div class="d-flex flex-column justify-content-center align-items-center bg-primary bg-gradient  p-4"
        style="min-height: 100vh;">
        <h2 class="fw-bold text-center text-dark mb-4">Welcome {{auth()->user()->username}}</h2>

        <h3 class="fw-bold text-center text-dark mb-4">user icon goes here</h3>

        <div class="card shadow rounded-4 border-0" style="max-width: 400px; width: 100%;">
            <div class="card-body p-4">


                <form action="/changeUsername" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="username" class="form-label">Change Username</label>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control bg-light border-0" id="username" name="username"
                                placeholder="your new username" required>
                            <button type="submit" class="btn btn-dark  rounded-pill py-2">Update</button>
                        </div>
                        <hr>
                    </div>
                </form>
                <form action="/changeEmail" method="POST">
                    @csrf
                    <div class="mb-3 ">
                        <label for="email" class="form-label">Change Email</label>
                        <div class="d-flex gap-2">
                            <input type="email" class="form-control bg-light border-0" id="email" name="email"
                                placeholder="your new email" required>
                            <button type="submit" class="btn btn-dark  rounded-pill py-2">Update</button>
                        </div>
                        <hr>
                    </div>
                </form>
                <form action="/changePassword" method="POST">
                    @csrf
                    <div class="mb-3 gap-3">
                        <label for="oldPass" class="form-label">Change Password</label>
                        <div class="mb-3">
                            <input type="password" class="form-control bg-light border-0" id="oldPass" name="oldPass"
                                placeholder="old password" required>
                                @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                        </div>
                        <div class="mb-3">

                            <input type="password" class="form-control bg-light border-0" id="newPass" name="newPass"
                                placeholder="new password" required>
                                @error('newPass')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                 @enderror
                        </div>
                        <div class="mb-3">

                            <input type="password" class="form-control bg-light border-0" id="newPass_confirmation"
                                name="newPass_confirmation" placeholder="confirm new password" required>
                        </div>
                        <button type="submit" class="btn btn-dark w-100  rounded-pill py-2">Update</button>

                        <hr>
                    </div>
                </form>
                <form action="/deleteUser" method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone.');">
                    @csrf
                    <div class="mb-3 gap-3">
                        <label for="password" class="form-label">Delete Account Password</label>
                        <div class="mb-3">
                            <input type="password" class="form-control bg-light border-0" id="password" name="password"
                                placeholder="old password" required>
                                @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                        </div>
                        <button type="submit" class="btn btn-danger w-100  rounded-pill py-2">Delete</button>

                        <hr>
                    </div>
                </form>




            </div>
        </div>
    </div>
</x-layout>