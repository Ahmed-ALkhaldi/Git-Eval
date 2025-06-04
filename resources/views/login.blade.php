<form action="{{ route('login') }}" method="post">
    @csrf
    <div class="row gy-3 overflow-hidden">
        <div class="col-12">
            <div class="form-floating mb-3">
                <input value="{{ old('email')}}" type="email" class="form-control @error('email') is-invalid @enderror " name="email" id="email" placeholder="name@example.com">
                <label for="email" class="form-label">Email</label>
                @error('email')
                    <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="col-12">
            <div class="form-floating mb-3">
                <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="password" value="" placeholder="Password">
                <label for="password" class="form-label">Password</label>
                @error('password')
                    <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="col-12">
            <div class="d-grid">
                <button class="btn bsb-btn-xl btn-primary py-3" type="submit">Log In Now</button>
            </div>
        </div>
    </div>
</form>