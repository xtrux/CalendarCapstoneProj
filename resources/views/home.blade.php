<x-layout title="Main Page">
    @auth
     <div class="card shadow mt-4">
            <div class="card-body">
                <div>
                    <h1 class="display-6 fw-bold">Calendar App</h1>
                    <p class="mt-4 text-muted">welcome {{ auth()->user()->username }} </p>
                </div>
            </div>
        </div>
    @else
        <div class="card shadow mt-4">
            <div class="card-body">
                <div>
                    <h1 class="display-6 fw-bold">Calendar App</h1>
                    <p class="mt-4 text-muted">This is main page.</p>
                </div>
            </div>
        </div>
    @endauth

</x-layout>