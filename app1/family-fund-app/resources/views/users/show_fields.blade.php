<!-- Name Field -->
<div class="form-group">
<label for="name">Name:</label>
    <p>{{ $user->name }}</p>
</div>

<!-- Email Field -->
<div class="form-group">
<label for="email">Email:</label>
    <p>{{ $user->email }}</p>
</div>

<!-- Email Verified At Field -->
<div class="form-group">
<label for="email_verified_at">Email Verified At:</label>
    <p>{{ $user->email_verified_at }}</p>
</div>

<!-- Password Field -->
<div class="form-group">
<label for="password">Password:</label>
    <p>{{ $user->password }}</p>
</div>

<!-- Remember Token Field -->
<div class="form-group">
<label for="remember_token">Remember Token:</label>
    <p>{{ $user->remember_token }}</p>
</div>

