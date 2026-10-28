<x-layout>
  <div class="p-4">
    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <p>Check your email for a verification link.</p>
    <form method="POST" action="/email/verification-notification">
      @csrf
      <button class="btn btn-primary btn-sm">Resend verification email</button>
    </form>
  </div>
</x-layout>